<?php
/**
 * Configurações da loja.
 *
 * Os valores do banco abaixo são os padrões do XAMPP.
 * Para outra máquina ou para a InfinityFree, crie includes/config.local.php
 * (copie de config.local.exemplo.php). Esse arquivo não vai para o GitHub.
 */

$config = [
    // Banco de dados
    'db_host'    => 'localhost',
    'db_porta'   => 3306,
    'db_nome'    => 'triade',
    'db_usuario' => 'root',
    'db_senha'   => '',

    // Endereço do site. Deixe null para descobrir sozinho
    // (funciona tanto em http://localhost/Triade_esportes_web quanto na raiz de um domínio).
    'url_base' => null,

    // Mostrar erros detalhados na tela. Desligue na hospedagem.
    'modo_debug' => true,
];

if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_merge($config, require __DIR__ . '/config.local.php');
}

/*
 * Regras de negócio. Foram definidas no design e ainda não confirmadas pela Triade:
 * se a empresa mudar alguma, troque só aqui.
 */
const LOJA_NOME             = 'Triade Esporte';
const PIX_DESCONTO          = 0.05;   // 5% de desconto no Pix
const PIX_VALIDADE_MINUTOS  = 30;     // tempo para pagar o código Pix
const CARTAO_MAX_PARCELAS   = 6;      // até 6x sem juros
const CARTAO_PARCELA_MINIMA = 30.00;  // parcela mínima de R$ 30
const BOLETO_PRAZO_DIAS     = 2;      // aprovação em até 2 dias úteis
const FRETE_GRATIS_ACIMA    = 399.00; // PAC grátis acima deste valor
const TROCA_PRAZO_DIAS      = 30;

// Contatos (exemplos: trocar pelos reais quando a empresa enviar)
const CONTATO_WHATSAPP  = '5551999999999';
const CONTATO_EMAIL     = 'contato@triadeesporte.com.br';
const CONTATO_INSTAGRAM = 'triadeesporte';
const DEPOSITO_ENDERECO = 'Rua Exemplo, 123, Centro, São Leopoldo/RS';
