<?php

/**
 * Render $format with $value in it, but only when $value is worth showing.
 *
 * The function was named fig_shownif while the facade routes fig::showIf() to
 * fig_showIf - PHP function names are case-insensitive but "shownif" is not
 * "showif", so calling it was a hard "function not found", not a subtle bug.
 *
 * Note this is presently identical to fig_hiddenIf(); which of the two should
 * invert is a design question left alone deliberately.
 */
function fig_showif(string $format = '', string $value = '', string $considerNotEmpty = ''): string
{
    $html = '';

    if ($value != $considerNotEmpty) {
        $html = fig::escape(sprintf($format, $value));
    }

    return $html;
}
