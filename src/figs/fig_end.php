<?php

/**
 * finish the buffering of a named block
 *
 * optionally appending to the current data variable
 */
function fig_end(int $append = fig::NORMAL): void
{
    // default to [] - fig::value()'s own default is '', and count() on a string
    // is a TypeError, so before any block had ever been opened this blew up on
    // the line below and the guard's message was unreachable
    $figblocks = fig::value('_fig##blocks_', []);

    if (count($figblocks) == 0) {
        throw new Exception('Cannot end block because you are not in a block.');
    }

    $name = array_pop($figblocks);

    fig::set('_fig##blocks_', $figblocks);

    // capture output buffer
    fig::set($name, ob_get_contents(), $append);

    ob_end_clean();
}
