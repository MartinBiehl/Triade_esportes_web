<?php
/**
 * Liga ou desliga um favorito. Recebe POST do formulário (sem JavaScript) ou do fetch (com JavaScript).
 *   produto_id   produto
 *   voltar       página para voltar depois (sem JavaScript)
 * Sem login: o fetch recebe 401 com o endereço da tela Entrar; o formulário é levado direto para lá.
 */
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_validar();

$produto_id = (int) ($_POST['produto_id'] ?? 0);
$voltar = destino_seguro($_POST['voltar'] ?? null, 'produto.php?id=' . $produto_id);
$cliente = cliente_logado();

if (!$cliente) {
    $entrar = url('conta/entrar.php?voltar=' . urlencode($voltar));
    if (pedido_ajax()) {
        responder_json(['ok' => false, 'mensagem' => 'Entre na sua conta para salvar favoritos', 'entrar' => $entrar], 401);
    }
    aviso_definir('Entre na sua conta para salvar favoritos', 'erro');
    header('Location: ' . $entrar);
    exit;
}

if (!consulta('SELECT 1 FROM produtos WHERE id = ? AND ativo = 1', [$produto_id])->fetchColumn()) {
    pedido_ajax() ? responder_json(['ok' => false, 'mensagem' => 'Produto não encontrado'], 404) : redirecionar($voltar);
}

$favorito = alternar_favorito((int) $cliente['id'], $produto_id);
$mensagem = $favorito ? 'Salvo nos favoritos' : 'Removido dos favoritos';

if (pedido_ajax()) {
    responder_json(['ok' => true, 'favorito' => $favorito, 'mensagem' => $mensagem]);
}
aviso_definir($mensagem, 'sucesso', $favorito ? ['rotulo' => 'Ver', 'href' => url('conta/favoritos.php')] : null);
redirecionar($voltar);
