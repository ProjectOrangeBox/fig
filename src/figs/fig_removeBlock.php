<?php

/**
 * Drop a name from the stack of open blocks.
 *
 * The stack is a list - the names are the values, and the keys are just
 * positions - so unset($blocks[$name]) never matched anything and this was a
 * silent no-op. Find the entry, then remove it by its position, and reindex so
 * the result stays a list for array_pop() in fig_end() to work on.
 */
function fig_removeblock(string $name): void
{
    $blocks = fig::value('_fig##blocks_', []);

    $position = array_search($name, $blocks, true);

    if ($position !== false) {
        unset($blocks[$position]);

        $blocks = array_values($blocks);
    }

    fig::set('_fig##blocks_', $blocks);
}
