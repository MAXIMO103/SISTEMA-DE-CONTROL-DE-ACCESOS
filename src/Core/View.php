<?php
namespace App\Core;

class View
{
    public static function render(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        $basePath = dirname(__DIR__) . '/Views/';
        $viewFile = $basePath . str_replace('.', '/', $viewPath) . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "Vista no encontrada: {$viewPath}";
            exit;
        }

        // Extraer variables para la vista
        extract($data);

        // Capturar contenido de la vista
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === 'none') {
            echo $content;
            return;
        }

        $layoutFile = $basePath . 'layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }
}

// Funciones globales auxiliares para vistas
if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir);
        if ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) {
            return '/' . ltrim($path, '/');
        }
        return rtrim($scriptDir, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::getToken();
    }
}

