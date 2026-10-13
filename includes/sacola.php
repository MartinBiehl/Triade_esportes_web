<?php
/**
 * Sacola de compras, guardada na sessão do PHP.
 * Funciona sem conta: só vai para o banco quando o pedido é fechado.
 *
 * Formato: $_SESSION['sacola']['12|M'] = ['produto_id' => 12, 'tamanho' => 'M', 'quantidade' => 1]
 */

const SACOLA_MAX_POR_ITEM = 10;

function sacola_chave(int $produto_id, string $tamanho): string
{
    return $produto_id . '|' . $tamanho;
}

/** Estoque de um tamanho, ou null se o produto/tamanho não existe ou está inativo. */
function estoque_disponivel(int $produto_id, string $tamanho): ?int
{
    $estoque = consulta(
        'SELECT t.estoque FROM produto_tamanhos t
           JOIN produtos p ON p.id = t.produto_id AND p.ativo = 1
          WHERE t.produto_id = ? AND t.tamanho = ?',
        [$produto_id, $tamanho]
    )->fetchColumn();
    return $estoque === false ? null : (int) $estoque;
}

/**
 * Adiciona à sacola. Devolve null se deu certo ou a mensagem de erro para o cliente.
 */
function sacola_adicionar(int $produto_id, string $tamanho, int $quantidade = 1): ?string
{
    $estoque = estoque_disponivel($produto_id, $tamanho);
    if ($estoque === null) {
        return 'Escolha um tamanho.';
    }
    if ($estoque === 0) {
        return 'Esse tamanho esgotou.';
    }

    $chave = sacola_chave($produto_id, $tamanho);
    $atual = $_SESSION['sacola'][$chave]['quantidade'] ?? 0;
    $nova  = min($atual + max(1, $quantidade), $estoque, SACOLA_MAX_POR_ITEM);

    if ($nova === $atual) {
        return 'Você já tem a quantidade máxima disponível desse item na sacola.';
    }

    $_SESSION['sacola'][$chave] = [
        'produto_id' => $produto_id,
        'tamanho'    => $tamanho,
        'quantidade' => $nova,
    ];
    return null;
}

/** Muda a quantidade de um item (0 remove). Respeita o estoque. */
function sacola_atualizar(string $chave, int $quantidade): ?string
{
    $item = $_SESSION['sacola'][$chave] ?? null;
    if (!$item) {
        return 'Esse item não está mais na sacola.';
    }
    if ($quantidade <= 0) {
        unset($_SESSION['sacola'][$chave]);
        return null;
    }
    $estoque = estoque_disponivel((int) $item['produto_id'], $item['tamanho']) ?? 0;
    $limite  = min($estoque, SACOLA_MAX_POR_ITEM);
    if ($quantidade > $limite) {
        $_SESSION['sacola'][$chave]['quantidade'] = max(1, $limite);
        return $limite <= 1 ? 'Só temos 1 unidade desse tamanho.' : "Só temos $limite unidades desse tamanho.";
    }
    $_SESSION['sacola'][$chave]['quantidade'] = $quantidade;
    return null;
}

/** Tira o item e devolve o que foi tirado (para o "Desfazer"). */
function sacola_remover(string $chave): ?array
{
    $item = $_SESSION['sacola'][$chave] ?? null;
    unset($_SESSION['sacola'][$chave]);
    return $item;
}

function sacola_esvaziar(): void
{
    $_SESSION['sacola'] = [];
}

/**
 * Itens da sacola com os dados atuais do produto (nome, preço, foto, estoque).
 * Produto que saiu do catálogo ou esgotou é tirado; quantidade acima do estoque é ajustada.
 * $avisos recebe as mensagens dessas correções para mostrar ao cliente.
 */
function sacola_itens(?array &$avisos = null): array
{
    $avisos = [];
    $sacola = $_SESSION['sacola'] ?? [];
    if (!$sacola) {
        return [];
    }

    $ids = array_unique(array_map(fn ($i) => (int) $i['produto_id'], $sacola));
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $produtos = [];
    foreach (consulta(SQL_PRODUTO_CARD . " WHERE p.ativo = 1 AND p.id IN ($marcas)", array_values($ids))->fetchAll() as $p) {
        $produtos[(int) $p['id']] = $p;
    }

    $itens = [];
    foreach ($sacola as $chave => $item) {
        $produto = $produtos[(int) $item['produto_id']] ?? null;
        $estoque = $produto ? (estoque_disponivel((int) $item['produto_id'], $item['tamanho']) ?? 0) : 0;

        if (!$produto || $estoque === 0) {
            $avisos[] = ($produto['nome'] ?? 'Um produto') . ' (' . $item['tamanho'] . ') esgotou e saiu da sacola.';
            unset($_SESSION['sacola'][$chave]);
            continue;
        }
        if ($item['quantidade'] > $estoque) {
            $avisos[] = 'Ajustamos ' . $produto['nome'] . ' para ' . $estoque . ' unidade(s), o que temos em estoque.';
            $item['quantidade'] = $_SESSION['sacola'][$chave]['quantidade'] = $estoque;
        }

        $preco = preco_atual($produto);
        $itens[] = [
            'chave'      => $chave,
            'produto'    => $produto,
            'tamanho'    => $item['tamanho'],
            'quantidade' => (int) $item['quantidade'],
            'estoque'    => min($estoque, SACOLA_MAX_POR_ITEM),
            'preco'      => $preco,
            'subtotal'   => round($preco * $item['quantidade'], 2),
        ];
    }
    return $itens;
}

/** Totais da sacola. O frete de verdade é escolhido no checkout. */
function sacola_resumo(array $itens): array
{
    $subtotal = round(array_sum(array_column($itens, 'subtotal')), 2);
    return [
        'subtotal'        => $subtotal,
        'quantidade'      => array_sum(array_column($itens, 'quantidade')),
        'frete_gratis'    => $subtotal >= FRETE_GRATIS_ACIMA,
        'falta_frete'     => max(0, round(FRETE_GRATIS_ACIMA - $subtotal, 2)),
        'total_pix'       => preco_pix($subtotal),
        'desconto_pix'    => round($subtotal - preco_pix($subtotal), 2),
    ];
}
