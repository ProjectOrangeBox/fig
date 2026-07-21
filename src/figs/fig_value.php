<?php

/**
 * pull something from the data container
 *
 * since data is already "extracted" into the view anyway as proper local varaiables
 * this is really just a wrapper for that.
 */
function fig_value(string $variableName, mixed $default = '', bool $escape = false): mixed
{
    $variableValue = fig::get($variableName, $default);

    return $escape ? fig::escape($variableValue ?? '') : $variableValue;
}
