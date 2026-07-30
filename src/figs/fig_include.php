<?php

/**
 * include another template
 */
/**
 * @param array<string, mixed> $data
 */
function fig_include(?string $view, array $data = []): void
{
    echo container()->view->render($view, $data);
}
