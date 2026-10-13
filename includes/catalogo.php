<?php
/**
 * Consultas do catálogo: esportes, categorias, produtos, busca e filtros.
 */

/** Campos padrão de um produto para cards (com estoque total e primeira foto). */
const SQL_PRODUTO_CARD = '
    SELECT p.*,
           c.nome AS categoria_nome, c.slug AS categoria_slug,
           e.nome AS esporte_nome, e.slug AS esporte_slug,
           COALESCE(p.preco_promocional, p.preco) AS preco_final,
           (SELECT COALESCE(SUM(t.estoque), 0) FROM produto_tamanhos t WHERE t.produto_id = p.id) AS estoque_total,
           (SELECT i.arquivo FROM produto_imagens i WHERE i.produto_id = p.id ORDER BY i.ordem LIMIT 1) AS imagem
      FROM produtos p
      JOIN categorias c ON c.id = p.categoria_id AND c.ativo = 1
      JOIN esportes e   ON e.id = c.esporte_id   AND e.ativo = 1
';

/** Esportes ativos com o número de produtos e as categorias de cada um. */
function esportes_com_categorias(): array
{
    $esportes = consulta(
        'SELECT e.*, COUNT(p.id) AS total_produtos
           FROM esportes e
           LEFT JOIN categorias c ON c.esporte_id = e.id AND c.ativo = 1
           LEFT JOIN produtos p   ON p.categoria_id = c.id AND p.ativo = 1
          WHERE e.ativo = 1
          GROUP BY e.id
          ORDER BY e.ordem, e.nome'
    )->fetchAll();

    $categorias = consulta(
        'SELECT c.*, COUNT(p.id) AS total_produtos,
                (SELECT p2.midia FROM produtos p2 WHERE p2.categoria_id = c.id AND p2.ativo = 1 ORDER BY p2.id LIMIT 1) AS midia
           FROM categorias c
           LEFT JOIN produtos p ON p.categoria_id = c.id AND p.ativo = 1
          WHERE c.ativo = 1
          GROUP BY c.id
          ORDER BY c.ordem, c.nome'
    )->fetchAll();

    foreach ($esportes as &$esporte) {
        $esporte['categorias'] = array_values(array_filter(
            $categorias,
            fn ($c) => (int) $c['esporte_id'] === (int) $esporte['id'] && $c['total_produtos'] > 0
        ));
    }
    return $esportes;
}

function esporte_por_slug(string $slug): ?array
{
    return consulta('SELECT * FROM esportes WHERE slug = ? AND ativo = 1', [$slug])->fetch() ?: null;
}

function categoria_por_slug(int $esporte_id, string $slug): ?array
{
    return consulta('SELECT * FROM categorias WHERE esporte_id = ? AND slug = ? AND ativo = 1', [$esporte_id, $slug])->fetch() ?: null;
}

function categorias_do_esporte(int $esporte_id): array
{
    return consulta(
        'SELECT c.* FROM categorias c
          WHERE c.esporte_id = ? AND c.ativo = 1
            AND EXISTS (SELECT 1 FROM produtos p WHERE p.categoria_id = c.id AND p.ativo = 1)
          ORDER BY c.ordem, c.nome',
        [$esporte_id]
    )->fetchAll();
}

// ---------------------------------------------------------------
// Listas para o Início
// ---------------------------------------------------------------

function produtos_destaque(int $limite = 8): array
{
    return consulta(SQL_PRODUTO_CARD . ' WHERE p.ativo = 1 AND p.destaque = 1 ORDER BY p.id LIMIT ' . (int) $limite)->fetchAll();
}

function produtos_lancamentos(int $limite = 8): array
{
    return consulta(SQL_PRODUTO_CARD . ' WHERE p.ativo = 1 AND p.lancamento = 1 ORDER BY p.criado_em DESC, p.id DESC LIMIT ' . (int) $limite)->fetchAll();
}

function produtos_promocao(int $limite = 8): array
{
    return consulta(SQL_PRODUTO_CARD . ' WHERE p.ativo = 1 AND p.preco_promocional IS NOT NULL
        ORDER BY (p.preco - p.preco_promocional) / p.preco DESC LIMIT ' . (int) $limite)->fetchAll();
}

/** Outros produtos da mesma categoria (ou do mesmo esporte, se faltar). */
function produtos_relacionados(array $produto, int $limite = 6): array
{
    return consulta(
        SQL_PRODUTO_CARD . ' WHERE p.ativo = 1 AND p.id <> ? AND e.id = ?
          ORDER BY (p.categoria_id = ?) DESC, p.destaque DESC, p.id LIMIT ' . (int) $limite,
        [$produto['id'], $produto['esporte_id'], $produto['categoria_id']]
    )->fetchAll();
}

// ---------------------------------------------------------------
// Produto
// ---------------------------------------------------------------

/** Produto completo para a página do produto, ou null se não existe/está inativo. */
function produto_por_id(int $id): ?array
{
    $produto = consulta(
        str_replace('SELECT p.*,', 'SELECT p.*, e.id AS esporte_id,', SQL_PRODUTO_CARD) . ' WHERE p.id = ? AND p.ativo = 1',
        [$id]
    )->fetch();
    if (!$produto) {
        return null;
    }
    $produto['tamanhos'] = consulta(
        'SELECT tamanho, estoque FROM produto_tamanhos WHERE produto_id = ? ORDER BY ordem, id',
        [$id]
    )->fetchAll();
    $produto['imagens'] = consulta(
        'SELECT arquivo FROM produto_imagens WHERE produto_id = ? ORDER BY ordem, id',
        [$id]
    )->fetchAll(PDO::FETCH_COLUMN);
    return $produto;
}

/** Tabelas do guia de tamanhos (tela 08). Medidas aproximadas, em cm. */
function guia_de_tamanhos(?string $tipo): ?array
{
    $guias = [
        'camisa' => [
            'titulo'  => 'Camisas',
            'dica'    => 'Meça uma camisa que você já usa, deitada, de axila a axila (largura) e do ombro até a barra (comprimento).',
            'colunas' => ['Tam.', 'Largura', 'Compr.', 'Altura (m)'],
            'linhas'  => [
                ['P',  '50', '70', '1,60–1,70'],
                ['M',  '53', '72', '1,68–1,76'],
                ['G',  '56', '74', '1,74–1,82'],
                ['GG', '59', '76', '1,80–1,90'],
            ],
        ],
        'calcado' => [
            'titulo'  => 'Chuteiras e tênis',
            'dica'    => 'Fique em pé sobre uma folha, marque o calcanhar e a ponta do dedo maior e meça. Entre dois números, escolha o maior.',
            'colunas' => ['Número', 'Pé (cm)'],
            'linhas'  => [
                ['38', '24,5'], ['39', '25,3'], ['40', '26,0'],
                ['41', '26,7'], ['42', '27,4'], ['43', '28,0'],
            ],
        ],
        'calcao' => [
            'titulo'  => 'Calções',
            'dica'    => 'Meça a cintura na altura do umbigo, sem apertar a fita.',
            'colunas' => ['Tamanho', 'Cintura (cm)'],
            'linhas'  => [['P', '68 a 76'], ['M', '76 a 84'], ['G', '84 a 92'], ['GG', '92 a 100']],
        ],
        'meiao' => [
            'titulo'  => 'Meiões',
            'dica'    => 'O tamanho do meião acompanha o número do calçado.',
            'colunas' => ['Tamanho', 'Calçado'],
            'linhas'  => [['34-38', '34 ao 38'], ['39-43', '39 ao 43']],
        ],
    ];
    return $guias[$tipo] ?? null;
}

// ---------------------------------------------------------------
// Busca e filtros (telas 04 e 05)
// ---------------------------------------------------------------

const FAIXAS_PRECO = [
    'ate-100'  => ['Até R$ 100',          0,   100],
    '100-300'  => ['R$ 100 a R$ 300',     100, 300],
    '300-500'  => ['R$ 300 a R$ 500',     300, 500],
    'acima-500' => ['Acima de R$ 500',    500, null],
];

const ORDENACOES = [
    'relevancia'  => 'Mais relevantes',
    'menor-preco' => 'Menor preço',
    'maior-preco' => 'Maior preço',
    'novidades'   => 'Novidades',
];

/** Lê os filtros da URL e devolve só valores válidos. */
function filtros_da_url(array $get): array
{
    $lista = fn ($valor) => array_values(array_filter(
        array_map('strval', is_array($valor) ? $valor : ($valor === null || $valor === '' ? [] : [$valor])),
        fn ($v) => $v !== '' && mb_strlen($v) <= 60
    ));

    return [
        'q'         => trim(mb_substr((string) ($get['q'] ?? ''), 0, 80)),
        'esporte'   => (string) ($get['esporte'] ?? ''),
        'categoria' => (string) ($get['categoria'] ?? ''),
        'tamanho'   => $lista($get['tamanho'] ?? []),
        'time'      => $lista($get['time'] ?? []),
        'preco'     => array_key_exists($get['preco'] ?? '', FAIXAS_PRECO) ? $get['preco'] : '',
        'oficial'   => !empty($get['oficial']),
        'promo'     => !empty($get['promo']),
        'ordem'     => array_key_exists($get['ordem'] ?? '', ORDENACOES) ? $get['ordem'] : 'relevancia',
    ];
}

/** Quantos filtros do painel estão ligados (número no botão "Filtros"). */
function total_filtros_ativos(array $f): int
{
    return count($f['tamanho']) + count($f['time']) + ($f['preco'] ? 1 : 0)
         + ($f['oficial'] ? 1 : 0) + ($f['promo'] ? 1 : 0) + ($f['ordem'] !== 'relevancia' ? 1 : 0);
}

/**
 * Monta o WHERE da busca. $ignorar deixa de fora um filtro
 * (usado para listar as opções de um filtro sem que ele mesmo as esconda).
 */
function where_da_busca(array $f, array $ignorar = []): array
{
    $sql = ['p.ativo = 1'];
    $params = [];

    if ($f['esporte'] !== '') {
        $sql[] = 'e.slug = ?';
        $params[] = $f['esporte'];
    }
    if ($f['categoria'] !== '') {
        $sql[] = 'c.slug = ?';
        $params[] = $f['categoria'];
    }
    if ($f['q'] !== '') {
        // cada palavra precisa aparecer em algum lugar (nome, time, marca, categoria ou esporte)
        foreach (preg_split('/\s+/', $f['q']) as $palavra) {
            $termo = '%' . addcslashes($palavra, '%_\\') . '%';
            $sql[] = '(p.nome LIKE ? OR p.time_nome LIKE ? OR p.marca LIKE ? OR c.nome LIKE ? OR e.nome LIKE ?)';
            array_push($params, $termo, $termo, $termo, $termo, $termo);
        }
    }
    if ($f['tamanho'] && !in_array('tamanho', $ignorar, true)) {
        $marcas = implode(',', array_fill(0, count($f['tamanho']), '?'));
        $sql[] = "EXISTS (SELECT 1 FROM produto_tamanhos t WHERE t.produto_id = p.id AND t.estoque > 0 AND t.tamanho IN ($marcas))";
        array_push($params, ...$f['tamanho']);
    }
    if ($f['time'] && !in_array('time', $ignorar, true)) {
        $marcas = implode(',', array_fill(0, count($f['time']), '?'));
        $sql[] = "p.time_nome IN ($marcas)";
        array_push($params, ...$f['time']);
    }
    if ($f['preco'] && !in_array('preco', $ignorar, true)) {
        [, $min, $max] = FAIXAS_PRECO[$f['preco']];
        $sql[] = 'COALESCE(p.preco_promocional, p.preco) >= ?';
        $params[] = $min;
        if ($max !== null) {
            $sql[] = 'COALESCE(p.preco_promocional, p.preco) < ?';
            $params[] = $max;
        }
    }
    if ($f['oficial']) {
        $sql[] = 'p.oficial = 1';
    }
    if ($f['promo']) {
        $sql[] = 'p.preco_promocional IS NOT NULL';
    }

    return [implode(' AND ', $sql), $params];
}

function buscar_produtos(array $f): array
{
    [$where, $params] = where_da_busca($f);

    $ordem = match ($f['ordem']) {
        'menor-preco' => 'preco_final ASC, p.id',
        'maior-preco' => 'preco_final DESC, p.id',
        'novidades'   => 'p.lancamento DESC, p.criado_em DESC, p.id DESC',
        default       => 'p.destaque DESC, p.oficial DESC, p.id',
    };

    // produtos esgotados vão para o fim da lista
    return consulta(
        'SELECT * FROM (' . SQL_PRODUTO_CARD . " WHERE $where) AS p ORDER BY (p.estoque_total = 0), " . $ordem,
        $params
    )->fetchAll();
}

/** Opções que fazem sentido no painel de filtros para a busca atual. */
function opcoes_de_filtro(array $f): array
{
    [$where, $params] = where_da_busca($f, ['tamanho']);
    $tamanhos = consulta(
        "SELECT DISTINCT t.tamanho
           FROM produto_tamanhos t
           JOIN produtos p   ON p.id = t.produto_id
           JOIN categorias c ON c.id = p.categoria_id
           JOIN esportes e   ON e.id = c.esporte_id
          WHERE $where AND t.estoque > 0",
        $params
    )->fetchAll(PDO::FETCH_COLUMN);
    usort($tamanhos, 'comparar_tamanhos');

    [$where, $params] = where_da_busca($f, ['time']);
    $times = consulta(
        "SELECT DISTINCT p.time_nome
           FROM produtos p
           JOIN categorias c ON c.id = p.categoria_id
           JOIN esportes e   ON e.id = c.esporte_id
          WHERE $where AND p.time_nome IS NOT NULL
          ORDER BY p.time_nome",
        $params
    )->fetchAll(PDO::FETCH_COLUMN);

    return ['tamanhos' => $tamanhos, 'times' => $times];
}

/** Ordem natural dos tamanhos: PP, P, M, G, GG, XG, depois Único, depois números (34-38, 38, 39...). */
function comparar_tamanhos(string $a, string $b): int
{
    $letras = ['PP', 'P', 'M', 'G', 'GG', 'XG', 'XGG', 'Único'];
    $peso = function (string $t) use ($letras): array {
        $i = array_search($t, $letras, true);
        if ($i !== false) {
            return [0, $i];
        }
        return [1, (float) $t]; // "34-38" vira 34
    };
    return $peso($a) <=> $peso($b);
}

/** Monta a URL de resultados mudando só alguns filtros. */
function url_resultados(array $f, array $mudar = []): string
{
    $f = array_merge($f, $mudar);
    $query = array_filter([
        'q'         => $f['q'] ?? '',
        'esporte'   => $f['esporte'] ?? '',
        'categoria' => $f['categoria'] ?? '',
        'tamanho'   => $f['tamanho'] ?? [],
        'time'      => $f['time'] ?? [],
        'preco'     => $f['preco'] ?? '',
        'oficial'   => !empty($f['oficial']) ? 1 : '',
        'promo'     => !empty($f['promo']) ? 1 : '',
        'ordem'     => ($f['ordem'] ?? 'relevancia') !== 'relevancia' ? $f['ordem'] : '',
    ], fn ($v) => $v !== '' && $v !== []);

    return url('resultados.php' . ($query ? '?' . http_build_query($query) : ''));
}
