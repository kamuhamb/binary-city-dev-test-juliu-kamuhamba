<?php
declare(strict_types=1);

namespace App\Core;

/** Renders a PHP template inside views/layout.php. */
final class View
{
    public static function render(string $template, array $data = []): void
    {
        $viewsDir = __DIR__ . '/../../views/';
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewsDir . $template . '.php';
        $content = ob_get_clean();
        require $viewsDir . 'layout.php';
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
