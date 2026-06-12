<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
ph_require_login();

$gameFile = __DIR__ . '/pursuit_hunter_v5.html';

if (!is_file($gameFile)) {
    http_response_code(500);
    echo 'Fichier du jeu introuvable.';
    exit;
}

$practice = isset($_GET['practice']);
$bootstrap = sprintf(
    '<script>window.PH_CSRF=%s;window.PH_PRACTICE=%s;</script>',
    json_encode(ph_csrf_token()),
    $practice ? 'true' : 'false'
);

header('Content-Type: text/html; charset=utf-8');

$html = (string) file_get_contents($gameFile);
echo str_contains($html, '<body>')
    ? str_replace('<body>', '<body>' . $bootstrap, $html)
    : $bootstrap . $html;
