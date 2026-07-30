<?php

/**
 * Translate a stored value into its display label.
 *
 * The guard and the lookup used to disagree: it tested in_array($value, $map) -
 * which searches the labels - and then read $map[$value], which is a key. So the
 * intended call, map('a', ['a' => 'Apple']), threw "cannot locate", and the call
 * that got past the guard, map('Apple', ...), warned on an undefined key and
 * then TypeError'd on returning null. It is a key lookup, so the guard checks
 * for a key.
 */
/**
 * @param array<array-key, mixed> $map
 */
function fig_map(string $value, array $map): string
{
    if (!array_key_exists($value, $map)) {
        throw new Exception('Cannot locate "' . $value . '" in map.');
    }

    return (string) $map[$value];
}
