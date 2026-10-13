<?php
/**
 * Opções de entrega e frete por região do CEP.
 *
 * SIMULAÇÃO: não há integração com os Correios. O RS foi dividido em 4 regiões pelas
 * faixas de CEP; quanto mais longe de São Leopoldo, mais caro e mais demorado.
 * Os valores são inventados, mas seguem a lógica de distância.
 */

const OPCOES_ENTREGA = [
    'pac'            => ['nome' => 'PAC',                     'descricao' => 'Correios'],
    'sedex'          => ['nome' => 'SEDEX',                   'descricao' => 'Correios, mais rápido'],
    'transportadora' => ['nome' => 'Transportadora',          'descricao' => 'Entrega na porta'],
    'retirada'       => ['nome' => 'Retirar em São Leopoldo', 'descricao' => 'No nosso depósito'],
];

/** Retirada no depósito: grátis e pronta em 1 dia útil, para qualquer CEP. */
const RETIRADA = ['preco' => 0.00, 'prazo' => [1, 1]];

/**
 * Regiões do RS pela faixa de CEP (5 primeiros números).
 * preços em R$ · prazos em dias úteis [mínimo, máximo]
 */
const REGIOES_FRETE = [
    'metropolitana' => [
        'nome'   => 'Grande Porto Alegre',
        'faixa'  => [90000, 94999],
        'precos' => ['pac' => 14.90, 'sedex' => 24.90, 'transportadora' => 19.90],
        'prazos' => ['pac' => [2, 4], 'sedex' => [1, 2], 'transportadora' => [2, 3]],
    ],
    'serra' => [
        'nome'   => 'Serra, Litoral Norte e Vales',
        'faixa'  => [95000, 95999],
        'precos' => ['pac' => 22.90, 'sedex' => 34.90, 'transportadora' => 27.90],
        'prazos' => ['pac' => [4, 6], 'sedex' => [2, 3], 'transportadora' => [3, 5]],
    ],
    'sul-centro' => [
        'nome'   => 'Sul e Centro do estado',
        'faixa'  => [96000, 97999],
        'precos' => ['pac' => 27.90, 'sedex' => 42.90, 'transportadora' => 32.90],
        'prazos' => ['pac' => [5, 8], 'sedex' => [2, 4], 'transportadora' => [4, 7]],
    ],
    'norte' => [
        'nome'   => 'Norte, Missões e Fronteira',
        'faixa'  => [98000, 99999],
        'precos' => ['pac' => 31.90, 'sedex' => 49.90, 'transportadora' => 36.90],
        'prazos' => ['pac' => [6, 9], 'sedex' => [3, 5], 'transportadora' => [5, 8]],
    ],
];

/** Região do CEP (com a chave em 'id'), ou null se não for um CEP do RS. */
function regiao_do_cep(string $cep): ?array
{
    $n = so_numeros($cep);
    if (strlen($n) !== 8) {
        return null;
    }
    $prefixo = (int) substr($n, 0, 5);
    foreach (REGIOES_FRETE as $id => $regiao) {
        if ($prefixo >= $regiao['faixa'][0] && $prefixo <= $regiao['faixa'][1]) {
            return $regiao + ['id' => $id];
        }
    }
    return null;
}

/** Preço e prazo de uma forma de entrega para o CEP. Sem região conhecida, usa a mais distante. */
function frete_da_regiao(string $tipo, string $cep): array
{
    if ($tipo === 'retirada') {
        return RETIRADA;
    }
    $regiao = regiao_do_cep($cep) ?? REGIOES_FRETE['norte'];
    return ['preco' => $regiao['precos'][$tipo], 'prazo' => $regiao['prazos'][$tipo]];
}

/** Soma dias úteis (pula sábado e domingo; feriados não são considerados). */
function somar_dias_uteis(DateTimeImmutable $data, int $dias): DateTimeImmutable
{
    while ($dias > 0) {
        $data = $data->modify('+1 day');
        if ((int) $data->format('N') < 6) {
            $dias--;
        }
    }
    return $data;
}

/** "20 de out." */
function data_curta(DateTimeInterface $data): string
{
    $meses = ['jan.', 'fev.', 'mar.', 'abr.', 'maio', 'jun.', 'jul.', 'ago.', 'set.', 'out.', 'nov.', 'dez.'];
    return $data->format('j') . ' de ' . $meses[(int) $data->format('n') - 1];
}

/** Texto do prazo: "Entre 20 e 23 de out." ou "Pronto em 1 dia útil". */
function texto_prazo(string $tipo, string $cep = '', ?DateTimeImmutable $hoje = null): string
{
    $hoje ??= new DateTimeImmutable('today');
    [$min, $max] = frete_da_regiao($tipo, $cep)['prazo'];
    if ($tipo === 'retirada') {
        return 'Pronto em ' . $min . ' dia útil, a partir de ' . data_curta(somar_dias_uteis($hoje, $min));
    }
    $de  = somar_dias_uteis($hoje, $min);
    $ate = somar_dias_uteis($hoje, $max);
    if ($de->format('n') === $ate->format('n')) {
        return 'Entre ' . $de->format('j') . ' e ' . data_curta($ate);
    }
    return 'Entre ' . data_curta($de) . ' e ' . data_curta($ate);
}

/**
 * Opções de entrega para um CEP e valor de compra, com preço já considerando o frete grátis
 * (PAC grátis acima de FRETE_GRATIS_ACIMA, em todo o RS).
 */
function opcoes_entrega(string $cep, float $subtotal): array
{
    $opcoes = [];
    foreach (OPCOES_ENTREGA as $tipo => $opcao) {
        $frete = frete_da_regiao($tipo, $cep);
        $preco = $frete['preco'];
        if ($tipo === 'pac' && $subtotal >= FRETE_GRATIS_ACIMA) {
            $preco = 0.0;
        }
        $opcoes[$tipo] = $opcao + $frete + ['tipo' => $tipo, 'preco_final' => $preco, 'prazo_texto' => texto_prazo($tipo, $cep)];
    }
    return $opcoes;
}

/** Último CEP que o cliente calculou (fica na sessão para não digitar de novo). */
function cep_lembrado(): string
{
    return (string) ($_SESSION['cep'] ?? '');
}

function lembrar_cep(string $cep): void
{
    $_SESSION['cep'] = so_numeros($cep);
}

/**
 * Resultado do "Calcular frete" da página do produto.
 * Devolve [html, erro]. CEP de fora do RS mostra aviso em vez de preço.
 */
function frete_cep_html(string $cep, float $subtotal): array
{
    $n = so_numeros($cep);
    if (strlen($n) !== 8) {
        return ['', 'Digite o CEP com 8 números.'];
    }
    $regiao = regiao_do_cep($n);
    if (!$regiao) {
        return ['', 'Por enquanto entregamos só no Rio Grande do Sul. Seu CEP começa com ' . substr($n, 0, 2) . '.'];
    }
    lembrar_cep($n);

    $html = '<p class="t-footnote texto-secundario">Para ' . e(formatar_cep($n)) . ' · ' . e($regiao['nome']) . '</p><ul class="lista" role="list">';
    foreach (opcoes_entrega($n, $subtotal) as $tipo => $o) {
        $gratis = $o['preco_final'] == 0;
        $html .= '<li><div class="linha-lista">'
               . icone($tipo === 'retirada' ? 'loja' : 'caminhao', 'linha-lista__icone')
               . '<span class="linha-lista__rotulo">' . e($o['nome']) . '<br><span class="t-footnote texto-secundario">' . e($o['prazo_texto']) . '</span></span>'
               . '<span class="linha-lista__valor' . ($gratis ? ' texto-sucesso' : '') . '">' . ($gratis ? 'Grátis' : dinheiro($o['preco_final'])) . '</span>'
               . '</div></li>';
    }
    $html .= '</ul>';
    if ($subtotal < FRETE_GRATIS_ACIMA) {
        $html .= '<p class="t-footnote texto-secundario">PAC grátis em compras acima de ' . dinheiro(FRETE_GRATIS_ACIMA) . '.</p>';
    }
    return [$html, null];
}
