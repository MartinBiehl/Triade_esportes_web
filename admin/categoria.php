<?php
/**
 * Painel da loja · Cadastrar ou editar categoria (dentro de um esporte)
 */
require __DIR__ . '/../includes/admin.php';

exigir_admin();

$id = (int) ($_GET['id'] ?? 0);
$categoria = $id
    ? consulta('SELECT c.*, (SELECT COUNT(*) FROM produtos WHERE categoria_id = c.id) AS total_produtos FROM categorias c WHERE c.id = ?', [$id])->fetch()
    : null;
if ($id && !$categoria) {
    aviso_definir('Categoria não encontrada', 'erro');
    redirecionar('admin/catalogo.php');
}

$esportes = consulta('SELECT id, nome FROM esportes ORDER BY ordem, nome')->fetchAll(PDO::FETCH_KEY_PAIR);
$esporte_inicial = (int) ($_GET['esporte'] ?? 0);
$dados = $categoria ?: [
    'esporte_id' => isset($esportes[$esporte_inicial]) ? $esporte_inicial : '', 'nome' => '', 'ativo' => 1,
    'ordem' => (int) consulta('SELECT COALESCE(MAX(ordem), 0) + 1 FROM categorias WHERE esporte_id = ?', [$esporte_inicial])->fetchColumn(),
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    if (($_POST['acao'] ?? '') === 'excluir' && $categoria) {
        $erro = excluir_categoria($id);
        aviso_definir($erro ?? 'Categoria excluída', $erro ? 'erro' : 'sucesso');
        redirecionar($erro ? 'admin/categoria.php?id=' . $id : 'admin/catalogo.php');
    }
    [$dados, $erros] = validar_categoria($_POST);
    if (!$erros) {
        salvar_categoria($dados, $categoria ? $id : null);
        aviso_definir($categoria ? 'Categoria salva' : 'Categoria cadastrada');
        redirecionar('admin/catalogo.php');
    }
}

$titulo = $categoria ? 'Editar categoria' : 'Nova categoria';
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
    <?= campo_selecao('esporte_id', 'Esporte', ['' => 'Escolha…'] + $esportes, $dados['esporte_id'], $erros['esporte_id'] ?? null, ['required' => true, 'data-obrigatorio' => 'Escolha o esporte.']) ?>
    <?= campo_texto('nome', 'Nome', $dados['nome'], $erros['nome'] ?? null, ['required' => true, 'maxlength' => 60, 'placeholder' => 'Ex.: Joelheiras', 'data-obrigatorio' => 'Digite o nome da categoria.']) ?>
    <?= campo_texto('ordem', 'Posição', (string) $dados['ordem'], null, ['inputmode' => 'numeric', 'maxlength' => 3], 'Menor aparece antes') ?>
    <?= campo_interruptor('ativo', 'Aparece na loja', (bool) $dados['ativo'], 'Desligada, a categoria e os produtos dela somem do site.') ?>
    <button type="submit" class="botao botao--primary botao--bloco"><?= $categoria ? 'Salvar alterações' : 'Cadastrar categoria' ?></button>
  </form>

  <?php if ($categoria): ?>
    <a class="botao botao--glass botao--bloco" href="<?= e(url('admin/produtos.php?categoria=' . $id)) ?>">Ver os <?= (int) $categoria['total_produtos'] ?> produtos desta categoria</a>
    <?php if ((int) $categoria['total_produtos'] === 0): ?>
      <form method="post" data-confirmar="Excluir a categoria <?= e($categoria['nome']) ?>?">
        <?= csrf_campo() ?>
        <input type="hidden" name="acao" value="excluir">
        <button type="submit" class="botao botao--ghost botao--bloco texto-erro"><?= icone('lixeira', 'icone--sm') ?> Excluir categoria</button>
      </form>
    <?php else: ?>
      <p class="t-footnote texto-secundario" style="text-align: center">Para excluir, a categoria precisa estar vazia. Para tirá-la do site, desligue "Aparece na loja".</p>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php pagina_fim(['scripts' => ['validacao.js', 'admin.js']]); ?>
