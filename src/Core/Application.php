<?php
namespace App\Core;

class Application
{
    private Router $router;
    private Request $request;

    public function __construct()
    {
        $this->loadEnvironment();
        $this->setTimezone();
        Session::start();

        $this->router = new Router();
        $this->request = new Request();
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function run(): void
    {
        $this->router->dispatch($this->request);
    }

    private function loadEnvironment(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    // Eliminar comillas dobles o simples circundantes
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                    }
                    if (!isset($_ENV[$name])) {
                        $_ENV[$name] = $value;
                        putenv("{$name}={$value}");
                    }
                }
            }
        }
    }

    private function setTimezone(): void
    {
        $tz = $_ENV['APP_TIMEZONE'] ?? 'America/Lima';
        date_default_timezone_set($tz);
    }
}

