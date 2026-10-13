-- Triade Esporte: dados de exemplo.
-- Rode depois de 01_estrutura.sql. Produtos, preços e clientes são fictícios.
--
-- Contas de teste (senha das duas: triade123)
--   cliente: ana@exemplo.com
--   painel:  admin@triade.com.br  (o painel fica para uma etapa posterior)

SET NAMES utf8mb4;

-- ---------------------------------------------------------------
-- Esportes e categorias
-- ---------------------------------------------------------------

INSERT INTO esportes (id, nome, slug, descricao, cor, ordem) VALUES
  (1, 'Futebol',  'futebol',  'Camisas de times, chuteiras, bolas e acessórios', 'blue', 1),
  (2, 'Basquete', 'basquete', 'Camisas, tênis e bolas para quadra e rua',        'lime', 2);

INSERT INTO categorias (id, esporte_id, nome, slug, ordem) VALUES
  (1,  1, 'Camisas',    'camisas',    1),
  (2,  1, 'Chuteiras',  'chuteiras',  2),
  (3,  1, 'Bolas',      'bolas',      3),
  (4,  1, 'Calções',    'calcoes',    4),
  (5,  1, 'Meiões',     'meioes',     5),
  (6,  1, 'Caneleiras', 'caneleiras', 6),
  (7,  2, 'Camisas',    'camisas',    1),
  (8,  2, 'Tênis',      'tenis',      2),
  (9,  2, 'Bolas',      'bolas',      3),
  (10, 2, 'Calções',    'calcoes',    4);

-- ---------------------------------------------------------------
-- Produtos
-- ---------------------------------------------------------------

INSERT INTO produtos
  (id, categoria_id, nome, slug, descricao, time_nome, marca, preco, preco_promocional,
   oficial, lancamento, destaque, midia, guia_tamanhos)
VALUES
  -- Futebol: camisas
  (1, 1, 'Camisa Grêmio I 26/27', 'camisa-gremio-i-26-27',
   'Camisa titular do Grêmio para a temporada 26/27. Tecido leve com tecnologia de secagem rápida e escudo bordado.',
   'Grêmio', NULL, 299.90, NULL, 1, 1, 1, 'camisa', 'camisa'),
  (2, 1, 'Camisa Internacional I 26/27', 'camisa-internacional-i-26-27',
   'Camisa titular do Internacional para a temporada 26/27. Tecido leve com tecnologia de secagem rápida e escudo bordado.',
   'Internacional', NULL, 299.90, NULL, 1, 1, 1, 'camisa', 'camisa'),
  (3, 1, 'Camisa Juventude I 26/27', 'camisa-juventude-i-26-27',
   'Camisa titular do Juventude para a temporada 26/27. Modelo torcedor com corte regular.',
   'Juventude', NULL, 249.90, NULL, 1, 0, 0, 'camisa', 'camisa'),
  (4, 1, 'Camisa Seleção Brasileira I 2026', 'camisa-selecao-brasileira-i-2026',
   'Camisa amarela da Seleção para 2026. Modelo torcedor com escudo emborrachado.',
   'Seleção Brasileira', NULL, 349.90, 299.90, 1, 0, 1, 'camisa', 'camisa'),
  (5, 1, 'Camisa Grêmio II 26/27', 'camisa-gremio-ii-26-27',
   'Camisa reserva do Grêmio para a temporada 26/27, na cor branca.',
   'Grêmio', NULL, 299.90, NULL, 1, 0, 0, 'camisa', 'camisa'),
  (6, 1, 'Camisa Internacional II 26/27', 'camisa-internacional-ii-26-27',
   'Camisa reserva do Internacional para a temporada 26/27, na cor branca.',
   'Internacional', NULL, 299.90, 269.90, 1, 0, 0, 'camisa', 'camisa'),

  -- Futebol: chuteiras, bolas e acessórios
  (7, 2, 'Chuteira Campo Pro Speed', 'chuteira-campo-pro-speed',
   'Chuteira para gramado natural, cabedal sintético leve e travas cônicas para arranque rápido.',
   NULL, 'Triade Pro', 459.90, 367.92, 0, 0, 1, 'chuteira', 'calcado'),
  (8, 2, 'Chuteira Society Control', 'chuteira-society-control',
   'Chuteira para grama sintética com solado de borracha e travas baixas.',
   NULL, 'Triade Pro', 289.90, NULL, 0, 0, 0, 'chuteira', 'calcado'),
  (9, 2, 'Chuteira Futsal Street', 'chuteira-futsal-street',
   'Chuteira para quadra com solado de borracha que não risca o piso.',
   NULL, 'Triade Pro', 219.90, NULL, 0, 1, 0, 'chuteira', 'calcado'),
  (10, 3, 'Bola de Campo Pro Match', 'bola-de-campo-pro-match',
   'Bola tamanho 5 com gomos termossoldados, indicada para jogos em gramado natural.',
   NULL, 'Triade Pro', 199.90, NULL, 0, 0, 1, 'bola', NULL),
  (11, 3, 'Bola de Futsal Indoor', 'bola-de-futsal-indoor',
   'Bola de futsal com quique controlado, para quadras cobertas.',
   NULL, 'Triade Pro', 149.90, NULL, 0, 0, 0, 'bola', NULL),
  (12, 4, 'Calção de Treino Dry', 'calcao-de-treino-dry',
   'Calção leve com elástico na cintura e tecido que seca rápido.',
   NULL, 'Triade Pro', 89.90, NULL, 0, 0, 0, 'calcao', 'calcao'),
  (13, 5, 'Meião Performance', 'meiao-performance',
   'Meião com compressão leve e reforço no calcanhar e na ponta.',
   NULL, 'Triade Pro', 49.90, NULL, 0, 0, 0, 'meiao', 'meiao'),
  (14, 6, 'Caneleira Shield', 'caneleira-shield',
   'Caneleira com casco rígido e espuma de impacto, presa por tira de velcro.',
   NULL, 'Triade Pro', 69.90, NULL, 0, 0, 0, 'caneleira', NULL),

  -- Basquete
  (15, 7, 'Camisa Los Angeles Lakers Icon', 'camisa-los-angeles-lakers-icon',
   'Regata Icon do Los Angeles Lakers, tecido de malha respirável.',
   'Los Angeles Lakers', NULL, 399.90, NULL, 1, 1, 1, 'camisa', 'camisa'),
  (16, 7, 'Camisa Boston Celtics Icon', 'camisa-boston-celtics-icon',
   'Regata Icon do Boston Celtics, tecido de malha respirável.',
   'Boston Celtics', NULL, 399.90, NULL, 1, 0, 0, 'camisa', 'camisa'),
  (17, 7, 'Camisa Chicago Bulls Icon', 'camisa-chicago-bulls-icon',
   'Regata Icon do Chicago Bulls, tecido de malha respirável.',
   'Chicago Bulls', NULL, 399.90, 339.90, 1, 0, 0, 'camisa', 'camisa'),
  (18, 8, 'Tênis Court Elevate', 'tenis-court-elevate',
   'Tênis de cano médio com amortecimento no calcanhar e solado com tração para quadra.',
   NULL, 'Triade Pro', 599.90, NULL, 0, 0, 1, 'tenis', 'calcado'),
  (19, 8, 'Tênis Rebound Low', 'tenis-rebound-low',
   'Tênis de cano baixo, leve, para quem joga rápido e muda de direção.',
   NULL, 'Triade Pro', 429.90, 379.90, 0, 0, 0, 'tenis', 'calcado'),
  (20, 9, 'Bola de Basquete Street 7', 'bola-de-basquete-street-7',
   'Bola tamanho 7 de borracha resistente, feita para quadras externas.',
   NULL, 'Triade Pro', 179.90, NULL, 0, 0, 0, 'bola', NULL),
  (21, 9, 'Bola de Basquete Indoor Pro 7', 'bola-de-basquete-indoor-pro-7',
   'Bola tamanho 7 de couro sintético com ótima aderência, para quadras cobertas.',
   NULL, 'Triade Pro', 289.90, NULL, 0, 1, 0, 'bola', NULL),
  (22, 10, 'Calção de Basquete Mesh', 'calcao-de-basquete-mesh',
   'Calção longo de tela respirável com bolsos laterais.',
   NULL, 'Triade Pro', 119.90, NULL, 0, 0, 0, 'calcao', 'calcao');

-- ---------------------------------------------------------------
-- Tamanhos e estoque (estoque 0 = aparece riscado)
-- ---------------------------------------------------------------

INSERT INTO produto_tamanhos (produto_id, tamanho, estoque, ordem) VALUES
  -- Camisas de futebol
  (1, 'P', 8, 1),  (1, 'M', 12, 2), (1, 'G', 10, 3), (1, 'GG', 0, 4),
  (2, 'P', 6, 1),  (2, 'M', 10, 2), (2, 'G', 9, 3),  (2, 'GG', 4, 4),
  (3, 'P', 3, 1),  (3, 'M', 5, 2),  (3, 'G', 5, 3),  (3, 'GG', 2, 4),
  (4, 'P', 0, 1),  (4, 'M', 7, 2),  (4, 'G', 7, 3),  (4, 'GG', 3, 4),
  (5, 'P', 4, 1),  (5, 'M', 6, 2),  (5, 'G', 0, 3),  (5, 'GG', 2, 4),
  (6, 'P', 4, 1),  (6, 'M', 6, 2),  (6, 'G', 6, 3),  (6, 'GG', 2, 4),
  -- Chuteiras
  (7, '38', 2, 1), (7, '39', 4, 2), (7, '40', 5, 3), (7, '41', 5, 4), (7, '42', 3, 5), (7, '43', 0, 6),
  (8, '38', 3, 1), (8, '39', 3, 2), (8, '40', 4, 3), (8, '41', 4, 4), (8, '42', 2, 5), (8, '43', 1, 6),
  (9, '38', 2, 1), (9, '39', 2, 2), (9, '40', 3, 3), (9, '41', 0, 4), (9, '42', 2, 5), (9, '43', 1, 6),
  -- Bolas e caneleira
  (10, 'Único', 15, 1),
  (11, 'Único', 10, 1),
  (14, 'P', 5, 1), (14, 'M', 8, 2), (14, 'G', 4, 3),
  -- Calção e meião de futebol
  (12, 'P', 6, 1), (12, 'M', 10, 2), (12, 'G', 8, 3), (12, 'GG', 4, 4),
  (13, '34-38', 20, 1), (13, '39-43', 20, 2),
  -- Camisas de basquete
  (15, 'P', 3, 1), (15, 'M', 6, 2), (15, 'G', 6, 3), (15, 'GG', 3, 4),
  (16, 'P', 2, 1), (16, 'M', 4, 2), (16, 'G', 4, 3), (16, 'GG', 0, 4),
  (17, 'P', 2, 1), (17, 'M', 4, 2), (17, 'G', 3, 3), (17, 'GG', 2, 4),
  -- Tênis
  (18, '38', 1, 1), (18, '39', 2, 2), (18, '40', 3, 3), (18, '41', 3, 4), (18, '42', 2, 5), (18, '43', 2, 6),
  (19, '38', 2, 1), (19, '39', 2, 2), (19, '40', 0, 3), (19, '41', 3, 4), (19, '42', 3, 5), (19, '43', 1, 6),
  -- Bolas e calção de basquete
  (20, 'Único', 12, 1),
  (21, 'Único', 6, 1),
  (22, 'P', 4, 1), (22, 'M', 6, 2), (22, 'G', 6, 3), (22, 'GG', 3, 4);

-- ---------------------------------------------------------------
-- Cliente de teste, com endereço, favoritos e pedidos
-- ---------------------------------------------------------------

INSERT INTO clientes (id, nome, email, cpf, telefone, senha_hash) VALUES
  (1, 'Ana Souza', 'ana@exemplo.com', '12345678909', '(51) 99999-0000',
   '$2y$10$mx9ol2Bn4p39NxTnu1iaSOEtVxc3.NkaMaL.y7MxcWHb/sPjKN7nu');

INSERT INTO enderecos (cliente_id, apelido, destinatario, cep, logradouro, numero, complemento, bairro, cidade, uf, principal) VALUES
  (1, 'Casa', 'Ana Souza', '93010000', 'Rua Independência', '450', 'Apto 302', 'Centro', 'São Leopoldo', 'RS', 1);

INSERT INTO favoritos (cliente_id, produto_id) VALUES (1, 2), (1, 15), (1, 18);

-- Pedido de exemplo do design: Camisa Grêmio (M) + Chuteira Pro Speed (41), pago no Pix.
-- Subtotal 667,82 - 5% Pix (33,39) = 634,43
INSERT INTO pedidos
  (id, numero, cliente_id, nome, email, cpf, telefone, cep, logradouro, numero_end, complemento,
   bairro, cidade, uf, entrega_tipo, entrega_prazo, pagamento_tipo, parcelas,
   subtotal, frete, desconto, total, codigo_rastreio, status, criado_em)
VALUES
  (1, 'TR-20481', 1, 'Ana Souza', 'ana@exemplo.com', '12345678909', '(51) 99999-0000',
   '93010000', 'Rua Independência', '450', 'Apto 302', 'Centro', 'São Leopoldo', 'RS',
   'pac', 'Entre 20 e 23 de out.', 'pix', 1,
   667.82, 0.00, 33.39, 634.43, 'QB482915376BR', 'enviado', '2026-10-11 10:12:00'),
  (2, 'TR-19877', 1, 'Ana Souza', 'ana@exemplo.com', '12345678909', '(51) 99999-0000',
   NULL, NULL, NULL, NULL, NULL, NULL, NULL,
   'retirada', 'Pronto em 1 dia útil', 'cartao', 2,
   199.90, 0.00, 0.00, 199.90, NULL, 'entregue', '2026-09-02 16:40:00');

INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, tamanho, quantidade, preco_unitario) VALUES
  (1, 1,  'Camisa Grêmio I 26/27',    'M',     1, 299.90),
  (1, 7,  'Chuteira Campo Pro Speed', '41',    1, 367.92),
  (2, 10, 'Bola de Campo Pro Match',  'Único', 1, 199.90);

INSERT INTO pedido_historico (pedido_id, status, descricao, criado_em) VALUES
  (1, 'aguardando_pagamento', 'Pedido recebido',                       '2026-10-11 10:12:00'),
  (1, 'pago',                 'Pagamento Pix confirmado',              '2026-10-11 10:13:00'),
  (1, 'em_separacao',         'Pedido em separação no depósito',       '2026-10-11 14:00:00'),
  (1, 'enviado',              'Postado nos Correios (PAC)',            '2026-10-12 09:30:00'),
  (2, 'aguardando_pagamento', 'Pedido recebido',                       '2026-09-02 16:40:00'),
  (2, 'pago',                 'Pagamento no cartão aprovado',          '2026-09-02 16:41:00'),
  (2, 'pronto_retirada',      'Pronto para retirada em São Leopoldo',  '2026-09-03 11:00:00'),
  (2, 'entregue',             'Retirado no depósito',                  '2026-09-04 15:20:00');

-- ---------------------------------------------------------------
-- Administrador de teste (para o painel, que vem depois)
-- ---------------------------------------------------------------

INSERT INTO administradores (nome, email, senha_hash) VALUES
  ('Administrador Triade', 'admin@triade.com.br',
   '$2y$10$mx9ol2Bn4p39NxTnu1iaSOEtVxc3.NkaMaL.y7MxcWHb/sPjKN7nu');
