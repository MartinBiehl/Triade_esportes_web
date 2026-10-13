<?php
/**
 * Página provisória da etapa 0: confere se o PHP e o banco estão funcionando.
 * Na etapa 2 ela vira a tela Início.
 */
require __DIR__ . '/includes/bootstrap.php';

$erro = null;
$esportes = [];
$totais = [];

try {
    $esportes = consulta(
        'SELECT e.nome, COUNT(p.id) AS produtos
           FROM esportes e
           LEFT JOIN categorias c ON c.esporte_id = e.id
           LEFT JOIN produtos p   ON p.categoria_id = c.id AND p.ativo = 1
          WHERE e.ativo = 1
          GROUP BY e.id, e.nome, e.ordem
          ORDER BY e.ordem'
    )->fetchAll();

    foreach (['categorias', 'produtos', 'clientes', 'pedidos'] as $tabela) {
        $totais[$tabela] = (int) consulta("SELECT COUNT(*) FROM $tabela")->fetchColumn();
    }
} catch (PDOException $ex) {
    $erro = $config['modo_debug'] ? $ex->getMessage() : 'Não foi possível conectar ao banco.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(LOJA_NOME) ?> · instalação</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 480px; margin: 40px auto; padding: 0 16px; color: #07090D; background: #F8F8FA; }
    h1 { font-size: 28px; letter-spacing: -0.02em; }
    .ok { color: #1FB86A; } .erro { color: #F2394B; }
    li { margin: 4px 0; }
  </style>
</head>
<body>
  <h1><?= e(LOJA_NOME) ?></h1>
  <p>PHP <?= e(PHP_VERSION) ?> · endereço base: <code><?= e(url('')) ?></code></p>

  <?php if ($erro): ?>
    <p class="erro"><strong>Banco de dados não conectado.</strong></p>
    <p><?= e($erro) ?></p>
    <p>Confira se o MySQL está ligado no XAMPP e se os scripts da pasta <code>sql/</code> foram importados. O passo a passo está no README.</p>
  <?php else: ?>
    <p class="ok"><strong>Banco de dados conectado.</strong></p>
    <ul>
      <?php foreach ($esportes as $esporte): ?>
        <li><?= e($esporte['nome']) ?>: <?= (int) $esporte['produtos'] ?> produtos</li>
      <?php endforeach; ?>
    </ul>
    <p>
      <?= $totais['categorias'] ?> categorias · <?= $totais['produtos'] ?> produtos ·
      <?= $totais['clientes'] ?> cliente de teste · <?= $totais['pedidos'] ?> pedidos de exemplo
    </p>
  <?php endif; ?>
</body>
</html>
