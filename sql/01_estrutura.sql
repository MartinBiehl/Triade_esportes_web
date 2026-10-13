-- Triade Esporte: estrutura do banco de dados.
-- Rode com o banco já selecionado (no phpMyAdmin, clique no banco antes de importar).
-- Apaga e recria todas as tabelas, então use só na instalação.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS administradores;
DROP TABLE IF EXISTS recuperacao_senha;
DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS pedido_historico;
DROP TABLE IF EXISTS pedido_itens;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS enderecos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS produto_tamanhos;
DROP TABLE IF EXISTS produto_imagens;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS esportes;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------
-- Catálogo: Esporte > Categoria > Produto
-- ---------------------------------------------------------------

CREATE TABLE esportes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(60)  NOT NULL,
  slug       VARCHAR(60)  NOT NULL UNIQUE,
  descricao  VARCHAR(160) NULL,
  cor        ENUM('blue', 'lime', 'dark', 'light') NOT NULL DEFAULT 'blue', -- cor do Category Tile
  ordem      INT          NOT NULL DEFAULT 0,
  ativo      TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  esporte_id INT UNSIGNED NOT NULL,
  nome       VARCHAR(60)  NOT NULL,
  slug       VARCHAR(60)  NOT NULL,
  ordem      INT          NOT NULL DEFAULT 0,
  ativo      TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY uk_categoria_slug (esporte_id, slug),
  CONSTRAINT fk_categoria_esporte FOREIGN KEY (esporte_id) REFERENCES esportes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE produtos (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id      INT UNSIGNED  NOT NULL,
  nome              VARCHAR(120)  NOT NULL,
  slug              VARCHAR(140)  NOT NULL UNIQUE,
  descricao         TEXT          NULL,
  time_nome         VARCHAR(60)   NULL,      -- usado no filtro "Time"
  marca             VARCHAR(60)   NULL,
  preco             DECIMAL(10,2) NOT NULL,
  preco_promocional DECIMAL(10,2) NULL,      -- preenchido = selo "Promo"
  oficial           TINYINT(1)    NOT NULL DEFAULT 0, -- selo "Oficial"
  lancamento        TINYINT(1)    NOT NULL DEFAULT 0, -- selo "Novo"
  destaque          TINYINT(1)    NOT NULL DEFAULT 0, -- aparece no Início
  midia             ENUM('camisa', 'bola', 'chuteira', 'tenis', 'calcao', 'meiao', 'caneleira')
                    NOT NULL DEFAULT 'camisa', -- silhueta provisória enquanto não há foto
  guia_tamanhos     ENUM('camisa', 'calcado', 'calcao', 'meiao') NULL, -- qual tabela mostrar no guia
  ativo             TINYINT(1)    NOT NULL DEFAULT 1,
  criado_em         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_produto_categoria (categoria_id),
  CONSTRAINT fk_produto_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE produto_imagens (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produto_id INT UNSIGNED NOT NULL,
  arquivo    VARCHAR(200) NOT NULL, -- caminho dentro de assets/img/produtos/
  ordem      INT          NOT NULL DEFAULT 0,
  CONSTRAINT fk_imagem_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Estoque por tamanho. Estoque 0 aparece riscado (Size Option "Unavailable").
CREATE TABLE produto_tamanhos (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produto_id INT UNSIGNED NOT NULL,
  tamanho    VARCHAR(10)  NOT NULL,
  estoque    INT UNSIGNED NOT NULL DEFAULT 0,
  ordem      INT          NOT NULL DEFAULT 0,
  UNIQUE KEY uk_produto_tamanho (produto_id, tamanho),
  CONSTRAINT fk_tamanho_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Clientes
-- ---------------------------------------------------------------

CREATE TABLE clientes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(120) NOT NULL,
  email      VARCHAR(160) NOT NULL UNIQUE,
  cpf        CHAR(11)     NULL,     -- só números
  telefone   VARCHAR(20)  NULL,
  senha_hash VARCHAR(255) NOT NULL, -- password_hash() do PHP
  criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enderecos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id   INT UNSIGNED NOT NULL,
  apelido      VARCHAR(40)  NULL, -- "Casa", "Trabalho"
  destinatario VARCHAR(120) NOT NULL,
  cep          CHAR(8)      NOT NULL,
  logradouro   VARCHAR(160) NOT NULL,
  numero       VARCHAR(20)  NOT NULL,
  complemento  VARCHAR(80)  NULL,
  bairro       VARCHAR(80)  NOT NULL,
  cidade       VARCHAR(80)  NOT NULL,
  uf           CHAR(2)      NOT NULL DEFAULT 'RS',
  principal    TINYINT(1)   NOT NULL DEFAULT 0,
  CONSTRAINT fk_endereco_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favoritos (
  cliente_id INT UNSIGNED NOT NULL,
  produto_id INT UNSIGNED NOT NULL,
  criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cliente_id, produto_id),
  CONSTRAINT fk_favorito_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE,
  CONSTRAINT fk_favorito_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Código de troca de senha. Guardamos só o hash do código, nunca o código em si.
CREATE TABLE recuperacao_senha (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64)     NOT NULL UNIQUE, -- sha256 do código
  expira_em  DATETIME     NOT NULL,
  usado_em   DATETIME     NULL,
  CONSTRAINT fk_recuperacao_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Pedidos
-- ---------------------------------------------------------------

-- Os dados do comprador e do endereço são copiados para o pedido,
-- para funcionar na compra sem conta e não mudar se o cliente editar o cadastro depois.
CREATE TABLE pedidos (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numero         VARCHAR(12)   NOT NULL UNIQUE, -- ex.: TR-20481
  cliente_id     INT UNSIGNED  NULL,            -- NULL = compra sem conta
  nome           VARCHAR(120)  NOT NULL,
  email          VARCHAR(160)  NOT NULL,
  cpf            CHAR(11)      NOT NULL,
  telefone       VARCHAR(20)   NOT NULL,
  cep            CHAR(8)       NULL,            -- NULL quando é retirada
  logradouro     VARCHAR(160)  NULL,
  numero_end     VARCHAR(20)   NULL,
  complemento    VARCHAR(80)   NULL,
  bairro         VARCHAR(80)   NULL,
  cidade         VARCHAR(80)   NULL,
  uf             CHAR(2)       NULL,
  entrega_tipo   ENUM('pac', 'sedex', 'transportadora', 'retirada') NOT NULL,
  entrega_prazo  VARCHAR(60)   NULL,            -- texto mostrado ao cliente
  pagamento_tipo ENUM('pix', 'cartao', 'boleto') NOT NULL,
  parcelas       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  subtotal       DECIMAL(10,2) NOT NULL,
  frete          DECIMAL(10,2) NOT NULL DEFAULT 0,
  desconto       DECIMAL(10,2) NOT NULL DEFAULT 0,
  total          DECIMAL(10,2) NOT NULL,
  status         ENUM('aguardando_pagamento', 'pagamento_recusado', 'pago', 'em_separacao',
                      'enviado', 'pronto_retirada', 'entregue', 'cancelado')
                 NOT NULL DEFAULT 'aguardando_pagamento',
  criado_em      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pedido_cliente (cliente_id),
  CONSTRAINT fk_pedido_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nome e preço são copiados no momento da compra.
CREATE TABLE pedido_itens (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id      INT UNSIGNED  NOT NULL,
  produto_id     INT UNSIGNED  NULL,
  nome_produto   VARCHAR(120)  NOT NULL,
  tamanho        VARCHAR(10)   NULL,
  quantidade     INT UNSIGNED  NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_item_pedido  FOREIGN KEY (pedido_id)  REFERENCES pedidos (id) ON DELETE CASCADE,
  CONSTRAINT fk_item_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Linha do tempo do pedido (tela 24, rastreio).
CREATE TABLE pedido_historico (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  status    VARCHAR(30)  NOT NULL,
  descricao VARCHAR(200) NOT NULL,
  criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_historico_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Painel da loja (o painel em si fica para uma etapa posterior)
-- ---------------------------------------------------------------

CREATE TABLE administradores (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome       VARCHAR(120) NOT NULL,
  email      VARCHAR(160) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
