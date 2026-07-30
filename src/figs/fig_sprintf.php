<?php

/**
 * allow some basic formatting with optional
 * if the value is X show
 */
/**
 * @param array<array-key, mixed> $values
 */
function fig_sprintf(string $format = '', array $values = [], bool $escape = true): string
{
    $html = sprintf($format, ...$values);

    return ($escape) ? fig::escape($html) : $html;
}
