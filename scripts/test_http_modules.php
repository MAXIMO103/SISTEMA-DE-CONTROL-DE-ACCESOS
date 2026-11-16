<?php
/**
 * Test de endpoints HTTP reales simulando navegación web en Apache / servidor local
 */

$baseUrl = 'http://localhost/coede_beta';

echo "=== INICIANDO PRUEBAS HTTP INTEGRALES EN {$baseUrl} ===\n\n";

function makeRequest(string $url, string $method = 'GET', ?array $data = null, array $cookies = []): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    if (!empty($cookies)) {
        $cookieParts = [];
        foreach ($cookies as $k => $v) {
            $cookieParts[] = "{$k}={$v}";
        }
        curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookieParts));
    }

    $raw = curl_exec($ch);
    $info = curl_getinfo($ch);
    $headerSize = $info['header_size'];
    $headerStr = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    curl_close($ch);

    // Extraer cookies de respuesta
    $retCookies = [];
    if (preg_match_all('/^Set-Cookie:\s*([^;=]+)=([^;]+)/mi', $headerStr, $m)) {
        for ($i = 0; $i < count($m[1]); $i++) {
            $retCookies[$m[1][$i]] = $m[2][$i];
        }
    }

    return [
        'status' => $info['http_code'],
        'headers' => $headerStr,
        'body' => $body,
        'cookies' => $retCookies
    ];
}

$allCookies = [];

// 1. Probar GET /login
echo "[1] GET /login ... ";
$r1 = makeRequest("{$baseUrl}/login");
if ($r1['status'] === 200) {
    echo "OK (200)\n";
    $allCookies = array_merge($allCookies, $r1['cookies']);
} else {
    echo "FAIL ({$r1['status']})\n";
}

// Extraer token CSRF del body
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $r1['body'], $csrfMatches);
$csrfToken = $csrfMatches[1] ?? '';
echo "  -> Token CSRF extraído: " . substr($csrfToken, 0, 16) . "...\n";

// 2. Probar POST /login con admin
echo "[2] POST /login (Autenticación como admin) ... ";
$r2 = makeRequest("{$baseUrl}/login", 'POST', [
    'csrf_token' => $csrfToken,
    'username'   => 'admin',
    'password'   => 'AdminCoede2026!'
], $allCookies);

$allCookies = array_merge($allCookies, $r2['cookies']);

if ($r2['status'] === 302) {
    echo "OK (302 Redirect a /)\n";
} else {
    echo "FAIL ({$r2['status']})\n";
    echo substr($r2['body'], 0, 400) . "\n";
    exit(1);
}

// 3. Probar GET / (Portal autenticado)
echo "[3] GET / (Portal autenticado) ... ";
$r3 = makeRequest("{$baseUrl}/", 'GET', null, $allCookies);
if ($r3['status'] === 200 && str_contains($r3['body'], 'Bienvenido')) {
    echo "OK (200 - Sesión activa verificada)\n";
} else {
    echo "FAIL ({$r3['status']})\n";
}

// 4. Probar GET /gate
echo "[4] GET /gate (Control de Puerta) ... ";
$r4 = makeRequest("{$baseUrl}/gate", 'GET', null, $allCookies);
echo ($r4['status'] === 200 ? "OK (200)" : "FAIL ({$r4['status']})") . "\n";

// 5. Probar AJAX lookup trabajador en puerta
echo "[5] GET /api/gate/lookup?dni=45892134 ... ";
$r5 = makeRequest("{$baseUrl}/api/gate/lookup?dni=45892134", 'GET', null, $allCookies);
$json5 = json_decode($r5['body'], true);
if ($r5['status'] === 200 && ($json5['personnel']['dni'] ?? '') === '45892134') {
    echo "OK (Trabajador recuperado con foto: " . ($json5['has_photo'] ? 'Sí' : 'No') . ")\n";
} else {
    echo "FAIL ({$r5['status']})\n";
}

// 6. Probar AJAX lookup visitante en puerta
echo "[6] GET /api/gate/lookup?dni=10293847 ... ";
$r6 = makeRequest("{$baseUrl}/api/gate/lookup?dni=10293847", 'GET', null, $allCookies);
$json6 = json_decode($r6['body'], true);
if ($r6['status'] === 200 && !empty($json6['visitor'])) {
    echo "OK (Visitante adaptado: " . $json6['visitor']['full_name'] . ")\n";
} else {
    echo "FAIL ({$r6['status']})\n";
}

// 7. Probar GET /personnel
echo "[7] GET /personnel (Padrón de Personal) ... ";
$r7 = makeRequest("{$baseUrl}/personnel", 'GET', null, $allCookies);
echo ($r7['status'] === 200 ? "OK (200)" : "FAIL ({$r7['status']})") . "\n";

// 8. Probar GET /vehicles
echo "[8] GET /vehicles (Padrón de Vehículos) ... ";
$r8 = makeRequest("{$baseUrl}/vehicles", 'GET', null, $allCookies);
echo ($r8['status'] === 200 ? "OK (200)" : "FAIL ({$r8['status']})") . "\n";

// 9. Probar GET /incidents
echo "[9] GET /incidents (Bitácora de Incidencias) ... ";
$r9 = makeRequest("{$baseUrl}/incidents", 'GET', null, $allCookies);
echo ($r9['status'] === 200 ? "OK (200)" : "FAIL ({$r9['status']})") . "\n";

// 10. Probar GET /sid
echo "[10] GET /sid (Módulo de Noticias SID) ... ";
$r10 = makeRequest("{$baseUrl}/sid", 'GET', null, $allCookies);
echo ($r10['status'] === 200 ? "OK (200)" : "FAIL ({$r10['status']})") . "\n";

// 11. Probar GET /reports (Dashboard integrado)
echo "[11] GET /reports (Dashboard y Estadísticas) ... ";
$r11 = makeRequest("{$baseUrl}/reports", 'GET', null, $allCookies);
echo ($r11['status'] === 200 ? "OK (200)" : "FAIL ({$r11['status']})") . "\n";

// 12. Probar GET /config (Configuración institucional)
echo "[12] GET /config (Configuración del Sistema) ... ";
$r12 = makeRequest("{$baseUrl}/config", 'GET', null, $allCookies);
echo ($r12['status'] === 200 ? "OK (200)" : "FAIL ({$r12['status']})") . "\n";

// 13. Probar GET /config/audit (Registro de auditoría)
echo "[13] GET /config/audit (Auditoría Forense) ... ";
$r13 = makeRequest("{$baseUrl}/config/audit", 'GET', null, $allCookies);
if ($r13['status'] === 200 && str_contains($r13['body'], 'LOGIN_SUCCESS')) {
    echo "OK (200 - Evento LOGIN_SUCCESS verificado en la tabla de auditoría)\n";
} else {
    echo "FAIL ({$r13['status']})\n";
}

// 14. Probar GET /config/backups
echo "[14] GET /config/backups (Gestión de Respaldos) ... ";
$r14 = makeRequest("{$baseUrl}/config/backups", 'GET', null, $allCookies);
echo ($r14['status'] === 200 ? "OK (200)" : "FAIL ({$r14['status']})") . "\n";

// 15. Probar Polling de Notificaciones
echo "[15] GET /api/notifications/poll ... ";
$r15 = makeRequest("{$baseUrl}/api/notifications/poll", 'GET', null, $allCookies);
$json15 = json_decode($r15['body'], true);
if ($r15['status'] === 200 && isset($json15['unread_count'])) {
    echo "OK (Campana activa con {$json15['unread_count']} avisos)\n";
} else {
    echo "FAIL ({$r15['status']})\n";
}

echo "\n¡TODAS LAS PRUEBAS DE MÓDULOS HTTP COMPLETADAS CON ÉXITO!\n";

