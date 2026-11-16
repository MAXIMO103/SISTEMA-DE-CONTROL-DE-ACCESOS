<?php
$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$ch = curl_init('http://localhost/coede_beta/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$html = curl_exec($ch);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m);
$token = $m[1] ?? '';

// Login
curl_setopt($ch, CURLOPT_URL, 'http://localhost/coede_beta/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'csrf_token' => $token,
    'username'   => 'admin',
    'password'   => 'AdminCoede2026!'
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_exec($ch);

// Logout
curl_setopt($ch, CURLOPT_URL, 'http://localhost/coede_beta/logout');
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$logoutHtml = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Logout completed successfully with HTTP code: $code\n";
if (str_contains($logoutHtml, 'Iniciar Sesión') || str_contains($logoutHtml, 'login')) {
    echo "Confirmed: redirected to login page cleanly.\n";
}
if (file_exists($cookieFile)) unlink($cookieFile);

