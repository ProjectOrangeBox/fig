<?php

/**
 * this is helpful because you can add (set) values in data
 * these need to be retrieved with fig::value() because
 * when the view template is loaded the data is already
 * extracted locally
 */
function fig_set(string $name, mixed $value, int $append = fig::NORMAL): void
{
    logMsg('DEBUG', __METHOD__ . ' ' . $name);

    if (!isset(fig::$data[$name])) {
        if (is_array($value)) {
            fig::$data[$name] = [];
        } else {
            fig::$data[$name] = '';
        }
    }

    switch ($append) {
        case fig::BEFORE:
            if (is_array($value)) {
                // array_merge, not the + union operator. Union keeps the
                // left-hand entry whenever a key exists on both sides, so for
                // the integer-keyed lists this is mostly used for - the block
                // stack above all - appending a second element silently did
                // nothing. Opening a nested block lost its name, and the
                // matching end() then closed the wrong one.
                fig::$data[$name] = array_merge($value, fig::value($name, []));
            } else {
                fig::$data[$name] = $value . fig::value($name, '');
            }
            break;
        case fig::AFTER:
            if (is_array($value)) {
                fig::$data[$name] = array_merge(fig::value($name, []), $value);
            } else {
                fig::$data[$name] = fig::value($name, '') . $value;
            }
            break;
        default:
            // overwrite
            fig::$data[$name] = $value;
            break;
    }
}
