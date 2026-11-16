<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Cargar variables de entorno
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            $_ENV[$k] = $v;
        }
    }
}

echo "=== SISTEMA WEB COEDE: SCRIPT DE CARGA DE DATOS DE DEMOSTRACIÓN (SEED) ===\n";

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'coede_db';
$appUser = $_ENV['DB_USERNAME'] ?? 'coede_app';
$appPass = $_ENV['DB_PASSWORD'] ?? 'CoedeSegura2026!';

try {
    $db = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $appUser, $appPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (\Exception $e) {
    $db = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

$seedFile = dirname(__DIR__) . '/database/seeds/demo_seed.sql';
if (!file_exists($seedFile)) {
    die("Archivo de seed no encontrado: {$seedFile}\n");
}

echo "Cargando datos sintéticos de demostración desde demo_seed.sql...\n";
$sql = file_get_contents($seedFile);

// Actualizar contraseñas con hash BCRYPT real generado en tiempo de ejecución para mayor seguridad
$adminHash = password_hash('AdminCoede2026!', PASSWORD_BCRYPT);
$serviceHash = password_hash('ServicioCoede2026!', PASSWORD_BCRYPT);

$db->exec($sql);

// Asegurar hashes correctos
$stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE username = 'admin'");
$stmt->execute(['hash' => $adminHash]);

$stmt2 = $db->prepare("UPDATE users SET password_hash = :hash WHERE username = 'servicio'");
$stmt2->execute(['hash' => $serviceHash]);

echo "¡SEED COMPLETADO EXITOSAMENTE!\n";
echo "Usuarios disponibles para prueba local:\n";
echo "  1. ADMINISTRADOR: Usuario 'admin'    | Clave: 'AdminCoede2026!'\n";
echo "  2. SERVICIO:      Usuario 'servicio' | Clave: 'ServicioCoede2026!'\n";

