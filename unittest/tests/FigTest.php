<?php

declare(strict_types=1);

use orange\framework\Data;

/**
 * fig is a static facade over a directory of plugin functions, so everything it
 * owns is static state - the plugin search paths, the resolved-plugin map, and
 * the shared data object. Each test resets all three by reflection, otherwise a
 * path added by one test is still registered for the next.
 *
 * The plugins that render a view (include, includeModal, and render by way of
 * include) reach for container()->view and are not covered here; they need a
 * built container rather than a unit fixture.
 */
final class FigTest extends unitTestHelper
{
    /** the buffer depth PHPUnit itself is holding when the test starts */
    private int $baseBufferLevel = 0;

    protected function setUp(): void
    {
        $this->baseBufferLevel = ob_get_level();

        $this->resetFig();

        fig::configure([], Data::newInstance());
    }

    protected function tearDown(): void
    {
        // A test that threw mid-block leaves its buffer open, which would
        // swallow the rest of the suite's output - but only unwind as far as
        // the level this test started at. PHPUnit holds a buffer of its own and
        // closing that is what "closed output buffers other than its own" means.
        while (ob_get_level() > $this->baseBufferLevel) {
            ob_end_clean();
        }

        $this->resetFig();
    }

    private function resetFig(): void
    {
        new ReflectionProperty(fig::class, 'pluginPaths')->setValue(null, []);
        new ReflectionProperty(fig::class, 'loadedPlugins')->setValue(null, []);
    }

    /**
     * @return array<int, string>
     */
    private function pluginPaths(): array
    {
        return new ReflectionProperty(fig::class, 'pluginPaths')->getValue();
    }

    /* --- the facade --------------------------------------------------------- */

    public function testConfigureRegistersThePackagesOwnPluginDirectory(): void
    {
        $this->assertContains(realpath(__DIR__ . '/../../src/figs'), array_map('realpath', $this->pluginPaths()));
    }

    public function testAddPathAppendsAndAddPathFirstPrepends(): void
    {
        $before = count($this->pluginPaths());

        fig::addPath('/tmp/appended');
        fig::addPath('/tmp/prepended', true);

        $paths = $this->pluginPaths();

        $this->assertSame('/tmp/prepended', $paths[0]);
        $this->assertSame('/tmp/appended', $paths[count($paths) - 1]);
        $this->assertCount($before + 2, $paths);
    }

    public function testAddPathStripsATrailingSeparator(): void
    {
        fig::addPath('/tmp/withslash/', true);

        $this->assertSame('/tmp/withslash', $this->pluginPaths()[0]);
    }

    public function testAddPathsRegistersEveryPath(): void
    {
        fig::addPaths(['/tmp/one', '/tmp/two']);

        $paths = $this->pluginPaths();

        $this->assertContains('/tmp/one', $paths);
        $this->assertContains('/tmp/two', $paths);
    }

    public function testConfigureHonoursExtraPluginDirectories(): void
    {
        $this->resetFig();

        fig::configure(['plugins directories' => ['/tmp/extra']], Data::newInstance());

        $this->assertContains('/tmp/extra', $this->pluginPaths());
    }

    public function testAnUnknownPluginThrows(): void
    {
        $this->expectException(FigException::class);
        $this->expectExceptionMessage('Could not locate fig plugin "fig_nosuchplugin"');

        fig::nosuchplugin();
    }

    /* --- set / get / value -------------------------------------------------- */

    public function testSetAndValueRoundTripAString(): void
    {
        fig::set('title', 'Hello');

        $this->assertSame('Hello', fig::value('title'));
        $this->assertSame('Hello', fig::get('title'));
        $this->assertSame('Hello', fig::v('title'));
    }

    public function testValueReturnsTheDefaultWhenUnset(): void
    {
        $this->assertSame('', fig::value('missing'));
        $this->assertSame('fallback', fig::value('missing', 'fallback'));
        $this->assertNull(fig::get('missing'));
        $this->assertSame('other', fig::get('missing', 'other'));
    }

    public function testValueCanEscapeOnTheWayOut(): void
    {
        fig::set('unsafe', '<b>&</b>');

        $this->assertSame('<b>&</b>', fig::value('unsafe'));
        $this->assertSame('&lt;b&gt;&amp;&lt;/b&gt;', fig::value('unsafe', '', true));
    }

    public function testSetOverwritesByDefault(): void
    {
        fig::set('name', 'first');
        fig::set('name', 'second');

        $this->assertSame('second', fig::value('name'));
    }

    public function testSetAppendsAndPrependsStrings(): void
    {
        fig::set('list', 'b');
        fig::set('list', 'c', fig::AFTER);
        fig::set('list', 'a', fig::BEFORE);

        $this->assertSame('abc', fig::value('list'));
    }

    public function testAppendAndPrependAreTheNamedFormsOfThat(): void
    {
        fig::set('css', 'base');
        fig::append('css', '-after');
        fig::prepend('css', 'before-');

        $this->assertSame('before-base-after', fig::value('css'));
    }

    /**
     * Appending to a list has to actually append. It used to use the + union
     * operator, which keeps the left-hand entry wherever a key exists on both
     * sides - so for integer-keyed lists the new element was silently dropped.
     */
    public function testAppendingToAnArrayAddsToItRatherThanBeingDropped(): void
    {
        fig::set('scripts', ['one'], fig::AFTER);
        fig::set('scripts', ['two'], fig::AFTER);
        fig::set('scripts', ['three'], fig::AFTER);

        $this->assertSame(['one', 'two', 'three'], fig::value('scripts'));
    }

    public function testPrependingToAnArrayPutsItInFront(): void
    {
        fig::set('scripts', ['second'], fig::AFTER);
        fig::set('scripts', ['first'], fig::BEFORE);

        $this->assertSame(['first', 'second'], fig::value('scripts'));
    }

    /* --- blocks -------------------------------------------------------------- */

    public function testABlockCapturesWhatIsEchoedInsideIt(): void
    {
        fig::block('header');
        echo 'captured output';
        fig::end();

        $this->assertSame('captured output', fig::value('header'));
    }

    /**
     * The block name is pushed onto a stack, so blocks nest - which is the case
     * the array-append bug broke: the inner name was lost and end() then closed
     * the outer one while still inside the inner buffer.
     */
    public function testBlocksNest(): void
    {
        fig::block('outer');
        echo 'OUTER';

        fig::block('inner');
        echo 'INNER';
        fig::end();

        fig::end();

        $this->assertSame('INNER', fig::value('inner'));
        $this->assertSame('OUTER', fig::value('outer'));
    }

    public function testInBlockAndCurrentBlockTrackTheStack(): void
    {
        $this->assertFalse(fig::inBlock());
        $this->assertSame('', fig::currentBlock(''));

        fig::block('outer');
        $outerCurrent = fig::currentBlock('');
        $outerIn = fig::inBlock();

        fig::block('inner');
        $innerCurrent = fig::currentBlock('');

        fig::end();
        fig::end();

        $this->assertTrue($outerIn);
        $this->assertSame('outer', $outerCurrent);
        $this->assertSame('inner', $innerCurrent);
        $this->assertFalse(fig::inBlock());
    }

    /**
     * currentBlock() declares a string return and end() gives false on an empty
     * array, so asking outside a block used to be a TypeError - which is
     * precisely when a template would ask.
     */
    public function testCurrentBlockOutsideAnyBlockIsEmptyRatherThanAFatal(): void
    {
        $this->assertSame('', fig::currentBlock(''));
    }

    public function testHasBlockReportsWhetherThatBlockIsOpen(): void
    {
        $this->assertFalse(fig::hasBlock('header'));

        fig::block('header');
        $openInside = fig::hasBlock('header');
        fig::end();

        $this->assertTrue($openInside);
        // hasBlock tracks what is OPEN, so a finished block is no longer one
        $this->assertFalse(fig::hasBlock('header'));
    }

    /**
     * The stack is a list, so its names are values rather than keys - removing
     * by key never matched anything and this was a silent no-op.
     */
    public function testRemoveBlockTakesTheNameOffTheStack(): void
    {
        fig::block('outer');
        fig::block('inner');

        fig::removeBlock('inner');

        $this->assertFalse(fig::hasBlock('inner'));
        $this->assertTrue(fig::hasBlock('outer'));
        $this->assertSame('outer', fig::currentBlock(''));

        // the stack stays a list, so end() can still pop it
        fig::end();

        $this->assertFalse(fig::inBlock());
    }

    public function testRemovingABlockThatIsNotOpenChangesNothing(): void
    {
        fig::block('outer');

        fig::removeBlock('nosuchblock');

        $this->assertTrue(fig::hasBlock('outer'));

        fig::end();
    }

    public function testEndingWithoutABlockThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not in a block');

        fig::end();
    }

    public function testABlockCanAppendToAnExistingValue(): void
    {
        fig::set('body', 'first ');

        fig::block('body');
        echo 'second';
        fig::end(fig::AFTER);

        $this->assertSame('first second', fig::value('body'));
    }

    /* --- extends / render guards -------------------------------------------- */

    public function testExtendsRecordsTheParentView(): void
    {
        fig::extends('layouts/main');

        $this->assertSame('layouts/main', fig::value('_fig##extends_'));
    }

    public function testExtendingTwiceThrows(): void
    {
        fig::extends('layouts/main');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('already extending');

        fig::extends('layouts/other');
    }

    public function testRenderingWithoutExtendingThrows(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('aren\'t extending');

        fig::render();
    }

    /* --- formatting plugins -------------------------------------------------- */

    public function testEscapeAndItsShorthand(): void
    {
        $this->assertSame('&lt;b&gt;&amp;&quot;&lt;/b&gt;', fig::escape('<b>&"</b>'));
        $this->assertSame(fig::escape('<b>'), fig::e('<b>'));
    }

    public function testEscapeHandlesSingleQuotesAndUnicode(): void
    {
        // ENT_QUOTES, so single quotes go too
        $this->assertSame('&#039;', fig::escape("'"));
        // and printable unicode is left alone
        $this->assertSame('résumé', fig::escape('résumé'));
    }

    public function testMoneyFormatsWithTheSignOutsideTheSymbol(): void
    {
        $this->assertSame('$0.00', fig::money(0));
        $this->assertSame('$1,234.50', fig::money(1234.5));
        $this->assertSame('-$1,234.50', fig::money(-1234.5));
        // strings are coerced
        $this->assertSame('$12.00', fig::money('12'));
    }

    /**
     * The guard checked in_array() against the map's values while the lookup
     * read by key, so the intended call threw and the other way TypeError'd.
     */
    public function testMapTranslatesAKeyToItsLabel(): void
    {
        $this->assertSame('Apple', fig::map('a', ['a' => 'Apple', 'b' => 'Banana']));
        $this->assertSame('Banana', fig::map('b', ['a' => 'Apple', 'b' => 'Banana']));
    }

    public function testMapThrowsForAKeyItDoesNotHave(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot locate "z" in map');

        fig::map('z', ['a' => 'Apple']);
    }

    public function testWrapSurroundsAString(): void
    {
        $this->assertSame('<li>one</li>', fig::wrap('one', '<li>', '</li>', false));
    }

    public function testWrapRepeatsItselfOverAnArray(): void
    {
        $this->assertSame(
            '<li>one</li><li>two</li>',
            fig::wrap(['one', 'two'], '<li>', '</li>', false),
        );
    }

    public function testWrapEscapesByDefault(): void
    {
        $this->assertSame('<li>&lt;b&gt;</li>', fig::wrap('<b>', '<li>', '</li>'));
    }

    /**
     * fig::showIf() was a hard fatal - the file defined fig_shownif(), which is
     * not the name the facade routes to.
     */
    public function testShowIfRendersOnlyWhenTheValueDiffers(): void
    {
        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', fig::showIf('<b>%s</b>', 'x'));
        $this->assertSame('', fig::showIf('<b>%s</b>', ''));
        $this->assertSame('', fig::showIf('<b>%s</b>', 'skip', 'skip'));
    }

    public function testHiddenIfIsCallable(): void
    {
        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', fig::hiddenIf('<b>%s</b>', 'x'));
        $this->assertSame('', fig::hiddenIf('<b>%s</b>', ''));
    }

    public function testSprintfFillsAndEscapesByDefault(): void
    {
        $this->assertSame('&lt;b&gt;a b&lt;/b&gt;', fig::sprintf('<b>%s %s</b>', ['a', 'b']));
        $this->assertSame('<b>a b</b>', fig::sprintf('<b>%s %s</b>', ['a', 'b'], false));
    }

    public function testElementClosesTheTagUnlessItIsSelfClosing(): void
    {
        $this->assertStringEndsWith('</p>', fig::element('p', [], 'text'));
        $this->assertStringContainsString('text', fig::element('p', [], 'text'));

        // br is self-closing, so no closing tag is emitted
        $this->assertStringNotContainsString('</br>', fig::element('br'));
        $this->assertStringEndsWith('</span>', fig::element('span'));
    }

    /**
     * Documents a defect rather than blessing it. The attributes are built with
     * str_replace('=', '="', http_build_query($attr, '', '" ')) and the tag is
     * then closed with '">', which only lines up when there is at least one
     * attribute - with none, the opening tag comes out as `<p ">`. The same
     * expression also leaves attribute values unescaped, so a value containing a
     * double quote escapes the attribute entirely. Left alone here because
     * fixing it is a rewrite of the attribute builder, and the framework's own
     * element() helper carries the identical bug and should change with it.
     */
    public function testElementEmitsAMalformedTagWhenThereAreNoAttributes(): void
    {
        $this->assertSame('<p ">text</p>', fig::element('p', [], 'text'));
    }

    public function testElementEscapesItsContentUnlessTold(): void
    {
        $this->assertStringContainsString('&lt;b&gt;', fig::element('p', [], '<b>'));
        $this->assertStringContainsString('<b>', fig::element('p', [], '<b>', false));
    }

    public function testDateFormatsAnExplicitTimestamp(): void
    {
        $timestamp = mktime(13, 45, 0, 7, 4, 2026);

        $this->assertSame('2026-07-04', fig::date($timestamp, 'Y-m-d'));
        $this->assertSame('2026-07-04 13:45', fig::date($timestamp, 'Y-m-d H:i'));
    }

    public function testDateParsesAStringAndHandlesNow(): void
    {
        $this->assertSame('2026-07-04', fig::date('2026-07-04', 'Y-m-d'));
        $this->assertSame(date('Y-m-d'), fig::date('now', 'Y-m-d'));
    }

    /**
     * Anything that doesn't parse to a real timestamp reads as empty rather
     * than as the epoch, which would be a plausible-looking wrong date.
     */
    public function testDateReturnsEmptyForSomethingUnparseable(): void
    {
        $this->assertSame('', fig::date('not a date', 'Y-m-d'));
        $this->assertSame('', fig::date(0, 'Y-m-d'));
    }
}
