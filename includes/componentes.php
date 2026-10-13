<?php
/**
 * Componentes reaproveitados em várias telas.
 * Cada função devolve o HTML de um componente do Figma.
 */

// ---------------------------------------------------------------
// Ícones e silhuetas (assets/img/icones.svg)
// ---------------------------------------------------------------

/** Ícone de traço. Ex.: icone('sacola'), icone('fechar', 'icone--sm') */
function icone(string $nome, string $classe = ''): string
{
    return '<svg class="icone ' . e($classe) . '" aria-hidden="true" focusable="false">'
         . '<use href="' . e(asset('img/icones.svg')) . '#i-' . e($nome) . '"></use></svg>';
}

/** Silhueta provisória do produto (camisa, bola, chuteira, tenis, calcao, meiao, caneleira). */
function silhueta(string $tipo, string $classe = ''): string
{
    return '<svg class="' . e($classe) . '" viewBox="0 0 100 100" aria-hidden="true" focusable="false">'
         . '<use href="' . e(asset('img/icones.svg')) . '#s-' . e($tipo) . '"></use></svg>';
}

// ---------------------------------------------------------------
// Produto
// ---------------------------------------------------------------

/** Preço que vale agora (promocional, se houver). */
function preco_atual(array $produto): float
{
    return (float) ($produto['preco_promocional'] ?? $produto['preco']);
}

/**
 * Selos do produto, na ordem de importância.
 * O produto pode trazer 'estoque_total' (soma do estoque de todos os tamanhos).
 */
function selos_produto(array $produto): array
{
    $selos = [];
    if (isset($produto['estoque_total']) && (int) $produto['estoque_total'] === 0) {
        $selos[] = ['esgotado', 'Esgotado'];
    }
    if (!empty($produto['preco_promocional'])) {
        $selos[] = ['promo', 'Promo'];
    }
    if (!empty($produto['lancamento'])) {
        $selos[] = ['novo', 'Novo'];
    }
    if (!empty($produto['oficial'])) {
        $selos[] = ['oficial', 'Oficial'];
    }
    if (preco_atual($produto) >= FRETE_GRATIS_ACIMA) {
        $selos[] = ['frete', 'Frete grátis'];
    }
    return $selos;
}

function selo(string $tipo, string $texto): string
{
    return '<span class="selo selo--' . e($tipo) . '">' . e($texto) . '</span>';
}

/** Foto do produto (proporção 4:5) ou a silhueta enquanto não há foto. */
function midia_produto(array $produto, string $classe = ''): string
{
    $html = '<div class="midia-produto ' . e($classe) . '">';
    if (!empty($produto['imagem'])) {
        $html .= '<img src="' . e(url('assets/img/produtos/' . $produto['imagem'])) . '" alt="" loading="lazy">';
    } else {
        $html .= silhueta($produto['midia'] ?? 'camisa', 'midia-produto__silhueta');
    }
    return $html . '</div>';
}

/** Card de produto (Product Card): foto, até 2 selos, nome, preço, parcelas e preço no Pix. */
function card_produto(array $produto): string
{
    $preco = preco_atual($produto);
    $link  = url('produto.php?id=' . (int) $produto['id']);

    $selos = '';
    foreach (array_slice(selos_produto($produto), 0, 2) as [$tipo, $texto]) {
        $selos .= selo($tipo, $texto);
    }

    $preco_antigo = !empty($produto['preco_promocional'])
        ? '<span class="card-produto__preco-antigo">' . dinheiro((float) $produto['preco']) . '</span>'
        : '';

    return '<a class="card-produto" href="' . e($link) . '">'
         . ($selos ? '<div class="card-produto__selos">' . $selos . '</div>' : '')
         . midia_produto($produto)
         . '<div class="card-produto__info">'
         .   '<h3 class="card-produto__nome">' . e($produto['nome']) . '</h3>'
         .   '<div class="card-produto__preco"><span class="t-headline">' . dinheiro($preco) . '</span>' . $preco_antigo . '</div>'
         .   '<span class="card-produto__parcelas">' . e(texto_parcelas($preco)) . '</span>'
         .   '<span class="card-produto__pix">' . dinheiro(preco_pix($preco)) . ' no Pix</span>'
         . '</div>'
         . '</a>';
}

// ---------------------------------------------------------------
// Estrutura de tela
// ---------------------------------------------------------------

/** Título grande das telas raiz (Nav Bar · Large Title). */
function nav_large(string $titulo, string $acoes = ''): string
{
    return '<header class="nav-large">'
         . '<h1 class="nav-large__titulo">' . e($titulo) . '</h1>'
         . ($acoes ? '<div class="nav-large__acoes">' . $acoes . '</div>' : '')
         . '</header>';
}

/** Título de seção com link "Ver tudo" (Section Header). */
function cabecalho_secao(string $titulo, ?string $link = null, string $texto_link = 'Ver tudo'): string
{
    return '<div class="cabecalho-secao">'
         . '<h2 class="cabecalho-secao__titulo">' . e($titulo) . '</h2>'
         . ($link ? '<a class="cabecalho-secao__link" href="' . e($link) . '">' . e($texto_link) . '</a>' : '')
         . '</div>';
}

/**
 * Linha de lista (List Row). Se tiver $link vira um link com seta.
 * Ex.: linha_lista('Meus pedidos', 'pacote', url('conta/pedidos.php'))
 */
function linha_lista(string $rotulo, ?string $icone = null, ?string $link = null, ?string $valor = null): string
{
    $tag   = $link ? 'a' : 'div';
    $href  = $link ? ' href="' . e($link) . '"' : '';
    return '<' . $tag . ' class="linha-lista"' . $href . '>'
         . ($icone ? icone($icone, 'linha-lista__icone') : '')
         . '<span class="linha-lista__rotulo">' . e($rotulo) . '</span>'
         . ($valor !== null ? '<span class="linha-lista__valor">' . e($valor) . '</span>' : '')
         . ($link ? icone('seta-direita', 'icone--sm linha-lista__seta') : '')
         . '</' . $tag . '>';
}

/** Abre um painel (Sheet). Feche com painel_fim(). */
function painel_inicio(string $id, string $titulo): string
{
    return '<div class="painel" id="' . e($id) . '" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="' . e($id) . '-titulo">'
         . '<div class="painel__fundo" data-fechar-painel></div>'
         . '<div class="painel__caixa vidro-thick">'
         . '<div class="painel__puxador" aria-hidden="true"></div>'
         . '<div class="painel__cabecalho">'
         .   '<h2 class="painel__titulo" id="' . e($id) . '-titulo">' . e($titulo) . '</h2>'
         .   '<button type="button" class="botao-icone botao-icone--sm botao-icone--glass" data-fechar-painel aria-label="Fechar">' . icone('fechar') . '</button>'
         . '</div>'
         . '<div class="painel__corpo">';
}

/** Fecha o painel. $rodape é o HTML dos botões fixos no fim (opcional). */
function painel_fim(string $rodape = ''): string
{
    return '</div>'
         . ($rodape ? '<div class="painel__rodape">' . $rodape . '</div>' : '')
         . '</div></div>';
}
