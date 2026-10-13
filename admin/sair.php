<?php
/**
 * Painel da loja · Sair (só por formulário POST, para um link não deslogar ninguém)
 */
require __DIR__ . '/../includes/admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    admin_sair();
    aviso_definir('Você saiu do painel');
}
redirecionar('admin/entrar.php');
