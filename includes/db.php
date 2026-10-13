<?php
/**
 * Conexão com o MySQL usando PDO.
 *
 * Uso: db()->prepare('SELECT ... WHERE id = ?')->execute([$id]);
 * Sempre passe os valores pelo execute(), nunca colados no texto do SQL:
 * é isso que impede invasão por SQL injection.
 */

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        global $config;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['db_host'],
            $config['db_porta'],
            $config['db_nome']
        );

        $pdo = new PDO($dsn, $config['db_usuario'], $config['db_senha'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}

/** Executa uma consulta com parâmetros e devolve o resultado pronto para ler. */
function consulta(string $sql, array $parametros = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($parametros);
    return $stmt;
}
