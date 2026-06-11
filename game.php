<?php

declare(strict_types=1);
require_once __DIR__ . '/config.php';
ph_require_login();
$gameFile = getenv('USERPROFILE') . '\\Downloads\\pursuit_hunter_v5.html';
if (!is_string($gameFile) || !is_file($gameFile)) {
	$gameFile = __DIR__ . '/pursuit_hunter_v5.html';
}

if (!is_file($gameFile)) {
	http_response_code(500);
	echo 'Fichier du jeu introuvable.';
	exit;
}

readfile($gameFile);
