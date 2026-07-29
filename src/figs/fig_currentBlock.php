<?php

/**
 * The name of the innermost block currently open, or '' when none is.
 *
 * end() returns false on an empty array, and this declares a string return, so
 * asking outside any block used to be a TypeError rather than an answer -
 * exactly when a template would ask, since the point of asking is to find out.
 *
 * It also took a $name parameter it never read. Left in place so existing
 * callers keep working, but it is ignored and marked as such.
 */
function fig_currentblock(string $name = ''): string
{
    $blocks = fig::value('_fig##blocks_', []);

    return $blocks === [] ? '' : (string) end($blocks);
}
