<?php
namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                   ($_SERVER['SERVER_PORT'] ?? 80) == 443;

        if (!headers_sent()) {
            // Configuración segura de cookies de sesión
            session_set_cookie_params([
                'lifetime' => 0, // Expira al cerrar el navegador
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_name('COEDE_SESSID');
        }

        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            session_start();
        }
        self::$started = true;

        // Comprobación de inactividad de 15 minutos (900 segundos)
        $lifetime = (int)($_ENV['SESSION_LIFETIME_MINUTES'] ?? 15) * 60;
        $now = time();

        if (isset($_SESSION['last_activity'])) {
            $inactive = $now - $_SESSION['last_activity'];
            if ($inactive > $lifetime) {
                // Sesión expirada por inactividad
                self::destroy();
                session_start();
                $_SESSION['flash_error'] = 'Su sesión ha expirado por 15 minutos de inactividad por motivos de seguridad.';
                return;
            }
        }
        
        // Actualizar último timestamp de actividad si el usuario está autenticado
        if (isset($_SESSION['user_id'])) {
            $_SESSION['last_activity'] = $now;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['flash_' . $key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        $flashKey = 'flash_' . $key;
        if (isset($_SESSION[$flashKey])) {
            $value = $_SESSION[$flashKey];
            unset($_SESSION[$flashKey]);
            return $value;
        }
        return $default;
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            self::$started = false;
        }
    }
}
