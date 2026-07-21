<?php

/**
 * shorter syntax for escaping html
 */
function fig_e(string $html): string
{
    return fig::escape($html);
}
