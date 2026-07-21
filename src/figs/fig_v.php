<?php

/**
 * shorthand version of value
 */
function fig_v(string $variableName, mixed $default = '', bool $escape = false): mixed
{
    return fig::value($variableName, $default, $escape);
}
