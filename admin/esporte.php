<?php
/**
 * Painel da loja · Cadastrar ou editar esporte
 */
require __DIR__ . '/../includes/admin.php';

exigir_admin();

$id = (int) ($_GET['id'] ?? 0);
$esporte = $id ? consulta('SELECT * FROM esportes WHERE id = ?', [$id])->fetch() : null;
if ($id && !$esporte) {
    aviso_definir('Esporte não encontrado', 'erro');
    redirecionar('admin/catalogo.php');
}

$dados = $esporte ?: [
    'nome' => '', 'descricao' => '', 'cor' => 'blue', 'ativo' => 1,
    'ordem' => (int) consulta('SELECT COALESCE(MAX(ordem), 0) + 1 FROM esportes')->fetchColumn(),
];
$dados['descricao'] ??= '';
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    if (($_POST['acao'] ?? '') === 'excluir' && $esporte) {
        $erro = excluir_esporte($id);
        aviso_definir($erro ?? 'Esporte excluído', $erro ? 'erro' : 'sucesso');
        redirecionar($erro ? 'admin/esporte.php?id=' . $id : 'admin/catalogo.php');
    }
    [$dados, $erros] = validar_esporte($_POST);
    if (!$erros) {
        salvar_esporte($dados, $esporte ? $id : null);
        aviso_definir($esporte ? 'Esporte salvo' : 'Esporte cadastrado');
        redirecionar('admin/catalogo.php');
    }
}

$titulo = $esporte ? 'Editar esporte' : 'Novo esporte';
admin_pagina_inicio([
    'titulo' => $titulo,
    'aba'    => 'catalogo',
    'nav'    => ['titulo' => $titulo, 'voltar' => url('admin/catalogo.php')],
]);
?>
<div class="conteudo conteudo--leitura pilha-xl" style="padding-top: var(--spacing-lg)">
  <form class="pilha" method="post" data-validar novalidate>
    <?= csrf_campo() ?>
    <?= caixa_erros($erros) ?>
    <?= campo_texto('nome', 'Nome', $dados['nome'], $erros['nome'] ?? null, ['required' => true, 'maxlength' => 60, 'placeholder' => 'Ex.: Vôlei', 'data-obrigatorio' => 'Digite o nome do esporte.']) ?>
    <?= campo_texto('descricao', 'Frase curta', $dados['descricao'], null, ['maxlength' => 160, 'placeholder' => 'Ex.: Bolas, joelheiras e camisas'], 'Aparece no card do esporte') ?>
    <div class="campos-lado-a-lado">
      <?= campo_selecao('cor', 'Cor do card', CORES_ESPORTE, $dados['cor']) ?>
      <?= campo_texto('ordem', 'Posição', (string) $dados['ordem'], null, ['inputmode' => 'numeric', 'maxlength' => 3], 'Menor aparece antes') ?>
    </div>
    <?= campo_interruptor('ativo', 'Aparece na loja', (bool) $dados['ativo'], 'Desligado, o esporte e os produtos dele somem do site.') ?>
    <button type="submit" class="botao botao--primary botao--bloco"><?= $esporte ? 'Salvar alterações' : 'Cadastrar esporte' ?></button>
  </form>

  <?php if ($esporte): ?>
    <form method="post" data-confirmar="Excluir o esporte <?= e($esporte['nome']) ?>?">
      <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="excluir">
      <button type="submit" class="botao botao--ghost botao--bloco texto-erro"><?= icone('lixeira', 'icone--sm') ?> Excluir esporte</button>
    </form>
    <p class="t-footnote texto-secundario" style="text-align: center; margin-top: calc(var(--spacing-2xl) * -1)">Só dá para excluir um esporte sem categorias.</p>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['validacao.js', 'admin.js']]); ?>
