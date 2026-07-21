<?php

/**
 * wrapper for escaping html
 */
function fig_escape(string $html): string
{
    return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
}
