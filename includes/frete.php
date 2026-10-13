<?php
/**
 * Opções de entrega.
 *
 * SIMULAÇÃO: os valores abaixo são fixos. Na etapa 8 do plano o preço e o prazo passam
 * a variar conforme a região do CEP dentro do RS. Não há integração real com os Correios.
 */

const OPCOES_ENTREGA = [
    'pac' => [
        'nome'      => 'PAC',
        'descricao' => 'Correios',
        'preco'     => 24.90,
        'prazo'     => [6, 9],   // dias úteis (mínimo, máximo)
    ],
    'sedex' => [
        'nome'      => 'SEDEX',
        'descricao' => 'Correios, mais rápido',
        'preco'     => 39.90,
        'prazo'     => [2, 3],
    ],
    'transportadora' => [
        'nome'      => 'Transportadora',
        'descricao' => 'Entrega na porta',
        'preco'     => 29.90,
        'prazo'     => [4, 6],
    ],
    'retirada' => [
        'nome'      => 'Retirar em São Leopoldo',
        'descricao' => 'No nosso depósito',
        'preco'     => 0.00,
        'prazo'     => [1, 1],
    ],
];

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
function texto_prazo(string $tipo, ?DateTimeImmutable $hoje = null): string
{
    $hoje ??= new DateTimeImmutable('today');
    [$min, $max] = OPCOES_ENTREGA[$tipo]['prazo'];
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
 * Opções de entrega para um CEP e valor de compra, com preço já considerando o frete grátis.
 * $cep fica sem uso até a etapa 8 (simulação por região).
 */
function opcoes_entrega(string $cep, float $subtotal): array
{
    $opcoes = [];
    foreach (OPCOES_ENTREGA as $tipo => $opcao) {
        $preco = $opcao['preco'];
        if ($tipo === 'pac' && $subtotal >= FRETE_GRATIS_ACIMA) {
            $preco = 0.0;
        }
        $opcoes[$tipo] = $opcao + ['tipo' => $tipo, 'preco_final' => $preco, 'prazo_texto' => texto_prazo($tipo)];
    }
    return $opcoes;
}
