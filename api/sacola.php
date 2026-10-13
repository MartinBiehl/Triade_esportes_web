<?php
/**
 * Ações da sacola. Recebe POST do formulário (sem JavaScript) ou do fetch (com JavaScript).
 *   acao=adicionar  produto_id, tamanho, quantidade
 */
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_validar();

$acao       = $_POST['acao'] ?? '';
$produto_id = (int) ($_POST['produto_id'] ?? 0);
$tamanho    = trim((string) ($_POST['tamanho'] ?? ''));
$quantidade = (int) ($_POST['quantidade'] ?? 1);
$voltar     = 'produto.php?id=' . $produto_id;

if ($acao === 'adicionar') {
    $erro = sacola_adicionar($produto_id, $tamanho, $quantidade);

    if (pedido_ajax()) {
        responder_json([
            'ok'         => $erro === null,
            'mensagem'   => $erro ?? 'Adicionado à sacola',
            'quantidade' => sacola_quantidade(),
        ], $erro === null ? 200 : 422);
    }

    if ($erro === null) {
        aviso_definir('Adicionado à sacola', 'sucesso', ['rotulo' => 'Ver sacola', 'href' => url('sacola.php')]);
    } else {
        aviso_definir($erro, 'erro');
    }
    redirecionar($voltar);
}

http_response_code(400);
exit('Ação inválida.');
