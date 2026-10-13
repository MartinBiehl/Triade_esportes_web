<?php
/**
 * Funções usadas em todas as páginas.
 */

// ---------------------------------------------------------------
// Endereços
// ---------------------------------------------------------------

/** Pasta do site em relação ao domínio. Ex.: "/Triade_esportes_web" no XAMPP, "" na raiz. */
function url_base(): string
{
    static $base = null;

    if ($base === null) {
        global $config;

        if ($config['url_base'] !== null) {
            $base = rtrim($config['url_base'], '/');
        } else {
            $raiz      = str_replace('\\', '/', realpath(__DIR__ . '/..'));
            $documento = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
            $base      = ($documento !== '' && stripos($raiz, $documento) === 0)
                ? substr($raiz, strlen($documento))
                : '';
            $base = rtrim($base, '/');
        }
    }

    return $base;
}

/** Monta um link interno. Ex.: url('produto.php?id=3') */
function url(string $caminho = ''): string
{
    return url_base() . '/' . ltrim($caminho, '/');
}

/** Link para arquivos de assets/ com a data de modificação, para o navegador não usar versão velha. */
function asset(string $caminho): string
{
    $arquivo = __DIR__ . '/../assets/' . ltrim($caminho, '/');
    $versao  = is_file($arquivo) ? filemtime($arquivo) : 0;
    return url('assets/' . ltrim($caminho, '/')) . '?v=' . $versao;
}

function redirecionar(string $caminho): never
{
    header('Location: ' . url($caminho));
    exit;
}

// ---------------------------------------------------------------
// Texto e valores
// ---------------------------------------------------------------

/** Escapa texto antes de mostrar no HTML. Use em TODO dado que vem do banco ou do usuário. */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 1234.5 -> "R$ 1.234,50" */
function dinheiro(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/** Preço com o desconto do Pix. */
function preco_pix(float $valor): float
{
    return round($valor * (1 - PIX_DESCONTO), 2);
}

/** Quantas parcelas sem juros cabem neste valor (respeitando a parcela mínima). */
function max_parcelas(float $valor): int
{
    $parcelas = (int) floor($valor / CARTAO_PARCELA_MINIMA);
    return max(1, min(CARTAO_MAX_PARCELAS, $parcelas));
}

/** Texto "6x de R$ 50,00 sem juros". */
function texto_parcelas(float $valor): string
{
    $n = max_parcelas($valor);
    if ($n === 1) {
        return 'à vista no cartão';
    }
    return $n . 'x de ' . dinheiro(round($valor / $n, 2)) . ' sem juros';
}

/** Deixa só os números. Útil para CPF, CEP e telefone. */
function so_numeros(?string $texto): string
{
    return preg_replace('/\D+/', '', $texto ?? '');
}

/** Confere os dígitos verificadores do CPF. */
function cpf_valido(string $cpf): bool
{
    $cpf = so_numeros($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$t] !== $digito) {
            return false;
        }
    }
    return true;
}

// ---------------------------------------------------------------
// Proteção de formulários (CSRF)
// ---------------------------------------------------------------

/** Código secreto da sessão, colocado em todo formulário. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Campo escondido para colocar dentro de <form>. */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Confere o código. Formulário sem o código certo é recusado. */
function csrf_validar(): void
{
    $enviado = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(403);
        exit('Sessão expirada. Volte e tente de novo.');
    }
}

// ---------------------------------------------------------------
// Avisos entre páginas (ex.: "Adicionado à sacola" depois de um redirecionamento)
// ---------------------------------------------------------------

function aviso_definir(string $texto, string $tipo = 'sucesso', ?array $acao = null): void
{
    $_SESSION['aviso'] = ['texto' => $texto, 'tipo' => $tipo, 'acao' => $acao];
}

/** Lê e apaga o aviso guardado. */
function aviso_obter(): ?array
{
    $aviso = $_SESSION['aviso'] ?? null;
    unset($_SESSION['aviso']);
    return $aviso;
}

/** O pedido veio do JavaScript (fetch) e espera JSON? */
function pedido_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

// ---------------------------------------------------------------
// Respostas para o JavaScript (pasta api/)
// ---------------------------------------------------------------

function responder_json(array $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}
