<?php
/**
 * Checkout: guarda as escolhas de cada etapa na sessão e cria o pedido no fim.
 *
 * Etapas: identificacao (12) > endereco (13) > entrega (14) > pagamento (15) > revisao (16)
 *
 * SIMULAÇÃO DE PAGAMENTO: nenhum dado de cartão é guardado (nem na sessão, nem no banco).
 * Só ficam a bandeira e os 4 últimos dígitos, para mostrar na revisão.
 * Cartão terminado em 0002 é recusado, para mostrar a tela 30 · Pagamento recusado.
 */

const ETAPAS_CHECKOUT = [
    'endereco'  => 'Endereço',
    'entrega'   => 'Entrega',
    'pagamento' => 'Pagamento',
    'revisao'   => 'Revisão',
];

const UFS_ENTREGA = ['RS' => 'Rio Grande do Sul'];

function &checkout(): array
{
    if (!isset($_SESSION['checkout']) || !is_array($_SESSION['checkout'])) {
        $_SESSION['checkout'] = [];
    }
    return $_SESSION['checkout'];
}

/**
 * Garante que a sacola tem itens e que as etapas anteriores foram feitas.
 * Se faltar algo, manda o cliente para a etapa certa.
 */
function checkout_exigir(string $etapa): void
{
    if (!sacola_itens()) {
        redirecionar('sacola.php');
    }
    $c = checkout();
    $ordem = ['identificacao', 'endereco', 'entrega', 'pagamento', 'revisao'];
    foreach ($ordem as $anterior) {
        if ($anterior === $etapa) {
            return;
        }
        if (empty($c[$anterior])) {
            redirecionar('checkout/' . $anterior . '.php');
        }
    }
}

/** Indicador de etapas (Checkout Steps). */
function checkout_etapas(string $atual): string
{
    $html = '<ol class="etapas" aria-label="Etapas da compra">';
    $passou = true;
    foreach (ETAPAS_CHECKOUT as $chave => $rotulo) {
        if ($chave === $atual) {
            $html .= '<li aria-current="step">' . e($rotulo) . '</li>';
            $passou = false;
        } else {
            $html .= '<li' . ($passou ? ' data-estado="feito"' : '') . '>' . e($rotulo) . '</li>';
        }
    }
    return $html . '</ol>';
}

// ---------------------------------------------------------------
// Validação dos formulários
// ---------------------------------------------------------------

function formatar_cpf(string $cpf): string
{
    $n = so_numeros($cpf);
    return strlen($n) === 11 ? substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . substr($n, 9) : $cpf;
}

function formatar_cep(string $cep): string
{
    $n = so_numeros($cep);
    return strlen($n) === 8 ? substr($n, 0, 5) . '-' . substr($n, 5) : $cep;
}

function formatar_telefone(string $telefone): string
{
    $n = so_numeros($telefone);
    if (strlen($n) === 11) {
        return '(' . substr($n, 0, 2) . ') ' . substr($n, 2, 5) . '-' . substr($n, 7);
    }
    if (strlen($n) === 10) {
        return '(' . substr($n, 0, 2) . ') ' . substr($n, 2, 4) . '-' . substr($n, 6);
    }
    return $telefone;
}

/** CEP do Rio Grande do Sul: 90000-000 a 99999-999. */
function cep_do_rs(string $cep): bool
{
    $n = so_numeros($cep);
    return strlen($n) === 8 && $n[0] === '9';
}

/** Devolve [dados limpos, erros por campo]. */
function validar_identificacao(array $post): array
{
    $d = [
        'nome'     => trim((string) ($post['nome'] ?? '')),
        'email'    => mb_strtolower(trim((string) ($post['email'] ?? ''))),
        'cpf'      => so_numeros($post['cpf'] ?? ''),
        'telefone' => so_numeros($post['telefone'] ?? ''),
    ];
    $erros = [];
    if (mb_strlen($d['nome']) < 5 || !str_contains($d['nome'], ' ')) {
        $erros['nome'] = 'Digite nome e sobrenome.';
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'E-mail inválido.';
    }
    if (!cpf_valido($d['cpf'])) {
        $erros['cpf'] = 'CPF inválido.';
    }
    if (strlen($d['telefone']) < 10 || strlen($d['telefone']) > 11) {
        $erros['telefone'] = 'Telefone com DDD, só números.';
    }
    return [$d, $erros];
}

function validar_endereco(array $post): array
{
    $d = [
        'destinatario' => trim((string) ($post['destinatario'] ?? '')),
        'cep'          => so_numeros($post['cep'] ?? ''),
        'logradouro'   => trim((string) ($post['logradouro'] ?? '')),
        'numero'       => trim((string) ($post['numero'] ?? '')),
        'complemento'  => trim((string) ($post['complemento'] ?? '')),
        'bairro'       => trim((string) ($post['bairro'] ?? '')),
        'cidade'       => trim((string) ($post['cidade'] ?? '')),
        'uf'           => 'RS',
    ];
    $erros = [];
    if (mb_strlen($d['destinatario']) < 3) {
        $erros['destinatario'] = 'Quem vai receber?';
    }
    if (strlen($d['cep']) !== 8) {
        $erros['cep'] = 'CEP com 8 números.';
    } elseif (!cep_do_rs($d['cep'])) {
        $erros['cep'] = 'Por enquanto entregamos só no Rio Grande do Sul.';
    }
    foreach (['logradouro' => 'Digite a rua.', 'numero' => 'Digite o número (ou "s/n").', 'bairro' => 'Digite o bairro.', 'cidade' => 'Digite a cidade.'] as $campo => $mensagem) {
        if ($d[$campo] === '') {
            $erros[$campo] = $mensagem;
        }
    }
    foreach (['destinatario' => 120, 'logradouro' => 160, 'numero' => 20, 'complemento' => 80, 'bairro' => 80, 'cidade' => 80] as $campo => $max) {
        if (mb_strlen($d[$campo]) > $max) {
            $erros[$campo] = 'Texto muito longo.';
        }
    }
    return [$d, $erros];
}

/** Algoritmo de Luhn: confere se o número do cartão é possível. */
function cartao_valido(string $numero): bool
{
    $n = so_numeros($numero);
    if (strlen($n) < 13 || strlen($n) > 19) {
        return false;
    }
    $soma = 0;
    $dobrar = false;
    for ($i = strlen($n) - 1; $i >= 0; $i--) {
        $d = (int) $n[$i];
        if ($dobrar) {
            $d *= 2;
            if ($d > 9) {
                $d -= 9;
            }
        }
        $soma += $d;
        $dobrar = !$dobrar;
    }
    return $soma % 10 === 0;
}

function bandeira_cartao(string $numero): string
{
    $n = so_numeros($numero);
    return match (true) {
        str_starts_with($n, '4')                         => 'Visa',
        (bool) preg_match('/^(5[1-5]|2[2-7])/', $n)      => 'Mastercard',
        (bool) preg_match('/^3[47]/', $n)                => 'American Express',
        (bool) preg_match('/^(4011|4312|4389|4514|4576|5041|5066|5067|509|6277|6362|6363|650|6516|6550)/', $n) => 'Elo',
        (bool) preg_match('/^(606282|3841)/', $n)        => 'Hipercard',
        default                                          => 'Cartão',
    };
}

/**
 * Valida a escolha de pagamento. Para cartão, confere os dados mas só guarda bandeira e final.
 */
function validar_pagamento(array $post, float $subtotal_com_frete): array
{
    $tipo = $post['pagamento'] ?? '';
    $erros = [];
    $d = ['tipo' => $tipo, 'parcelas' => 1];

    if (!in_array($tipo, ['pix', 'cartao', 'boleto'], true)) {
        return [$d, ['pagamento' => 'Escolha como pagar.']];
    }

    if ($tipo === 'cartao') {
        $numero   = so_numeros($post['cartao_numero'] ?? '');
        $nome     = trim((string) ($post['cartao_nome'] ?? ''));
        $validade = so_numeros($post['cartao_validade'] ?? '');
        $cvv      = so_numeros($post['cartao_cvv'] ?? '');
        $parcelas = (int) ($post['parcelas'] ?? 1);

        if (!cartao_valido($numero)) {
            $erros['cartao_numero'] = 'Número do cartão inválido.';
        }
        if (mb_strlen($nome) < 3) {
            $erros['cartao_nome'] = 'Nome como está no cartão.';
        }
        if (strlen($validade) !== 4) {
            $erros['cartao_validade'] = 'Use MM/AA.';
        } else {
            $mes = (int) substr($validade, 0, 2);
            $ano = 2000 + (int) substr($validade, 2, 2);
            $fim = (new DateTimeImmutable("$ano-" . max(1, min(12, $mes)) . '-01'))->modify('last day of this month');
            if ($mes < 1 || $mes > 12 || $fim < new DateTimeImmutable('today')) {
                $erros['cartao_validade'] = 'Cartão vencido ou data inválida.';
            }
        }
        if (strlen($cvv) < 3 || strlen($cvv) > 4) {
            $erros['cartao_cvv'] = '3 ou 4 números no verso.';
        }
        if ($parcelas < 1 || $parcelas > max_parcelas($subtotal_com_frete)) {
            $erros['parcelas'] = 'Escolha as parcelas.';
        }

        $d += [
            'parcelas' => $parcelas,
            'bandeira' => bandeira_cartao($numero),
            'final'    => substr($numero, -4),
            'nome'     => $nome,
        ];
        $d['parcelas'] = $parcelas;
    }

    return [$d, $erros];
}

// ---------------------------------------------------------------
// Totais
// ---------------------------------------------------------------

/**
 * Totais do pedido. O desconto do Pix vale sobre os produtos (não sobre o frete).
 */
function checkout_totais(array $itens, ?string $entrega, ?string $pagamento): array
{
    $subtotal = round(array_sum(array_column($itens, 'subtotal')), 2);
    $frete = 0.0;
    if ($entrega) {
        $opcoes = opcoes_entrega(checkout()['endereco']['cep'] ?? '', $subtotal);
        $frete = $opcoes[$entrega]['preco_final'] ?? 0.0;
    }
    $desconto = $pagamento === 'pix' ? round($subtotal * PIX_DESCONTO, 2) : 0.0;
    $total = round($subtotal + $frete - $desconto, 2);

    return [
        'subtotal'  => $subtotal,
        'frete'     => $frete,
        'desconto'  => $desconto,
        'total'     => $total,
        'total_pix' => round($subtotal - round($subtotal * PIX_DESCONTO, 2) + $frete, 2),
        'total_cartao' => round($subtotal + $frete, 2),
    ];
}

// ---------------------------------------------------------------
// Criação do pedido
// ---------------------------------------------------------------

function gerar_numero_pedido(): string
{
    do {
        $numero = 'TR-' . random_int(20000, 99999);
        $existe = consulta('SELECT 1 FROM pedidos WHERE numero = ?', [$numero])->fetchColumn();
    } while ($existe);
    return $numero;
}

/**
 * Cria o pedido no banco a partir da sacola e do checkout.
 * Tudo dentro de uma transação: se faltar estoque de algum item, nada é gravado.
 * Devolve [numero do pedido, null] ou [null, mensagem de erro].
 */
function criar_pedido(?int $cliente_id = null): array
{
    $c = checkout();
    $itens = sacola_itens();
    if (!$itens) {
        return [null, 'Sua sacola está vazia.'];
    }

    $entrega   = $c['entrega'];
    $pagamento = $c['pagamento'];
    $totais    = checkout_totais($itens, $entrega, $pagamento['tipo']);
    $endereco  = $entrega === 'retirada' ? null : $c['endereco'];
    $pix       = $pagamento['tipo'] === 'pix';
    $boleto    = $pagamento['tipo'] === 'boleto';

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // trava as linhas de estoque até o fim da transação (evita vender a mesma última peça duas vezes)
        foreach ($itens as $item) {
            $estoque = consulta(
                'SELECT estoque FROM produto_tamanhos WHERE produto_id = ? AND tamanho = ? FOR UPDATE',
                [$item['produto']['id'], $item['tamanho']]
            )->fetchColumn();
            if ($estoque === false || (int) $estoque < $item['quantidade']) {
                $pdo->rollBack();
                return [null, $item['produto']['nome'] . ' (' . $item['tamanho'] . ') não tem mais a quantidade escolhida. Confira a sacola.'];
            }
        }

        $numero = gerar_numero_pedido();
        $status = ($pix || $boleto) ? 'aguardando_pagamento' : 'pago';

        consulta(
            'INSERT INTO pedidos
               (numero, cliente_id, nome, email, cpf, telefone, cep, logradouro, numero_end, complemento,
                bairro, cidade, uf, entrega_tipo, entrega_prazo, pagamento_tipo, parcelas,
                subtotal, frete, desconto, total, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $numero, $cliente_id,
                $c['identificacao']['nome'], $c['identificacao']['email'], $c['identificacao']['cpf'], formatar_telefone($c['identificacao']['telefone']),
                $endereco['cep'] ?? null, $endereco['logradouro'] ?? null, $endereco['numero'] ?? null,
                ($endereco['complemento'] ?? '') !== '' ? $endereco['complemento'] : null,
                $endereco['bairro'] ?? null, $endereco['cidade'] ?? null, $endereco['uf'] ?? null,
                $entrega, texto_prazo($entrega, $endereco['cep'] ?? ''), $pagamento['tipo'], $pagamento['parcelas'] ?? 1,
                $totais['subtotal'], $totais['frete'], $totais['desconto'], $totais['total'], $status,
            ]
        );
        $pedido_id = (int) $pdo->lastInsertId();

        foreach ($itens as $item) {
            consulta(
                'INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, tamanho, quantidade, preco_unitario)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$pedido_id, $item['produto']['id'], $item['produto']['nome'], $item['tamanho'], $item['quantidade'], $item['preco']]
            );
            consulta(
                'UPDATE produto_tamanhos SET estoque = estoque - ? WHERE produto_id = ? AND tamanho = ?',
                [$item['quantidade'], $item['produto']['id'], $item['tamanho']]
            );
        }

        registrar_historico($pedido_id, 'aguardando_pagamento', 'Pedido recebido');
        if ($status === 'pago') {
            registrar_historico($pedido_id, 'pago', 'Pagamento no cartão aprovado (' . $pagamento['bandeira'] . ' final ' . $pagamento['final'] . ')');
        }

        $pdo->commit();
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }

    // a sessão lembra os pedidos feitos nela, para mostrar as telas de Pix e confirmação sem login
    $_SESSION['pedidos_sessao'][] = $numero;
    sacola_esvaziar();
    unset($_SESSION['checkout']['pagamento']);

    return [$numero, null];
}

function registrar_historico(int $pedido_id, string $status, string $descricao): void
{
    consulta('INSERT INTO pedido_historico (pedido_id, status, descricao) VALUES (?, ?, ?)', [$pedido_id, $status, $descricao]);
}

/**
 * Pedido pelo número, só se foi feito nesta sessão ou é do cliente logado.
 * Assim ninguém vê o pedido de outra pessoa trocando o número na URL.
 */
function pedido_da_sessao(string $numero): ?array
{
    $permitido = in_array($numero, $_SESSION['pedidos_sessao'] ?? [], true);
    $pedido = consulta('SELECT * FROM pedidos WHERE numero = ?', [$numero])->fetch();
    if (!$pedido) {
        return null;
    }
    $cliente = $_SESSION['cliente_id'] ?? null;
    if (!$permitido && !($cliente && (int) $pedido['cliente_id'] === (int) $cliente)) {
        return null;
    }
    $pedido['itens'] = consulta('SELECT * FROM pedido_itens WHERE pedido_id = ? ORDER BY id', [$pedido['id']])->fetchAll();
    return $pedido;
}

/** Pix simulado aprovado: muda o status e registra no histórico. */
function aprovar_pagamento_simulado(array $pedido, string $descricao): void
{
    if ($pedido['status'] !== 'aguardando_pagamento') {
        return;
    }
    consulta('UPDATE pedidos SET status = ? WHERE id = ?', ['pago', $pedido['id']]);
    registrar_historico((int) $pedido['id'], 'pago', $descricao);
}

/** Código "copia e cola" de mentira, com cara de Pix (não é um código Pix válido). */
function pix_copia_e_cola(array $pedido): string
{
    $valor = number_format((float) $pedido['total'], 2, '.', '');
    return '00020126580014BR.GOV.BCB.PIX0136simulacao-triade-esporte-' . strtolower($pedido['numero'])
         . '5204000053039865406' . $valor . '5802BR5914TRIADE ESPORTE6013SAO LEOPOLDO6304SIMU';
}

/**
 * Desenha um "QR code" de mentira em SVG, a partir do número do pedido.
 * Tem os três quadrados de canto para parecer um QR, mas não pode ser lido.
 */
function qr_simulado_svg(string $semente): string
{
    $n = 29;
    $hash = '';
    for ($i = 0; strlen($hash) < $n * $n; $i++) {
        $hash .= hash('sha256', $semente . $i);
    }
    $quadrados = '';
    $canto = function (int $x, int $y) use ($n): bool {
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$cx, $cy]) {
            if ($x >= $cx - 1 && $x <= $cx + 7 && $y >= $cy - 1 && $y <= $cy + 7) {
                return true;
            }
        }
        return false;
    };
    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            if (!$canto($x, $y) && hexdec($hash[$y * $n + $x]) % 2 === 0) {
                $quadrados .= "M{$x} {$y}h1v1h-1z";
            }
        }
    }
    $olhos = '';
    foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$cx, $cy]) {
        $olhos .= "M{$cx} {$cy}h7v7h-7z M" . ($cx + 1) . ' ' . ($cy + 1) . 'v5h5v-5z M' . ($cx + 2) . ' ' . ($cy + 2) . 'h3v3h-3z ';
    }
    return '<svg viewBox="-2 -2 ' . ($n + 4) . ' ' . ($n + 4) . '" class="qr-simulado" role="img" aria-label="QR code do Pix (simulação)" shape-rendering="crispEdges">'
         . '<rect x="-2" y="-2" width="' . ($n + 4) . '" height="' . ($n + 4) . '" fill="#fff"/>'
         . '<path fill="#07090D" fill-rule="evenodd" d="' . $quadrados . ' ' . $olhos . '"/></svg>';
}

/** Barra inferior do checkout: total + botão que envia o formulário da etapa. */
function checkout_barra(string $form_id, string $rotulo, float $total, string $detalhe): string
{
    return '<div class="barra-inferior vidro-thick"><div class="barra-inferior__conteudo">'
         . '<div class="barra-inferior__resumo"><span class="t-headline">' . dinheiro($total) . '</span>'
         . '<span class="t-caption texto-secundario">' . e($detalhe) . '</span></div>'
         . '<button type="submit" form="' . e($form_id) . '" class="botao botao--primary" style="flex: 1">' . e($rotulo) . '</button>'
         . '</div></div>';
}
