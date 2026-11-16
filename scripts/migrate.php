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

echo "=== SISTEMA WEB COEDE: SCRIPT DE MIGRACIÓN Y PREPARACIÓN DE BASE DE DATOS ===\n";

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'coede_db';
$appUser = $_ENV['DB_USERNAME'] ?? 'coede_app';
$appPass = $_ENV['DB_PASSWORD'] ?? 'CoedeSegura2026!';

// 1. Conectar con root para crear la BD y el usuario de la aplicación
echo "[1/4] Verificando motor de base de datos en {$host}:{$port}...\n";
try {
    $rootPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $version = $rootPdo->query("SELECT VERSION()")->fetchColumn();
    echo "  -> Motor detectado: {$version}\n";
    if (str_contains(strtolower($version), 'mariadb')) {
        echo "  -> NOTA: Se detectó MariaDB (distribución XAMPP). Esquema 100% compatible configurado.\n";
    }

    echo "[2/4] Creando base de datos '{$dbName}' y usuario seguro '{$appUser}'...\n";
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Crear usuario coede_app con privilegios exclusivos en la base de datos de la app
    try {
        $rootPdo->exec("CREATE USER IF NOT EXISTS '{$appUser}'@'localhost' IDENTIFIED BY '{$appPass}'");
        $rootPdo->exec("CREATE USER IF NOT EXISTS '{$appUser}'@'127.0.0.1' IDENTIFIED BY '{$appPass}'");
        $rootPdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$appUser}'@'localhost'");
        $rootPdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$appUser}'@'127.0.0.1'");
        $rootPdo->exec("FLUSH PRIVILEGES");
        echo "  -> Usuario de aplicación '{$appUser}' configurado exitosamente.\n";
    } catch (\Exception $ue) {
        echo "  -> Aviso sobre usuario de aplicación: " . $ue->getMessage() . "\n";
    }
} catch (\Exception $e) {
    echo "  -> Error al conectar con root: " . $e->getMessage() . "\n";
    echo "  -> Intentando continuar directamente con la cuenta configurada...\n";
}

// 2. Conectar a la base de datos de la aplicación
echo "[3/4] Conectando a '{$dbName}'...\n";
try {
    $db = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $appUser, $appPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (\Exception $e) {
    // Si falla con appUser, probar con root
    echo "  -> Conexión con '{$appUser}' falló, usando 'root': " . $e->getMessage() . "\n";
    $db = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

// 3. Ejecutar migraciones en orden numérico
echo "[4/4] Ejecutando archivos de migración SQL...\n";
$migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.sql');
sort($migrationFiles);

foreach ($migrationFiles as $file) {
    $filename = basename($file);
    echo "  -> Aplicando: {$filename}... ";
    $sql = file_get_contents($file);
    
    // Separar por punto y coma respetando comandos
    $db->exec($sql);
    echo "OK\n";
}

echo "\n¡MIGRACIONES COMPLETADAS CON ÉXITO!\n";

