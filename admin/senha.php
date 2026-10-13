<?php
/**
 * Painel da loja · Trocar a senha do painel
 */
require __DIR__ . '/../includes/admin.php';

$admin = exigir_admin();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $erros = admin_trocar_senha((int) $admin['id'], (string) ($_POST['atual'] ?? ''), (string) ($_POST['senha'] ?? ''), (string) ($_POST['senha_confirmar'] ?? ''));
    if (!$erros) {
        aviso_definir('Senha do painel trocada');
        redirecionar('admin/');
    }
}

admin_pagina_inicio([
    'titulo' => 'Trocar senha',
    'aba'    => 'painel',
    'nav'    => ['titulo' => 'Trocar senha', 'voltar' => url('admin/')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">
  <form class="pilha" method="post" data-validar novalidate>
    <?= csrf_campo() ?>
    <?= campo_senha('atual', 'Senha atual', $erros['atual'] ?? null, ['autocomplete' => 'current-password', 'required' => true, 'data-obrigatorio' => 'Digite a senha atual.']) ?>
    <?= campo_senha('senha', 'Nova senha', $erros['senha'] ?? null, ['autocomplete' => 'new-password', 'required' => true, 'data-validar' => 'senha'], 'Pelo menos ' . SENHA_MINIMA . ' caracteres') ?>
    <?= campo_senha('senha_confirmar', 'Repita a nova senha', $erros['senha_confirmar'] ?? null, ['autocomplete' => 'new-password', 'required' => true, 'data-igual-a' => 'senha']) ?>
    <button type="submit" class="botao botao--primary botao--bloco">Trocar senha</button>
  </form>
</div>
<?php pagina_fim(['scripts' => ['validacao.js']]); ?>
