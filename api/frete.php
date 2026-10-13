<?php
/**
 * Calcula o frete pelo CEP (botão "Calcular" da página do produto, com JavaScript).
 *   cep    8 números
 *   valor  preço do produto, para saber se o PAC sai grátis
 * Devolve JSON: { ok, html } ou { ok: false, mensagem }.
 */
require __DIR__ . '/../includes/bootstrap.php';

[$html, $erro] = frete_cep_html((string) ($_GET['cep'] ?? ''), max(0, (float) ($_GET['valor'] ?? 0)));
responder_json($erro ? ['ok' => false, 'mensagem' => $erro] : ['ok' => true, 'html' => $html], $erro ? 422 : 200);
