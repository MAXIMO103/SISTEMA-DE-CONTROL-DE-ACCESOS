<?php
require_once __DIR__ . '/../vendor/autoload.php';

$cookieFile = __DIR__ . '/test_gate_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function httpReq($url, $method = 'GET', $data = [], $headers = []) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

echo "=== VERIFICACIÓN DEL REDISEÑO DE CONTROL DE ACCESO ===\n";

// 1. Obtener CSRF y Login
$res = httpReq('http://localhost/coede_beta/login');
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $res['body'], $m);
$csrf = $m[1] ?? '';

$login = httpReq('http://localhost/coede_beta/login', 'POST', [
    'csrf_token' => $csrf,
    'username'   => 'admin',
    'password'   => 'AdminCoede2026!'
]);
echo "[1] Login Admin: HTTP " . $login['code'] . "\n";

// 2. Comprobar HTML de Gate
$gate = httpReq('http://localhost/coede_beta/gate');
echo "[2] Pantalla Gate: HTTP " . $gate['code'] . "\n";

$checks = [
    'tacticalClock'        => str_contains($gate['body'], 'id="tacticalClock"'),
    'scannerCard'          => str_contains($gate['body'], 'id="scannerCard"'),
    'inputDni'             => str_contains($gate['body'], 'id="inputDni"'),
    'gateBeacon'           => str_contains($gate['body'], 'id="gateBeacon"'),
    'militaryIdCard'       => str_contains($gate['body'], 'military-id-card'),
    'btnOpenIncidentModal' => str_contains($gate['body'], 'id="btnOpenIncidentModal"'),
    'sweetalert2'          => str_contains($gate['body'], 'sweetalert2.all.min.js'),
    'incidentModal'        => str_contains($gate['body'], 'id="incidentModal"')
];

foreach ($checks as $name => $passed) {
    echo "  -> Componente '$name': " . ($passed ? "PRESENTE [✓]" : "FALTA [✗]") . "\n";
}

// 3. Consulta Trabajador MySQL
$pLook = httpReq('http://localhost/coede_beta/api/gate/lookup?dni=45892134', 'GET', [], ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
$pData = json_decode($pLook['body'], true);
echo "[3] Lookup MySQL Trabajador: " . ($pData['personnel']['names'] ?? 'Error') . " (Vigencia: " . ($pData['status_recommendation'] ?? '-') . ")\n";

// 4. Consulta Visitante Adapter Mock
$vLook = httpReq('http://localhost/coede_beta/api/gate/lookup?dni=10293847', 'GET', [], ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
$vData = json_decode($vLook['body'], true);
echo "[4] Lookup Visitante Mock: " . ($vData['visitor']['full_name'] ?? 'Error') . " (" . ($vData['status_recommendation'] ?? '-') . ")\n";

// 5. Consulta Acceso Restringido (Denegado con DNI 00112233)
$rLook = httpReq('http://localhost/coede_beta/api/gate/lookup?dni=00112233', 'GET', [], ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
$rData = json_decode($rLook['body'], true);
echo "[5] Lookup Restricción Seguridad: " . ($rData['status_recommendation'] ?? '-') . " - " . ($rData['status_label'] ?? '-') . "\n";

// 6. Registro de Incidencia Rápida AJAX
$inc = httpReq('http://localhost/coede_beta/incidents/store', 'POST', [
    'csrf_token'  => $csrf,
    'event_type'  => 'Evasión de control de guardia / Salida no constatada',
    'severity'    => 'MEDIA',
    'ref_dni'     => '10293847',
    'ref_plate'   => 'B6A-912',
    'description' => 'Incidencia de prueba rápida registrada desde el modal del puesto de control de acceso.'
], ['Accept: application/json', 'X-Requested-With: XMLHttpRequest']);
$incData = json_decode($inc['body'], true);
echo "[6] Registro Incidencia AJAX: " . ($incData['success'] ? "EXITOSA con código: {$incData['code']}" : "FALLÓ") . "\n";

if (file_exists($cookieFile)) unlink($cookieFile);
echo "=== FIN DE PRUEBAS DE VERIFICACIÓN ===\n";
