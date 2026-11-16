<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '3306';
            $db   = $_ENV['DB_DATABASE'] ?? 'coede_db';
            $user = $_ENV['DB_USERNAME'] ?? 'coede_app';
            $pass = $_ENV['DB_PASSWORD'] ?? 'CoedeSegura2026!';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
                // Configurar zona horaria de sesión en Lima (-05:00)
                self::$instance->exec("SET time_zone = '-05:00'");
            } catch (PDOException $e) {
                // Si la conexión falla con coede_app (por ejemplo antes de crear usuario), 
                // permitir conexión con root para facilitar instalación inicial
                if ($user !== 'root' && ($_ENV['APP_ENV'] ?? 'development') === 'development') {
                    try {
                        $dsnRoot = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
                        self::$instance = new PDO($dsnRoot, 'root', '', $options);
                        self::$instance->exec("SET time_zone = '-05:00'");
                        return self::$instance;
                    } catch (PDOException $e2) {
                        // Lanzar excepción controlada
                    }
                }
                throw new PDOException("Error de conexión a la base de datos COEDE: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }

    public static function setConnection(PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}

