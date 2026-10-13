<?php
/**
 * Painel da loja (etapa 7): login da loja, pedidos, produtos, esportes e categorias.
 * Só as páginas da pasta admin/ carregam este arquivo:
 *   require __DIR__ . '/../includes/admin.php';
 *
 * O login da loja é separado do login do cliente: usa a tabela administradores
 * e guarda $_SESSION['admin_id'], então entrar no painel não entra na conta de cliente.
 */

require __DIR__ . '/bootstrap.php';

const ESTOQUE_BAIXO       = 2;                // tamanhos com até 2 peças aparecem no aviso do painel
const FOTO_MAX_BYTES      = 3 * 1024 * 1024;  // 3 MB por foto
const FOTO_MAX_POR_PRODUTO = 6;
const FOTO_TIPOS          = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
const PASTA_FOTOS         = __DIR__ . '/../assets/img/produtos/';

// ---------------------------------------------------------------
// Login da loja
// ---------------------------------------------------------------

function admin_logado(): ?array
{
    static $cache = [];
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if (!$id) {
        return null;
    }
    if (!array_key_exists($id, $cache)) {
        $cache[$id] = consulta('SELECT id, nome, email FROM administradores WHERE id = ?', [$id])->fetch() ?: null;
    }
    if (!$cache[$id]) {
        unset($_SESSION['admin_id']);
    }
    return $cache[$id];
}

/** Toda página do painel começa com isto. Sem login, vai para admin/entrar.php e volta depois. */
function exigir_admin(): array
{
    $admin = admin_logado();
    if (!$admin) {
        $aqui = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
        $consulta = $_SERVER['QUERY_STRING'] ?? '';
        $voltar = $_SERVER['REQUEST_METHOD'] === 'GET' ? $aqui . ($consulta !== '' ? '?' . $consulta : '') : '';
        redirecionar('admin/entrar.php' . ($voltar !== '' && $voltar !== 'index.php' ? '?voltar=' . urlencode($voltar) : ''));
    }
    return $admin;
}

/** Para onde ir depois de entrar: só páginas da própria pasta admin/. */
function admin_destino(?string $voltar): string
{
    $voltar = (string) $voltar;
    return preg_match('~^[a-z-]+\.php(\?[a-z0-9_=&%.-]*)?$~i', $voltar) ? 'admin/' . $voltar : 'admin/';
}

/** Devolve null se entrou, ou a mensagem de erro. Mesmo limite de tentativas da conta do cliente. */
function admin_entrar(string $email, string $senha): ?string
{
    $espera = max(0, ($_SESSION['admin_bloqueado_ate'] ?? 0) - time());
    if ($espera) {
        return "Muitas tentativas. Tente de novo em {$espera} segundos.";
    }
    $admin = consulta('SELECT id, senha_hash FROM administradores WHERE email = ?', [mb_strtolower(trim($email))])->fetch();

    if (!$admin || !password_verify($senha, $admin['senha_hash'])) {
        $_SESSION['admin_erros'] = ($_SESSION['admin_erros'] ?? 0) + 1;
        if ($_SESSION['admin_erros'] >= LOGIN_MAX_TENTATIVAS) {
            $_SESSION['admin_bloqueado_ate'] = time() + LOGIN_ESPERA_SEGUNDOS;
            $_SESSION['admin_erros'] = 0;
        }
        return 'E-mail ou senha incorretos.';
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    unset($_SESSION['admin_erros'], $_SESSION['admin_bloqueado_ate']);
    return null;
}

function admin_sair(): void
{
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
}

/** Troca a senha da loja. Devolve a lista de erros (vazia = trocou). */
function admin_trocar_senha(int $admin_id, string $atual, string $nova, string $confirmar): array
{
    $hash = consulta('SELECT senha_hash FROM administradores WHERE id = ?', [$admin_id])->fetchColumn();
    if (!$hash || !password_verify($atual, $hash)) {
        return ['atual' => 'Senha atual incorreta.'];
    }
    $erros = validar_senha($nova, $confirmar);
    if (!$erros) {
        consulta('UPDATE administradores SET senha_hash = ? WHERE id = ?', [password_hash($nova, PASSWORD_DEFAULT), $admin_id]);
    }
    return $erros;
}

/** Moldura das páginas do painel: mesma aparência da loja, com as abas do painel. */
function admin_pagina_inicio(array $opcoes): void
{
    $opcoes['abas'] = [
        'painel'   => ['Painel',   'inicio',   'admin/'],
        'pedidos'  => ['Pedidos',  'pacote',   'admin/pedidos.php', pedidos_para_separar()],
        'produtos' => ['Produtos', 'grade',    'admin/produtos.php'],
        'catalogo' => ['Catálogo', 'esportes', 'admin/catalogo.php'],
    ];
    $opcoes['robots'] = 'noindex, nofollow';
    $opcoes['titulo'] = ($opcoes['titulo'] ?? 'Painel') . ' · Painel';
    pagina_inicio($opcoes);
}

// ---------------------------------------------------------------
// Pedidos
// ---------------------------------------------------------------

/** Filtros da lista de pedidos: chave => [rótulo, status incluídos]. */
const FILTROS_PEDIDO = [
    'todos'      => ['Todos', []],
    'separar'    => ['Para separar', ['pago', 'em_separacao']],
    'pagamento'  => ['Aguardando pagamento', ['aguardando_pagamento']],
    'enviados'   => ['Enviados', ['enviado', 'pronto_retirada']],
    'entregues'  => ['Entregues', ['entregue']],
    'cancelados' => ['Cancelados', ['cancelado', 'pagamento_recusado']],
];

const NOMES_ENTREGA = ['pac' => 'PAC', 'sedex' => 'SEDEX', 'transportadora' => 'Transportadora', 'retirada' => 'Retirada na loja'];
const NOMES_PAGAMENTO = ['pix' => 'Pix', 'cartao' => 'Cartão', 'boleto' => 'Boleto'];

/** Pedidos pagos que a loja ainda precisa separar (contador da aba Pedidos). */
function pedidos_para_separar(): int
{
    static $total = null;
    return $total ??= (int) consulta("SELECT COUNT(*) FROM pedidos WHERE status IN ('pago', 'em_separacao')")->fetchColumn();
}

/** Quantos pedidos há em cada filtro (para os chips). */
function contagem_filtros_pedido(): array
{
    $por_status = consulta('SELECT status, COUNT(*) AS total FROM pedidos GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
    $contagem = [];
    foreach (FILTROS_PEDIDO as $chave => [, $status]) {
        $contagem[$chave] = $status
            ? array_sum(array_intersect_key($por_status, array_flip($status)))
            : array_sum($por_status);
    }
    return $contagem;
}

/** Lista de pedidos do painel, do mais novo para o mais antigo. */
function pedidos_admin(string $filtro, string $busca = '', int $limite = 100): array
{
    $where = [];
    $params = [];
    $status = FILTROS_PEDIDO[$filtro][1] ?? [];
    if ($status) {
        $where[] = 'p.status IN (' . implode(',', array_fill(0, count($status), '?')) . ')';
        $params = array_merge($params, $status);
    }
    if ($busca !== '') {
        $termo = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busca) . '%';
        $where[] = '(p.numero LIKE ? OR p.nome LIKE ? OR p.email LIKE ?)';
        array_push($params, $termo, $termo, $termo);
    }
    return consulta(
        'SELECT p.*, (SELECT SUM(quantidade) FROM pedido_itens WHERE pedido_id = p.id) AS quantidade_itens
           FROM pedidos p' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
          ORDER BY p.criado_em DESC, p.id DESC LIMIT ' . (int) $limite,
        $params
    )->fetchAll();
}

/** Pedido completo para a tela de detalhe do painel (qualquer pedido, sem a trava de sessão do cliente). */
function pedido_admin(string $numero): ?array
{
    $pedido = consulta('SELECT * FROM pedidos WHERE numero = ?', [$numero])->fetch();
    if (!$pedido) {
        return null;
    }
    $pedido['itens'] = consulta(
        'SELECT i.*, p.midia, (SELECT arquivo FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem, id LIMIT 1) AS imagem
           FROM pedido_itens i LEFT JOIN produtos p ON p.id = i.produto_id WHERE i.pedido_id = ? ORDER BY i.id',
        [$pedido['id']]
    )->fetchAll();
    $pedido['historico'] = consulta(
        'SELECT status, descricao, criado_em FROM pedido_historico WHERE pedido_id = ? ORDER BY criado_em DESC, id DESC',
        [$pedido['id']]
    )->fetchAll();
    return $pedido;
}

/**
 * Para quais status o pedido pode ir agora. O pedido anda sempre para frente:
 * pagamento > separação > envio (ou pronto para retirar) > entrega. Cancelar só antes de sair da loja.
 */
function proximos_status(array $pedido): array
{
    $retirada = $pedido['entrega_tipo'] === 'retirada';
    return match ($pedido['status']) {
        'aguardando_pagamento' => ['pago', 'cancelado'],
        'pagamento_recusado'   => ['cancelado'],
        'pago'                 => ['em_separacao', 'cancelado'],
        'em_separacao'         => [$retirada ? 'pronto_retirada' : 'enviado', 'cancelado'],
        'enviado'              => ['entregue'],
        'pronto_retirada'      => ['entregue', 'cancelado'],
        default                => [],
    };
}

/** Texto do botão de cada mudança. */
function rotulo_acao_status(string $status, array $pedido): string
{
    $retirada = $pedido['entrega_tipo'] === 'retirada';
    return match ($status) {
        'pago'            => 'Confirmar pagamento',
        'em_separacao'    => 'Começar a separar',
        'enviado'         => 'Marcar como enviado',
        'pronto_retirada' => 'Avisar que está pronto para retirar',
        'entregue'        => $retirada ? 'Marcar como retirado' : 'Marcar como entregue',
        'cancelado'       => 'Cancelar pedido',
        default           => $status,
    };
}

/** Código de rastreio só com letras e números (ex.: QB482915376BR). */
function normalizar_rastreio(string $codigo): string
{
    return strtoupper(preg_replace('/[^a-z0-9]/i', '', $codigo));
}

function rastreio_valido(string $codigo): bool
{
    return (bool) preg_match('/^[A-Z0-9]{8,20}$/', $codigo);
}

/**
 * Muda o status do pedido, grava na linha do tempo do cliente
 * e, se cancelar, devolve as peças ao estoque. Devolve null se deu certo, ou a mensagem de erro.
 */
function mudar_status_pedido(array $pedido, string $novo, string $rastreio = '', string $motivo = ''): ?string
{
    if (!in_array($novo, proximos_status($pedido), true)) {
        return 'Esse passo não vale para o status atual do pedido.';
    }

    $rastreio = normalizar_rastreio($rastreio);
    if ($novo === 'enviado' && !rastreio_valido($rastreio)) {
        return 'Informe o código de rastreio (8 a 20 letras e números).';
    }
    $motivo = mb_substr(trim($motivo), 0, 120);
    $retirada = $pedido['entrega_tipo'] === 'retirada';

    $descricao = match ($novo) {
        'pago'            => $pedido['pagamento_tipo'] === 'boleto' ? 'Boleto compensado' : 'Pagamento confirmado',
        'em_separacao'    => 'A loja está separando seus produtos',
        'enviado'         => 'Enviado por ' . NOMES_ENTREGA[$pedido['entrega_tipo']] . ' · código ' . $rastreio,
        'pronto_retirada' => 'Pronto para retirada em São Leopoldo',
        'entregue'        => $retirada ? 'Retirado na loja' : 'Entregue no endereço',
        'cancelado'       => 'Pedido cancelado pela loja' . ($motivo !== '' ? ': ' . $motivo : ''),
    };

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // "AND status = ?" impede que duas pessoas mudem o mesmo pedido ao mesmo tempo
        $mudou = consulta(
            'UPDATE pedidos SET status = ?, codigo_rastreio = COALESCE(?, codigo_rastreio) WHERE id = ? AND status = ?',
            [$novo, $novo === 'enviado' ? $rastreio : null, $pedido['id'], $pedido['status']]
        )->rowCount();
        if (!$mudou) {
            $pdo->rollBack();
            return 'O pedido mudou enquanto você olhava. Confira o status de novo.';
        }
        if ($novo === 'cancelado') {
            devolver_estoque((int) $pedido['id']);
        }
        registrar_historico((int) $pedido['id'], $novo, $descricao);
        $pdo->commit();
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }
    return null;
}

/** Corrige o código de rastreio de um pedido já enviado. */
function corrigir_rastreio(array $pedido, string $codigo): ?string
{
    $codigo = normalizar_rastreio($codigo);
    if ($pedido['status'] !== 'enviado') {
        return 'Só dá para corrigir o código de pedidos enviados.';
    }
    if (!rastreio_valido($codigo)) {
        return 'Informe o código de rastreio (8 a 20 letras e números).';
    }
    consulta('UPDATE pedidos SET codigo_rastreio = ? WHERE id = ?', [$codigo, $pedido['id']]);
    return null;
}

// ---------------------------------------------------------------
// Resumo do painel
// ---------------------------------------------------------------

function resumo_painel(): array
{
    $vendas = consulta(
        "SELECT COUNT(*) AS pedidos, COALESCE(SUM(total), 0) AS valor
           FROM pedidos WHERE status NOT IN ('cancelado', 'pagamento_recusado', 'aguardando_pagamento') AND criado_em >= ?",
        [(new DateTimeImmutable('first day of this month'))->format('Y-m-d 00:00:00')]
    )->fetch();
    $hoje = (int) consulta('SELECT COUNT(*) FROM pedidos WHERE criado_em >= ?', [date('Y-m-d 00:00:00')])->fetchColumn();
    $por_status = consulta('SELECT status, COUNT(*) FROM pedidos GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

    return [
        'pedidos_hoje'  => $hoje,
        'vendas_mes'    => (float) $vendas['valor'],
        'pedidos_mes'   => (int) $vendas['pedidos'],
        'separar'       => (int) ($por_status['pago'] ?? 0) + (int) ($por_status['em_separacao'] ?? 0),
        'pagamento'     => (int) ($por_status['aguardando_pagamento'] ?? 0),
        'retirar'       => (int) ($por_status['pronto_retirada'] ?? 0),
    ];
}

/** Tamanhos de produtos ativos que estão acabando (ou acabaram). */
function estoque_baixo(int $limite = 8): array
{
    return consulta(
        'SELECT p.id, p.nome, p.midia, t.tamanho, t.estoque,
                (SELECT arquivo FROM produto_imagens WHERE produto_id = p.id ORDER BY ordem, id LIMIT 1) AS imagem
           FROM produto_tamanhos t JOIN produtos p ON p.id = t.produto_id
          WHERE p.ativo = 1 AND t.estoque <= ?
          ORDER BY t.estoque, p.nome LIMIT ' . (int) $limite,
        [ESTOQUE_BAIXO]
    )->fetchAll();
}

// ---------------------------------------------------------------
// Texto para endereço na web (slug)
// ---------------------------------------------------------------

/** "Calções de Basquete" -> "calcoes-de-basquete" */
function gerar_slug(string $texto): string
{
    $texto = mb_strtolower(trim($texto));
    $texto = strtr($texto, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
    ]);
    $texto = trim(preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
    return mb_substr($texto, 0, 50) ?: 'item';
}

/** Slug que ainda não existe na tabela (acrescenta -2, -3...). Categorias só precisam ser únicas dentro do esporte. */
function slug_livre(string $tabela, string $texto, ?int $esporte_id = null): string
{
    $tabela = ['produtos' => 'produtos', 'esportes' => 'esportes', 'categorias' => 'categorias'][$tabela];
    $base = gerar_slug($texto);
    $slug = $base;
    for ($n = 2; ; $n++) {
        $sql = "SELECT 1 FROM $tabela WHERE slug = ?" . ($esporte_id ? ' AND esporte_id = ?' : '');
        $params = $esporte_id ? [$slug, $esporte_id] : [$slug];
        if (!consulta($sql, $params)->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $n;
    }
}

/** "1.299,90", "199,9" ou "199.90" -> 199.9. Devolve null se não for um valor. */
function ler_preco(string $texto): ?float
{
    $texto = trim(str_replace(['R$', ' '], '', $texto));
    if (str_contains($texto, ',') || preg_match('/^\d{1,3}(\.\d{3})+$/', $texto)) {
        $texto = str_replace(['.', ','], ['', '.'], $texto); // "1.299,90" e "1.299" no jeito brasileiro
    }
    return preg_match('/^\d{1,7}(\.\d{1,2})?$/', $texto) ? round((float) $texto, 2) : null;
}

/** 199.9 -> "199,90" (para preencher o campo). */
function preco_campo(?float $valor): string
{
    return $valor === null ? '' : number_format($valor, 2, ',', '');
}

// ---------------------------------------------------------------
// Produtos
// ---------------------------------------------------------------

const MIDIAS_PRODUTO = [
    'camisa' => 'Camisa', 'chuteira' => 'Chuteira', 'tenis' => 'Tênis', 'bola' => 'Bola',
    'calcao' => 'Calção', 'meiao' => 'Meião', 'caneleira' => 'Caneleira',
];
const GUIAS_TAMANHO = ['' => 'Sem guia de tamanhos', 'camisa' => 'Camisas', 'calcado' => 'Calçados', 'calcao' => 'Calções', 'meiao' => 'Meiões'];

/** Todos os produtos (ativos e inativos), com estoque total e a foto de capa. */
function produtos_admin(string $busca = '', int $categoria_id = 0): array
{
    $where = [];
    $params = [];
    if ($busca !== '') {
        $termo = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busca) . '%';
        $where[] = '(p.nome LIKE ? OR p.marca LIKE ? OR p.time_nome LIKE ?)';
        array_push($params, $termo, $termo, $termo);
    }
    if ($categoria_id) {
        $where[] = 'p.categoria_id = ?';
        $params[] = $categoria_id;
    }
    return consulta(
        'SELECT p.*, c.nome AS categoria_nome, e.nome AS esporte_nome,
                (SELECT COALESCE(SUM(t.estoque), 0) FROM produto_tamanhos t WHERE t.produto_id = p.id) AS estoque_total,
                (SELECT COUNT(*) FROM produto_tamanhos t WHERE t.produto_id = p.id AND t.estoque <= ' . ESTOQUE_BAIXO . ') AS tamanhos_baixos,
                (SELECT i.arquivo FROM produto_imagens i WHERE i.produto_id = p.id ORDER BY i.ordem, i.id LIMIT 1) AS imagem
           FROM produtos p
           JOIN categorias c ON c.id = p.categoria_id
           JOIN esportes e   ON e.id = c.esporte_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
          ORDER BY p.ativo DESC, e.ordem, c.ordem, p.nome',
        $params
    )->fetchAll();
}

/** Produto para editar (mesmo inativo), com tamanhos e fotos. */
function produto_admin(int $id): ?array
{
    $produto = consulta('SELECT * FROM produtos WHERE id = ?', [$id])->fetch();
    if (!$produto) {
        return null;
    }
    $produto['tamanhos'] = consulta('SELECT tamanho, estoque FROM produto_tamanhos WHERE produto_id = ? ORDER BY ordem, id', [$id])->fetchAll();
    $produto['imagens']  = consulta('SELECT id, arquivo FROM produto_imagens WHERE produto_id = ? ORDER BY ordem, id', [$id])->fetchAll();
    $produto['vendido']  = (bool) consulta('SELECT 1 FROM pedido_itens WHERE produto_id = ? LIMIT 1', [$id])->fetchColumn();
    return $produto;
}

/** Esportes com as categorias de cada um (inclusive inativos), para o campo "Categoria". */
function opcoes_categoria(): array
{
    $linhas = consulta(
        'SELECT c.id, c.nome, c.ativo, e.nome AS esporte, e.ativo AS esporte_ativo
           FROM categorias c JOIN esportes e ON e.id = c.esporte_id ORDER BY e.ordem, e.nome, c.ordem, c.nome'
    )->fetchAll();
    $grupos = [];
    foreach ($linhas as $l) {
        $grupos[$l['esporte'] . ($l['esporte_ativo'] ? '' : ' (oculto)')][(int) $l['id']] = $l['nome'] . ($l['ativo'] ? '' : ' (oculta)');
    }
    return $grupos;
}

/**
 * Confere o formulário do produto.
 * Devolve [dados, tamanhos, erros]. Tamanhos: lista de ['tamanho' => 'M', 'estoque' => 5].
 */
function validar_produto(array $post): array
{
    $texto = fn (string $campo, int $max) => mb_substr(trim((string) ($post[$campo] ?? '')), 0, $max);
    $d = [
        'nome'              => $texto('nome', 120),
        'categoria_id'      => (int) ($post['categoria_id'] ?? 0),
        'marca'             => $texto('marca', 60),
        'time_nome'         => $texto('time_nome', 60),
        'descricao'         => $texto('descricao', 2000),
        'preco_texto'       => $texto('preco', 20),
        'promocional_texto' => $texto('preco_promocional', 20),
        'midia'             => (string) ($post['midia'] ?? 'camisa'),
        'guia_tamanhos'     => (string) ($post['guia_tamanhos'] ?? ''),
        'oficial'           => !empty($post['oficial']) ? 1 : 0,
        'lancamento'        => !empty($post['lancamento']) ? 1 : 0,
        'destaque'          => !empty($post['destaque']) ? 1 : 0,
        'ativo'             => !empty($post['ativo']) ? 1 : 0,
    ];
    $erros = [];

    if (mb_strlen($d['nome']) < 3) {
        $erros['nome'] = 'Digite o nome do produto.';
    }
    if (!$d['categoria_id'] || !consulta('SELECT 1 FROM categorias WHERE id = ?', [$d['categoria_id']])->fetchColumn()) {
        $erros['categoria_id'] = 'Escolha a categoria.';
    }
    $d['preco'] = ler_preco($d['preco_texto']);
    if ($d['preco'] === null || $d['preco'] <= 0) {
        $erros['preco'] = 'Digite o preço. Ex.: 199,90';
    }
    $d['preco_promocional'] = null;
    if ($d['promocional_texto'] !== '') {
        $d['preco_promocional'] = ler_preco($d['promocional_texto']);
        if ($d['preco_promocional'] === null || $d['preco_promocional'] <= 0) {
            $erros['preco_promocional'] = 'Digite um valor. Ex.: 149,90';
        } elseif ($d['preco'] !== null && $d['preco_promocional'] >= $d['preco']) {
            $erros['preco_promocional'] = 'O preço promocional precisa ser menor que o preço.';
        }
    }
    if (!isset(MIDIAS_PRODUTO[$d['midia']])) {
        $d['midia'] = 'camisa';
    }
    if (!isset(GUIAS_TAMANHO[$d['guia_tamanhos']])) {
        $d['guia_tamanhos'] = '';
    }

    // tamanhos: linhas vazias são ignoradas
    $tamanhos = [];
    $nomes = (array) ($post['tamanho'] ?? []);
    $estoques = (array) ($post['estoque'] ?? []);
    foreach ($nomes as $i => $nome) {
        $nome = mb_strtoupper(trim((string) $nome)) === 'ÚNICO' ? 'Único' : mb_strtoupper(trim((string) $nome));
        $estoque = trim((string) ($estoques[$i] ?? ''));
        if ($nome === '' && $estoque === '') {
            continue;
        }
        if ($nome === '' || mb_strlen($nome) > 10) {
            $erros['tamanhos'] = 'Cada tamanho precisa de um nome curto (até 10 letras). Ex.: P, M, 42, Único.';
        } elseif (!preg_match('/^\d{1,5}$/', $estoque)) {
            $erros['tamanhos'] = "Digite o estoque do tamanho $nome (0 ou mais).";
        } elseif (in_array($nome, array_column($tamanhos, 'tamanho'), true)) {
            $erros['tamanhos'] = "O tamanho $nome aparece duas vezes.";
        } else {
            $tamanhos[] = ['tamanho' => $nome, 'estoque' => (int) $estoque];
        }
    }
    if (!$tamanhos && !isset($erros['tamanhos'])) {
        $erros['tamanhos'] = 'Cadastre pelo menos um tamanho. Para bolas e acessórios, use "Único".';
    }

    return [$d, $tamanhos, $erros];
}

/** Grava o produto e os tamanhos. Devolve o id. */
function salvar_produto(array $d, array $tamanhos, ?int $id = null): int
{
    $campos = [
        $d['categoria_id'], $d['nome'], $d['descricao'] !== '' ? $d['descricao'] : null,
        $d['time_nome'] !== '' ? $d['time_nome'] : null, $d['marca'] !== '' ? $d['marca'] : null,
        $d['preco'], $d['preco_promocional'], $d['oficial'], $d['lancamento'], $d['destaque'],
        $d['midia'], $d['guia_tamanhos'] !== '' ? $d['guia_tamanhos'] : null, $d['ativo'],
    ];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        if ($id) {
            consulta(
                'UPDATE produtos SET categoria_id = ?, nome = ?, descricao = ?, time_nome = ?, marca = ?, preco = ?, preco_promocional = ?,
                        oficial = ?, lancamento = ?, destaque = ?, midia = ?, guia_tamanhos = ?, ativo = ? WHERE id = ?',
                array_merge($campos, [$id])
            );
        } else {
            consulta(
                'INSERT INTO produtos (categoria_id, nome, descricao, time_nome, marca, preco, preco_promocional,
                                       oficial, lancamento, destaque, midia, guia_tamanhos, ativo, slug)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array_merge($campos, [slug_livre('produtos', $d['nome'])])
            );
            $id = (int) $pdo->lastInsertId();
        }

        // tamanhos: atualiza os que ficaram, cria os novos e apaga os que saíram
        $existentes = consulta('SELECT tamanho FROM produto_tamanhos WHERE produto_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN);
        $manter = array_column($tamanhos, 'tamanho');
        foreach (array_diff($existentes, $manter) as $saiu) {
            consulta('DELETE FROM produto_tamanhos WHERE produto_id = ? AND tamanho = ?', [$id, $saiu]);
        }
        foreach ($tamanhos as $ordem => $t) {
            if (in_array($t['tamanho'], $existentes, true)) {
                consulta('UPDATE produto_tamanhos SET estoque = ?, ordem = ? WHERE produto_id = ? AND tamanho = ?', [$t['estoque'], $ordem + 1, $id, $t['tamanho']]);
            } else {
                consulta('INSERT INTO produto_tamanhos (produto_id, tamanho, estoque, ordem) VALUES (?, ?, ?, ?)', [$id, $t['tamanho'], $t['estoque'], $ordem + 1]);
            }
        }
        $pdo->commit();
    } catch (Throwable $erro) {
        $pdo->rollBack();
        throw $erro;
    }
    return $id;
}

/**
 * Salva as fotos enviadas em assets/img/produtos/. Devolve a lista de erros (fotos com erro são puladas).
 * A foto é conferida pelo conteúdo (getimagesize), não pelo nome, e ganha um nome novo e aleatório:
 * assim ninguém consegue mandar um arquivo .php disfarçado de foto.
 */
function salvar_fotos(int $produto_id, array $arquivos): array
{
    $erros = [];
    $ja_tem = (int) consulta('SELECT COUNT(*) FROM produto_imagens WHERE produto_id = ?', [$produto_id])->fetchColumn();
    $ordem = (int) consulta('SELECT COALESCE(MAX(ordem), 0) FROM produto_imagens WHERE produto_id = ?', [$produto_id])->fetchColumn();

    foreach ((array) ($arquivos['name'] ?? []) as $i => $nome) {
        $erro = $arquivos['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($erro === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $nome = mb_substr((string) $nome, 0, 60);
        if ($ja_tem >= FOTO_MAX_POR_PRODUTO) {
            $erros[] = "$nome não entrou: cada produto pode ter até " . FOTO_MAX_POR_PRODUTO . ' fotos.';
            continue;
        }
        if (in_array($erro, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || ($arquivos['size'][$i] ?? 0) > FOTO_MAX_BYTES) {
            $erros[] = "$nome é maior que 3 MB.";
            continue;
        }
        $temporario = $arquivos['tmp_name'][$i] ?? '';
        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($temporario)) {
            $erros[] = "$nome não chegou inteira. Tente de novo.";
            continue;
        }
        $info = @getimagesize($temporario);
        $extensao = $info ? (FOTO_TIPOS[$info[2]] ?? null) : null;
        if (!$extensao) {
            $erros[] = "$nome não é uma foto JPG, PNG ou WebP.";
            continue;
        }
        if (!is_dir(PASTA_FOTOS) || !is_writable(PASTA_FOTOS)) {
            $erros[] = 'A pasta assets/img/produtos não aceita arquivos. Confira as permissões dela.';
            break;
        }
        $arquivo = 'p' . $produto_id . '-' . bin2hex(random_bytes(6)) . '.' . $extensao;
        if (!move_uploaded_file($temporario, PASTA_FOTOS . $arquivo)) {
            $erros[] = "$nome não pôde ser salva.";
            continue;
        }
        consulta('INSERT INTO produto_imagens (produto_id, arquivo, ordem) VALUES (?, ?, ?)', [$produto_id, $arquivo, ++$ordem]);
        $ja_tem++;
    }
    return $erros;
}

/** Remove fotos do produto (do banco e da pasta). */
function remover_fotos(int $produto_id, array $ids): void
{
    foreach ($ids as $id) {
        $arquivo = consulta('SELECT arquivo FROM produto_imagens WHERE id = ? AND produto_id = ?', [(int) $id, $produto_id])->fetchColumn();
        if ($arquivo) {
            consulta('DELETE FROM produto_imagens WHERE id = ?', [(int) $id]);
            apagar_arquivo_foto($arquivo);
        }
    }
}

function apagar_arquivo_foto(string $arquivo): void
{
    $caminho = PASTA_FOTOS . basename($arquivo);
    if (is_file($caminho)) {
        @unlink($caminho);
    }
}

/** A foto escolhida vira a capa (primeira da galeria e a que aparece nos cards). */
function definir_capa(int $produto_id, int $imagem_id): void
{
    $ids = consulta('SELECT id FROM produto_imagens WHERE produto_id = ? ORDER BY ordem, id', [$produto_id])->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($imagem_id, array_map('intval', $ids), true)) {
        return;
    }
    $ordem = 2;
    foreach ($ids as $id) {
        consulta('UPDATE produto_imagens SET ordem = ? WHERE id = ?', [(int) $id === $imagem_id ? 1 : $ordem++, $id]);
    }
}

/** Apaga um produto que nunca foi vendido. Os vendidos só podem ser ocultados, para o histórico dos pedidos. */
function excluir_produto(int $id): ?string
{
    if (consulta('SELECT 1 FROM pedido_itens WHERE produto_id = ? LIMIT 1', [$id])->fetchColumn()) {
        return 'Este produto já foi vendido. Desligue "Aparece na loja" para tirá-lo do site.';
    }
    $fotos = consulta('SELECT arquivo FROM produto_imagens WHERE produto_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN);
    consulta('DELETE FROM produtos WHERE id = ?', [$id]); // tamanhos, fotos e favoritos saem junto (ON DELETE CASCADE)
    foreach ($fotos as $arquivo) {
        apagar_arquivo_foto($arquivo);
    }
    return null;
}

// ---------------------------------------------------------------
// Esportes e categorias
// ---------------------------------------------------------------

const CORES_ESPORTE = ['blue' => 'Azul', 'lime' => 'Verde-limão', 'dark' => 'Escuro', 'light' => 'Claro'];

/** Esportes com as categorias e o número de produtos de cada uma. */
function catalogo_admin(): array
{
    $esportes = consulta('SELECT * FROM esportes ORDER BY ordem, nome')->fetchAll();
    $categorias = consulta(
        'SELECT c.*, (SELECT COUNT(*) FROM produtos p WHERE p.categoria_id = c.id) AS total_produtos
           FROM categorias c ORDER BY c.ordem, c.nome'
    )->fetchAll();
    foreach ($esportes as &$esporte) {
        $esporte['categorias'] = array_values(array_filter($categorias, fn ($c) => (int) $c['esporte_id'] === (int) $esporte['id']));
    }
    return $esportes;
}

function validar_esporte(array $post): array
{
    $d = [
        'nome'      => mb_substr(trim((string) ($post['nome'] ?? '')), 0, 60),
        'descricao' => mb_substr(trim((string) ($post['descricao'] ?? '')), 0, 160),
        'cor'       => (string) ($post['cor'] ?? 'blue'),
        'ordem'     => max(0, min(999, (int) ($post['ordem'] ?? 0))),
        'ativo'     => !empty($post['ativo']) ? 1 : 0,
    ];
    if (!isset(CORES_ESPORTE[$d['cor']])) {
        $d['cor'] = 'blue';
    }
    $erros = mb_strlen($d['nome']) < 2 ? ['nome' => 'Digite o nome do esporte.'] : [];
    return [$d, $erros];
}

function salvar_esporte(array $d, ?int $id = null): int
{
    $campos = [$d['nome'], $d['descricao'] !== '' ? $d['descricao'] : null, $d['cor'], $d['ordem'], $d['ativo']];
    if ($id) {
        consulta('UPDATE esportes SET nome = ?, descricao = ?, cor = ?, ordem = ?, ativo = ? WHERE id = ?', array_merge($campos, [$id]));
        return $id;
    }
    // o endereço (slug) é criado uma vez e não muda, para não quebrar links já compartilhados
    consulta('INSERT INTO esportes (nome, descricao, cor, ordem, ativo, slug) VALUES (?, ?, ?, ?, ?, ?)', array_merge($campos, [slug_livre('esportes', $d['nome'])]));
    return (int) db()->lastInsertId();
}

function excluir_esporte(int $id): ?string
{
    if (consulta('SELECT 1 FROM categorias WHERE esporte_id = ? LIMIT 1', [$id])->fetchColumn()) {
        return 'Este esporte tem categorias. Exclua as categorias antes, ou desligue "Aparece na loja".';
    }
    consulta('DELETE FROM esportes WHERE id = ?', [$id]);
    return null;
}

function validar_categoria(array $post): array
{
    $d = [
        'esporte_id' => (int) ($post['esporte_id'] ?? 0),
        'nome'       => mb_substr(trim((string) ($post['nome'] ?? '')), 0, 60),
        'ordem'      => max(0, min(999, (int) ($post['ordem'] ?? 0))),
        'ativo'      => !empty($post['ativo']) ? 1 : 0,
    ];
    $erros = [];
    if (mb_strlen($d['nome']) < 2) {
        $erros['nome'] = 'Digite o nome da categoria.';
    }
    if (!$d['esporte_id'] || !consulta('SELECT 1 FROM esportes WHERE id = ?', [$d['esporte_id']])->fetchColumn()) {
        $erros['esporte_id'] = 'Escolha o esporte.';
    }
    return [$d, $erros];
}

function salvar_categoria(array $d, ?int $id = null): int
{
    if ($id) {
        $atual = consulta('SELECT esporte_id, slug FROM categorias WHERE id = ?', [$id])->fetch();
        // mudou de esporte: o slug precisa ser único no esporte novo
        $slug = (int) $atual['esporte_id'] === $d['esporte_id'] ? $atual['slug'] : slug_livre('categorias', $d['nome'], $d['esporte_id']);
        consulta(
            'UPDATE categorias SET esporte_id = ?, nome = ?, ordem = ?, ativo = ?, slug = ? WHERE id = ?',
            [$d['esporte_id'], $d['nome'], $d['ordem'], $d['ativo'], $slug, $id]
        );
        return $id;
    }
    consulta(
        'INSERT INTO categorias (esporte_id, nome, ordem, ativo, slug) VALUES (?, ?, ?, ?, ?)',
        [$d['esporte_id'], $d['nome'], $d['ordem'], $d['ativo'], slug_livre('categorias', $d['nome'], $d['esporte_id'])]
    );
    return (int) db()->lastInsertId();
}

function excluir_categoria(int $id): ?string
{
    if (consulta('SELECT 1 FROM produtos WHERE categoria_id = ? LIMIT 1', [$id])->fetchColumn()) {
        return 'Esta categoria tem produtos. Mude os produtos de categoria antes, ou desligue "Aparece na loja".';
    }
    consulta('DELETE FROM categorias WHERE id = ?', [$id]);
    return null;
}

// ---------------------------------------------------------------
// Pedaços de tela do painel
// ---------------------------------------------------------------

/** Caixa de seleção (<select>) com o mesmo visual dos campos de texto. $opcoes pode ter grupos (optgroup). */
function campo_selecao(string $nome, string $rotulo, array $opcoes, string|int|null $valor, ?string $erro = null, array $attrs = []): string
{
    $id = 'campo-' . preg_replace('/[^a-z0-9_-]/i', '-', $nome);
    $html_opcoes = '';
    $opcao = fn ($v, $t) => '<option value="' . e((string) $v) . '"' . ((string) $v === (string) $valor ? ' selected' : '') . '>' . e($t) . '</option>';
    foreach ($opcoes as $v => $t) {
        if (is_array($t)) {
            $html_opcoes .= '<optgroup label="' . e((string) $v) . '">';
            foreach ($t as $v2 => $t2) {
                $html_opcoes .= $opcao($v2, $t2);
            }
            $html_opcoes .= '</optgroup>';
        } else {
            $html_opcoes .= $opcao($v, $t);
        }
    }
    $extras = '';
    foreach ($attrs as $chave => $v) {
        $extras .= $v === true ? ' ' . e($chave) : ' ' . e($chave) . '="' . e((string) $v) . '"';
    }
    return '<div class="campo' . ($erro ? ' campo--erro' : '') . '">'
         . '<label class="campo__rotulo" for="' . e($id) . '">' . e($rotulo) . '</label>'
         . '<select class="campo__entrada campo__entrada--selecao" id="' . e($id) . '" name="' . e($nome) . '" aria-describedby="' . e($id) . '-ajuda"'
         . ($erro ? ' aria-invalid="true"' : '') . $extras . '>' . $html_opcoes . '</select>'
         . '<span class="campo__ajuda" id="' . e($id) . '-ajuda">' . e($erro ?? '') . '</span>'
         . '</div>';
}

/** Linha com um interruptor (liga/desliga). */
function campo_interruptor(string $nome, string $rotulo, bool $ligado, ?string $ajuda = null): string
{
    return '<label class="caixa linha linha--entre">'
         . '<span class="pilha-xs"><span class="t-callout">' . e($rotulo) . '</span>'
         . ($ajuda ? '<span class="t-footnote texto-secundario">' . e($ajuda) . '</span>' : '') . '</span>'
         . '<input type="checkbox" class="interruptor" name="' . e($nome) . '" value="1"' . ($ligado ? ' checked' : '') . '>'
         . '</label>';
}

/** Lista de erros no topo do formulário. */
function caixa_erros(array $erros, string $titulo = 'Confira os campos marcados.'): string
{
    if (!$erros) {
        return '';
    }
    return '<div class="caixa-erro t-subheadline" role="alert">' . icone('alerta', 'icone--sm')
         . ' <span>' . e($titulo) . '</span></div>';
}

/** Linha de pedido nas listas do painel: número, cliente, status, valor. */
function linha_pedido_admin(array $p): string
{
    $itens = (int) ($p['quantidade_itens'] ?? 0);
    return '<a class="linha-lista admin-linha-pedido" href="' . e(url('admin/pedido.php?numero=' . urlencode($p['numero']))) . '">'
         . '<span class="linha-lista__rotulo pilha-xs">'
         .   '<span class="linha"><span class="t-headline">#' . e($p['numero']) . '</span>' . selo_status($p['status']) . '</span>'
         .   '<span class="t-footnote texto-secundario">' . e($p['nome']) . ' · ' . e(data_hora_curta($p['criado_em'])) . '</span>'
         .   '<span class="t-footnote texto-secundario">' . $itens . ($itens === 1 ? ' item' : ' itens') . ' · '
         .     e(NOMES_ENTREGA[$p['entrega_tipo']]) . ' · ' . e(NOMES_PAGAMENTO[$p['pagamento_tipo']]) . '</span>'
         . '</span>'
         . '<span class="linha-lista__valor t-callout">' . dinheiro((float) $p['total']) . '</span>'
         . icone('seta-direita', 'icone--sm linha-lista__seta')
         . '</a>';
}
