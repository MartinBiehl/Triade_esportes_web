<?php
/**
 * Primeiro arquivo carregado por toda página:
 * require __DIR__ . '/includes/bootstrap.php';
 */

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/funcoes.php';
require __DIR__ . '/componentes.php';
require __DIR__ . '/layout.php';

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

if ($config['modo_debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// Sessão: guarda a sacola e o login. O cookie não pode ser lido pelo JavaScript.
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'use_strict_mode' => true,
    ]);
}
