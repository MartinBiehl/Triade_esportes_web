<?php
/**
 * Sair da conta (só por POST, para um link externo não conseguir deslogar o cliente).
 */
require __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    sair();
    aviso_definir('Você saiu da conta');
}
redirecionar('conta/');
