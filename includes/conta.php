<?php
/**
 * Conta do cliente: login, cadastro, recuperação de senha, endereços e favoritos.
 *
 * O login fica na sessão: $_SESSION['cliente_id'].
 * Senhas são guardadas com password_hash() (nunca o texto da senha).
 */

const SENHA_MINIMA          = 8;
const LOGIN_MAX_TENTATIVAS  = 5;   // erros seguidos antes de esperar
const LOGIN_ESPERA_SEGUNDOS = 60;
const RECUPERACAO_MINUTOS   = 30;  // validade do link de troca de senha

/** Cliente logado (ou null). Lê o banco uma vez por página. */
function cliente_logado(): ?array
{
    static $cache = [];
    $id = (int) ($_SESSION['cliente_id'] ?? 0);
    if (!$id) {
        return null;
    }
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = consulta('SELECT id, nome, email, cpf, telefone, criado_em FROM clientes WHERE id = ?', [$id])->fetch() ?: null;
    }
    if (!$cache[$id]) {
        unset($_SESSION['cliente_id']); // conta apagada
    }
    return $cache[$id];
}

/** Páginas que exigem login mandam para Entrar e voltam para cá depois. */
function exigir_login(): array
{
    $cliente = cliente_logado();
    if (!$cliente) {
        $aqui = ltrim(substr($_SERVER['REQUEST_URI'] ?? '', strlen(url_base())), '/');
        redirecionar('conta/entrar.php?voltar=' . urlencode($aqui));
    }
    return $cliente;
}

/**
 * Para onde ir depois de entrar. Só aceita páginas do próprio site
 * (evita alguém montar um link que leve para um site falso depois do login).
 */
function destino_seguro(?string $voltar, string $padrao = 'conta/'): string
{
    $voltar = (string) $voltar;
    if ($voltar === 'checkout') {
        return 'checkout/identificacao.php';
    }
    if (preg_match('~^[a-z0-9_/-]*(\.php)?(\?[a-z0-9_=&%.-]*)?$~i', $voltar) && !str_contains($voltar, '..') && !str_starts_with($voltar, '/')) {
        return $voltar === '' ? $padrao : $voltar;
    }
    return $padrao;
}

function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome));
    $primeira = mb_substr($partes[0] ?? '', 0, 1);
    $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';
    return mb_strtoupper($primeira . $ultima);
}

// ---------------------------------------------------------------
// Entrar e sair
// ---------------------------------------------------------------

/** Quantos segundos faltam para poder tentar de novo (0 = pode tentar). */
function login_espera(): int
{
    $bloqueio = $_SESSION['login_bloqueado_ate'] ?? 0;
    return max(0, $bloqueio - time());
}

/** Devolve null se entrou, ou a mensagem de erro. */
function entrar(string $email, string $senha): ?string
{
    if ($espera = login_espera()) {
        return "Muitas tentativas. Tente de novo em {$espera} segundos.";
    }
    $cliente = consulta('SELECT id, senha_hash FROM clientes WHERE email = ?', [mb_strtolower(trim($email))])->fetch();

    if (!$cliente || !password_verify($senha, $cliente['senha_hash'])) {
        $_SESSION['login_erros'] = ($_SESSION['login_erros'] ?? 0) + 1;
        if ($_SESSION['login_erros'] >= LOGIN_MAX_TENTATIVAS) {
            $_SESSION['login_bloqueado_ate'] = time() + LOGIN_ESPERA_SEGUNDOS;
            $_SESSION['login_erros'] = 0;
        }
        return 'E-mail ou senha incorretos.';
    }

    if (password_needs_rehash($cliente['senha_hash'], PASSWORD_DEFAULT)) {
        consulta('UPDATE clientes SET senha_hash = ? WHERE id = ?', [password_hash($senha, PASSWORD_DEFAULT), $cliente['id']]);
    }
    logar((int) $cliente['id']);
    return null;
}

function logar(int $cliente_id): void
{
    session_regenerate_id(true); // novo código de sessão a cada login
    $_SESSION['cliente_id'] = $cliente_id;
    unset($_SESSION['login_erros'], $_SESSION['login_bloqueado_ate']);
    vincular_pedidos_da_sessao($cliente_id);
}

/** Sai da conta. A sacola continua. */
function sair(): void
{
    unset($_SESSION['cliente_id'], $_SESSION['checkout']);
    session_regenerate_id(true);
}

/**
 * Pedidos feitos sem conta nesta mesma sessão passam a ser do cliente.
 * Não junta pedidos antigos só pelo e-mail: sem confirmar o e-mail,
 * qualquer um poderia criar conta com o e-mail de outra pessoa e ver os pedidos dela.
 */
function vincular_pedidos_da_sessao(int $cliente_id): void
{
    $numeros = $_SESSION['pedidos_sessao'] ?? [];
    if (!$numeros) {
        return;
    }
    $marcas = implode(',', array_fill(0, count($numeros), '?'));
    consulta("UPDATE pedidos SET cliente_id = ? WHERE cliente_id IS NULL AND numero IN ($marcas)", array_merge([$cliente_id], $numeros));
}

// ---------------------------------------------------------------
// Criar conta
// ---------------------------------------------------------------

function validar_cadastro(array $post): array
{
    $d = [
        'nome'     => trim((string) ($post['nome'] ?? '')),
        'email'    => mb_strtolower(trim((string) ($post['email'] ?? ''))),
        'cpf'      => so_numeros($post['cpf'] ?? ''),
        'telefone' => so_numeros($post['telefone'] ?? ''),
    ];
    $senha = (string) ($post['senha'] ?? '');
    $erros = [];

    if (mb_strlen($d['nome']) < 5 || !str_contains($d['nome'], ' ')) {
        $erros['nome'] = 'Digite nome e sobrenome.';
    } elseif (mb_strlen($d['nome']) > 120) {
        $erros['nome'] = 'Nome muito longo.';
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($d['email']) > 160) {
        $erros['email'] = 'E-mail inválido.';
    } elseif (consulta('SELECT 1 FROM clientes WHERE email = ?', [$d['email']])->fetchColumn()) {
        $erros['email'] = 'Já existe uma conta com este e-mail. Tente entrar.';
    }
    if ($d['cpf'] !== '' && !cpf_valido($d['cpf'])) {
        $erros['cpf'] = 'CPF inválido.';
    }
    if ($d['telefone'] !== '' && (strlen($d['telefone']) < 10 || strlen($d['telefone']) > 11)) {
        $erros['telefone'] = 'Telefone com DDD.';
    }
    $erros += validar_senha($senha, (string) ($post['senha_confirmar'] ?? ''));

    $d['senha'] = $senha;
    return [$d, $erros];
}

function validar_senha(string $senha, string $confirmar): array
{
    if (mb_strlen($senha) < SENHA_MINIMA) {
        return ['senha' => 'Use pelo menos ' . SENHA_MINIMA . ' caracteres.'];
    }
    if (mb_strlen($senha) > 72) {
        return ['senha' => 'Use no máximo 72 caracteres.'];
    }
    if ($senha !== $confirmar) {
        return ['senha_confirmar' => 'As senhas não são iguais.'];
    }
    return [];
}

/** Cria o cliente e já entra na conta. Devolve o id. */
function criar_cliente(array $d): int
{
    consulta(
        'INSERT INTO clientes (nome, email, cpf, telefone, senha_hash) VALUES (?, ?, ?, ?, ?)',
        [$d['nome'], $d['email'], $d['cpf'] ?: null, $d['telefone'] ? formatar_telefone($d['telefone']) : null, password_hash($d['senha'], PASSWORD_DEFAULT)]
    );
    $id = (int) db()->lastInsertId();
    logar($id);
    return $id;
}

// ---------------------------------------------------------------
// Recuperar senha (o e-mail é simulado: o link aparece na tela)
// ---------------------------------------------------------------

/** Cria o código de troca de senha. Devolve o código, ou null se o e-mail não tem conta. */
function criar_recuperacao(string $email): ?string
{
    $id = consulta('SELECT id FROM clientes WHERE email = ?', [mb_strtolower(trim($email))])->fetchColumn();
    if (!$id) {
        return null;
    }
    // códigos antigos deixam de valer
    consulta('UPDATE recuperacao_senha SET usado_em = NOW() WHERE cliente_id = ? AND usado_em IS NULL', [$id]);

    $codigo = bin2hex(random_bytes(32));
    consulta(
        'INSERT INTO recuperacao_senha (cliente_id, token_hash, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))',
        [$id, hash('sha256', $codigo), RECUPERACAO_MINUTOS]
    );
    return $codigo;
}

/** Pedido de troca de senha ainda válido (ou null). O banco guarda só o hash do código. */
function recuperacao_valida(string $codigo): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $codigo)) {
        return null;
    }
    return consulta(
        'SELECT r.id, r.cliente_id, c.email FROM recuperacao_senha r JOIN clientes c ON c.id = r.cliente_id
          WHERE r.token_hash = ? AND r.usado_em IS NULL AND r.expira_em > NOW()',
        [hash('sha256', $codigo)]
    )->fetch() ?: null;
}

function trocar_senha(array $recuperacao, string $senha): void
{
    consulta('UPDATE clientes SET senha_hash = ? WHERE id = ?', [password_hash($senha, PASSWORD_DEFAULT), $recuperacao['cliente_id']]);
    consulta('UPDATE recuperacao_senha SET usado_em = NOW() WHERE id = ?', [$recuperacao['id']]);
}

// ---------------------------------------------------------------
// Pedidos
// ---------------------------------------------------------------

/** Rótulo e cor de cada status do pedido. */
const STATUS_PEDIDO = [
    'aguardando_pagamento' => ['Aguardando pagamento', 'alerta'],
    'pagamento_recusado'   => ['Pagamento recusado', 'erro'],
    'pago'                 => ['Pagamento aprovado', 'marca'],
    'em_separacao'         => ['Em separação', 'marca'],
    'enviado'              => ['A caminho', 'marca'],
    'pronto_retirada'      => ['Pronto para retirar', 'sucesso'],
    'entregue'             => ['Entregue', 'sucesso'],
    'cancelado'            => ['Cancelado', 'erro'],
];

function selo_status(string $status): string
{
    [$texto, $tipo] = STATUS_PEDIDO[$status] ?? [$status, 'marca'];
    return '<span class="status-pedido status-pedido--' . e($tipo) . '">' . e($texto) . '</span>';
}

function pedidos_do_cliente(int $cliente_id): array
{
    $pedidos = consulta(
        'SELECT p.*, (SELECT SUM(quantidade) FROM pedido_itens WHERE pedido_id = p.id) AS quantidade_itens
           FROM pedidos p WHERE p.cliente_id = ? ORDER BY p.criado_em DESC, p.id DESC',
        [$cliente_id]
    )->fetchAll();

    if ($pedidos) {
        // primeiras peças de cada pedido, para as miniaturas
        $ids = array_column($pedidos, 'id');
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $itens = consulta(
            "SELECT i.pedido_id, i.nome_produto, pr.midia, (SELECT arquivo FROM produto_imagens WHERE produto_id = pr.id ORDER BY ordem LIMIT 1) AS imagem
               FROM pedido_itens i LEFT JOIN produtos pr ON pr.id = i.produto_id
              WHERE i.pedido_id IN ($marcas) ORDER BY i.id",
            $ids
        )->fetchAll();
        foreach ($pedidos as &$p) {
            $p['miniaturas'] = array_values(array_filter($itens, fn ($i) => $i['pedido_id'] == $p['id']));
        }
    }
    return $pedidos;
}

/**
 * Linha do tempo do rastreio: os passos do pedido, com a data de quando cada um aconteceu.
 * Passos que ainda não aconteceram aparecem apagados.
 */
function linha_do_tempo(array $pedido): array
{
    $retirada = $pedido['entrega_tipo'] === 'retirada';
    $passos = [
        'aguardando_pagamento' => 'Pedido recebido',
        'pago'                 => 'Pagamento aprovado',
        'em_separacao'         => 'Em separação',
        $retirada ? 'pronto_retirada' : 'enviado' => $retirada ? 'Pronto para retirar' : 'Enviado',
        'entregue'             => $retirada ? 'Retirado' : 'Entregue',
    ];

    $historico = consulta('SELECT status, descricao, criado_em FROM pedido_historico WHERE pedido_id = ? ORDER BY criado_em, id', [$pedido['id']])->fetchAll();
    $por_status = [];
    foreach ($historico as $h) {
        $por_status[$h['status']] = $h;
    }

    $linha = [];
    $chegou = false;
    foreach (array_reverse($passos, true) as $status => $rotulo) {
        // um passo conta como feito se ele ou algum passo depois dele aconteceu
        $chegou = $chegou || isset($por_status[$status]);
        array_unshift($linha, [
            'rotulo'    => $rotulo,
            'feito'     => $chegou,
            'descricao' => $por_status[$status]['descricao'] ?? null,
            'quando'    => $por_status[$status]['criado_em'] ?? null,
        ]);
    }
    return $linha;
}

function data_hora_curta(string $data): string
{
    $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    $d = new DateTimeImmutable($data);
    return $d->format('j') . ' de ' . $meses[(int) $d->format('n') - 1] . ', ' . $d->format('H:i');
}

function data_longa(string $data): string
{
    $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $d = new DateTimeImmutable($data);
    return $d->format('j') . ' de ' . $meses[(int) $d->format('n') - 1] . ' de ' . $d->format('Y');
}

// ---------------------------------------------------------------
// Endereços
// ---------------------------------------------------------------

function enderecos_do_cliente(int $cliente_id): array
{
    return consulta('SELECT * FROM enderecos WHERE cliente_id = ? ORDER BY principal DESC, id', [$cliente_id])->fetchAll();
}

/** Endereço do cliente pelo id (null se não for dele). */
function endereco_do_cliente(int $cliente_id, int $id): ?array
{
    return consulta('SELECT * FROM enderecos WHERE id = ? AND cliente_id = ?', [$id, $cliente_id])->fetch() ?: null;
}

/** Salva (novo ou edição). O primeiro endereço vira o principal. */
function salvar_endereco(int $cliente_id, array $d, ?int $id = null, bool $principal = false): int
{
    $tem = (int) consulta('SELECT COUNT(*) FROM enderecos WHERE cliente_id = ?', [$cliente_id])->fetchColumn();
    $principal = $principal || $tem === 0;
    $campos = [
        ($d['apelido'] ?? '') !== '' ? $d['apelido'] : null, $d['destinatario'], $d['cep'], $d['logradouro'], $d['numero'],
        $d['complemento'] !== '' ? $d['complemento'] : null, $d['bairro'], $d['cidade'], 'RS',
    ];
    if ($id) {
        consulta(
            'UPDATE enderecos SET apelido = ?, destinatario = ?, cep = ?, logradouro = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, uf = ?
              WHERE id = ? AND cliente_id = ?',
            array_merge($campos, [$id, $cliente_id])
        );
    } else {
        consulta(
            'INSERT INTO enderecos (apelido, destinatario, cep, logradouro, numero, complemento, bairro, cidade, uf, cliente_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_merge($campos, [$cliente_id])
        );
        $id = (int) db()->lastInsertId();
    }
    if ($principal) {
        tornar_principal($cliente_id, $id);
    }
    return $id;
}

function tornar_principal(int $cliente_id, int $id): void
{
    consulta('UPDATE enderecos SET principal = (id = ?) WHERE cliente_id = ?', [$id, $cliente_id]);
}

function excluir_endereco(int $cliente_id, int $id): void
{
    $era_principal = consulta('SELECT principal FROM enderecos WHERE id = ? AND cliente_id = ?', [$id, $cliente_id])->fetchColumn();
    consulta('DELETE FROM enderecos WHERE id = ? AND cliente_id = ?', [$id, $cliente_id]);
    if ($era_principal) {
        // o mais antigo que sobrou vira o principal
        $outro = consulta('SELECT id FROM enderecos WHERE cliente_id = ? ORDER BY id LIMIT 1', [$cliente_id])->fetchColumn();
        if ($outro) {
            tornar_principal($cliente_id, (int) $outro);
        }
    }
}

/** Endereço em uma ou duas linhas de texto. */
function endereco_texto(array $e): array
{
    $rua = $e['logradouro'] . ', ' . (array_key_exists('numero_end', $e) ? $e['numero_end'] : $e['numero']) . (!empty($e['complemento']) ? ' · ' . $e['complemento'] : '');
    $cidade = $e['bairro'] . ', ' . $e['cidade'] . '/' . ($e['uf'] ?? 'RS') . ' · ' . formatar_cep($e['cep']);
    return [$rua, $cidade];
}

// ---------------------------------------------------------------
// Favoritos
// ---------------------------------------------------------------

/** Ids dos produtos favoritos do cliente logado (vazio se não está logado). */
function ids_favoritos(): array
{
    static $ids = null;
    if ($ids === null) {
        $cliente = cliente_logado();
        $ids = $cliente
            ? array_map('intval', consulta('SELECT produto_id FROM favoritos WHERE cliente_id = ?', [$cliente['id']])->fetchAll(PDO::FETCH_COLUMN))
            : [];
    }
    return $ids;
}

function eh_favorito(int $produto_id): bool
{
    return in_array($produto_id, ids_favoritos(), true);
}

/** Liga ou desliga o favorito. Devolve true se ficou favoritado. */
function alternar_favorito(int $cliente_id, int $produto_id, ?bool $querer = null): bool
{
    $ja = (bool) consulta('SELECT 1 FROM favoritos WHERE cliente_id = ? AND produto_id = ?', [$cliente_id, $produto_id])->fetchColumn();
    $querer ??= !$ja;
    if ($querer && !$ja) {
        consulta('INSERT INTO favoritos (cliente_id, produto_id) VALUES (?, ?)', [$cliente_id, $produto_id]);
    } elseif (!$querer && $ja) {
        consulta('DELETE FROM favoritos WHERE cliente_id = ? AND produto_id = ?', [$cliente_id, $produto_id]);
    }
    return $querer;
}

function produtos_favoritos(int $cliente_id): array
{
    return consulta(
        SQL_PRODUTO_CARD . ' JOIN favoritos f ON f.produto_id = p.id WHERE f.cliente_id = ? AND p.ativo = 1 ORDER BY f.criado_em DESC, p.id',
        [$cliente_id]
    )->fetchAll();
}

/** Botão de coração. Funciona sem JavaScript (formulário) e com JavaScript (sem recarregar). */
function botao_favorito(int $produto_id, string $classe = 'botao-icone--plain', string $voltar = ''): string
{
    $marcado = eh_favorito($produto_id);
    return '<form class="form-favorito" action="' . e(url('api/favoritos.php')) . '" method="post" data-favorito>'
         . csrf_campo()
         . '<input type="hidden" name="produto_id" value="' . $produto_id . '">'
         . '<input type="hidden" name="voltar" value="' . e($voltar) . '">'
         . '<button type="submit" class="botao-icone ' . e($classe) . '" aria-pressed="' . ($marcado ? 'true' : 'false') . '"'
         . ' aria-label="' . ($marcado ? 'Remover dos favoritos' : 'Favoritar') . '">' . icone('coracao') . '</button>'
         . '</form>';
}
