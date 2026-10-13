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
