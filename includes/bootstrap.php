<?php
/**
 * Primeiro arquivo carregado por toda página:
 * require __DIR__ . '/includes/bootstrap.php';
 */

require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/funcoes.php';
require __DIR__ . '/componentes.php';
require __DIR__ . '/catalogo.php';
require __DIR__ . '/sacola.php';
require __DIR__ . '/frete.php';
require __DIR__ . '/checkout.php';
require __DIR__ . '/conta.php';
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

// Erro não tratado (ex.: banco desligado): mostra uma página amigável em vez da tela branca.
set_exception_handler(function (Throwable $erro) use ($config) {
    http_response_code(500);
    $banco = $erro instanceof PDOException;
    $detalhe = $config['modo_debug'] ? $erro->getMessage() . ' (' . basename($erro->getFile()) . ':' . $erro->getLine() . ')' : '';
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>Erro · ' . e(LOJA_NOME) . '</title></head>'
       . '<body style="font-family: system-ui, sans-serif; max-width: 480px; margin: 48px auto; padding: 0 16px; color: #07090D">'
       . '<h1 style="font-size: 24px">' . ($banco ? 'Banco de dados não conectado' : 'Algo deu errado') . '</h1>'
       . '<p>' . ($banco
            ? 'Confira se o MySQL está ligado no XAMPP e se os scripts da pasta <code>sql/</code> foram importados. O passo a passo está no README.'
            : 'Tente de novo em alguns instantes.') . '</p>'
       . ($detalhe ? '<pre style="white-space: pre-wrap; background: #F1F2F5; padding: 12px; border-radius: 8px">' . e($detalhe) . '</pre>' : '')
       . '</body></html>';
});

// Pix vencido vira pedido cancelado e devolve o estoque (não há tarefa agendada no XAMPP nem na InfinityFree).
cancelar_pix_vencidos();
