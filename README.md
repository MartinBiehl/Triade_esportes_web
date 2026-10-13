# Triade Esporte · loja online

E-commerce mobile da Triade Esporte, loja de materiais esportivos de São Leopoldo/RS.
Trabalho acadêmico feito com **HTML5, CSS3, JavaScript puro, PHP 8 e MySQL**.

- Design: [Figma · Triade Esporte App Mobile](https://www.figma.com/design/fBpNCyeZ07WWK659mNd6yV)
- Situação: **etapa 7 (painel da loja)**. Já funcionam o painel da loja (pedidos, produtos, estoque e catálogo), o catálogo, a Sacola, a compra completa (Pix, cartão e boleto simulados), a conta do cliente, as páginas de Trocas e Sobre, a tela Sem internet, o carregamento com esqueleto e o site instalável no celular, além do frete por região do CEP.
- Guia de estilo com todos os componentes: `guia-de-estilo.php`

---

## Como rodar no seu computador (XAMPP)

O XAMPP é um programa gratuito que instala no seu computador tudo o que o site precisa: o Apache (servidor que roda o PHP), o PHP 8 e o MySQL.

### 1. Instalar o XAMPP

1. Baixe em <https://www.apachefriends.org/pt_br/download.html> a versão para Windows com **PHP 8.2 ou mais novo**.
2. Instale com as opções padrão (pasta `C:\xampp`).
3. Abra o **XAMPP Control Panel** e clique em **Start** em **Apache** e em **MySQL**. Os dois devem ficar verdes.

### 2. Colocar o projeto na pasta do XAMPP

O site precisa ficar dentro de `C:\xampp\htdocs`. Pelo terminal (ou pelo GitHub Desktop):

```bash
cd C:\xampp\htdocs
git clone https://github.com/MartinBiehl/Triade_esportes_web.git
```

Fica assim: `C:\xampp\htdocs\Triade_esportes_web\index.php`.

### 3. Criar o banco de dados

1. Abra <http://localhost/phpmyadmin>.
2. Clique em **Importar**, escolha `sql/00_criar_banco.sql` e clique em **Importar** no fim da página. Isso cria o banco `triade`.
3. Clique no banco **triade** na lista da esquerda.
4. Com ele selecionado, importe `sql/01_estrutura.sql` (cria as tabelas).
5. Ainda com ele selecionado, importe `sql/02_dados.sql` (produtos e contas de teste).

> Para voltar o banco ao estado inicial a qualquer momento, importe de novo o `01` e o `02`. O `01` apaga tudo e recria.

### 4. Abrir o site

Acesse <http://localhost/Triade_esportes_web>.
Deve abrir a tela Início da loja. Se aparecer **"Banco de dados não conectado"**, confira se o MySQL está ligado e se os scripts foram importados.

Para ver como fica no celular: no Chrome, aperte **F12** e depois **Ctrl+Shift+M**, e escolha um iPhone ou Android no topo.

### Contas de teste

| Tipo | E-mail | Senha |
|---|---|---|
| Cliente | `ana@exemplo.com` | `triade123` |
| Painel da loja (<http://localhost/Triade_esportes_web/admin>) | `admin@triade.com.br` | `triade123` |

### Pagamento de teste (tudo simulado, nada é cobrado)

| Forma | O que acontece |
|---|---|
| Pix | Mostra um QR Code e um código de exemplo. O botão **Simular pagamento aprovado** confirma o pedido. |
| Cartão `4111 1111 1111 1111` | Aprovado. Qualquer nome, validade futura e CVV de 3 números. |
| Cartão terminado em `0002` (ex.: `4000 0000 0000 0002`) | Recusado (tela 30). O pedido não é criado e o estoque não muda. |
| Boleto | Pedido criado aguardando pagamento. |

### Conta de teste

Entre com `ana@exemplo.com` / `triade123`. Ela já tem 2 pedidos (um a caminho, com código de rastreio), 1 endereço e favoritos.
O "Esqueci minha senha" não manda e-mail de verdade: o link para criar a senha nova aparece na própria tela, marcado como simulação.

### Painel da loja

Abra `/admin` (ex.: <http://localhost/Triade_esportes_web/admin>) e entre com `admin@triade.com.br` / `triade123`. O login é separado da conta de cliente: a conta da Ana não entra no painel, e entrar no painel não entra na loja como cliente.

| Aba | O que faz |
|---|---|
| Painel | Pedidos para separar, aguardando pagamento, pedidos do dia, vendas pagas do mês e tamanhos com estoque acabando (2 peças ou menos). |
| Pedidos | Lista com filtros e busca por número, nome ou e-mail. No pedido, o botão do próximo passo: confirmar pagamento, começar a separar, marcar como enviado (pede o código de rastreio), avisar que está pronto para retirar e marcar como entregue. Cada passo aparece no rastreio do cliente. |
| Produtos | Cadastrar, editar, ocultar e excluir produtos: preço, promoção, tamanhos com estoque, até 6 fotos (JPG, PNG ou WebP, até 3 MB), selos e destaque no Início. |
| Catálogo | Esportes e categorias. Um esporte novo só aparece na loja quando tiver produtos. |

Regras do painel:
- O pedido só anda para frente. Cancelar só vale antes de sair da loja, e as peças voltam para o estoque (o estoque sai quando o cliente compra). Pedido já pago e cancelado precisa ter o dinheiro devolvido por fora: o painel avisa, mas não faz estorno.
- Produto que já foi vendido não pode ser excluído, só ocultado ("Aparece na loja" desligado), para não estragar o histórico dos pedidos. Esporte e categoria só são excluídos vazios.
- As fotos vão para `assets/img/produtos/` com um nome novo e aleatório. O arquivo é conferido pelo conteúdo, não pela extensão, então um programa disfarçado de `.jpg` é recusado. Essas fotos ficam fora do GitHub (`.gitignore`): para levar para a hospedagem, envie a pasta junto.
- **Troque a senha do painel** ("Trocar senha do painel", no Painel) antes de publicar o link na InfinityFree. A senha `triade123` está neste README, que é público.

O frete também é simulado: o preço e o prazo mudam conforme a região do CEP dentro do RS (tabela abaixo). Dá para calcular na página do produto. Os dados do cartão nunca são gravados: o banco guarda só a bandeira e os 4 últimos números.

---

## Como publicar na InfinityFree (link público gratuito)

1. Crie uma conta em <https://www.infinityfree.com> e crie uma hospedagem (pode usar o subdomínio grátis que eles oferecem).
2. No painel da hospedagem, em **MySQL Databases**, crie um banco com o nome `triade`. Anote o **host**, o **nome do banco**, o **usuário** e a **senha** que aparecem.
3. Abra o **phpMyAdmin** pelo painel, selecione o banco e importe **só** `sql/01_estrutura.sql` e depois `sql/02_dados.sql` (o `00` não funciona lá, o banco já foi criado no passo 2).
4. No seu computador, copie `includes/config.local.exemplo.php` para `includes/config.local.php` e preencha com os dados do passo 2.
5. Pelo **File Manager** do painel (ou por FTP com o FileZilla), envie todos os arquivos do projeto para a pasta `htdocs` da hospedagem, **incluindo** o `config.local.php`.
6. Acesse o seu endereço. Deve aparecer "Banco de dados conectado".

> O `config.local.php` tem a senha do banco e por isso **não vai para o GitHub** (está no `.gitignore`). Ele só existe no seu computador e na hospedagem.

---

## Organização das pastas

```
Triade_esportes_web/
├── index.php            01 · Início
├── esportes.php         02 · Esportes
├── buscar.php           03 · Buscar
├── resultados.php       04 · Resultados, 05 · Filtros e 06 · Sem resultado
├── produto.php          07 · Produto, 08 · Guia de tamanhos e 09 · Adicionado
├── sacola.php           10 · Sacola e 11 · Sacola vazia
├── trocas.php           27 · Trocas e devoluções
├── sobre.php            28 · Sobre e contato
├── offline.html         29 · Sem internet (página guardada pelo sw.js)
├── sw.js                ajudante do navegador: tela Sem internet e arquivos guardados
├── manifest.json        nome, cores e ícones para instalar o site no celular
├── admin/               painel da loja (login separado do cliente)
│   ├── entrar.php       entrar no painel
│   ├── index.php        início: números, últimos pedidos e estoque acabando
│   ├── pedidos.php      lista de pedidos com filtros e busca
│   ├── pedido.php       detalhe do pedido e troca de status (rastreio, cancelar)
│   ├── produtos.php     lista de produtos
│   ├── produto.php      cadastrar ou editar produto (tamanhos, estoque e fotos)
│   ├── catalogo.php     esportes e categorias
│   ├── esporte.php      cadastrar ou editar esporte
│   ├── categoria.php    cadastrar ou editar categoria
│   ├── senha.php        trocar a senha do painel
│   └── sair.php         sair do painel
├── api/sacola.php       adiciona, altera e remove itens da sacola
├── api/favoritos.php    liga e desliga o coração de favorito
├── conta/               conta do cliente
│   ├── entrar.php       19 · Entrar
│   ├── criar.php        20 · Criar conta (vindo do pedido, já vem preenchido)
│   ├── recuperar.php    21 · Recuperar senha (e-mail simulado na tela)
│   ├── nova-senha.php   link do e-mail de recuperação
│   ├── index.php        22 · Conta
│   ├── pedidos.php      23 · Meus pedidos
│   ├── pedido.php       24 · Detalhe do pedido com rastreio
│   ├── enderecos.php    25 · Endereços
│   ├── endereco.php     adicionar ou editar endereço
│   ├── favoritos.php    26 · Favoritos
│   └── sair.php         sair da conta
├── checkout/            compra, uma página por passo
│   ├── index.php        início da compra (manda para o passo que falta)
│   ├── identificacao.php 12 · Identificação
│   ├── endereco.php     13 · Endereço
│   ├── entrega.php      14 · Entrega
│   ├── pagamento.php    15 · Pagamento e 15b · Cartão
│   ├── revisao.php      16 · Revisão (cria o pedido)
│   ├── pix.php          17 · Pix aguardando
│   ├── confirmado.php   18 · Pedido confirmado
│   └── recusado.php     30 · Pagamento recusado
├── guia-de-estilo.php   todos os componentes visuais numa página
├── includes/            arquivos internos, bloqueados para o navegador
│   ├── bootstrap.php    carregado no topo de toda página
│   ├── config.php       banco de dados, regras de negócio e contatos
│   ├── db.php           conexão com o MySQL
│   ├── funcoes.php      funções de apoio (preço, parcelas, CPF, segurança)
│   ├── componentes.php  HTML dos componentes (card de produto, selos, painel...)
│   ├── catalogo.php     consultas de produtos, busca e filtros
│   ├── sacola.php       sacola guardada na sessão
│   ├── frete.php        opções de entrega, preço e prazo por região do CEP
│   ├── checkout.php     passos da compra, validações e criação do pedido
│   ├── conta.php        login, cadastro, senha, pedidos, endereços e favoritos
│   ├── admin.php        tudo do painel da loja (só as páginas de admin/ carregam)
│   └── layout.php       moldura das páginas (cabeçalho e barra de abas)
├── assets/
│   ├── css/             tokens.css (cores e medidas do Figma), base.css, componentes.css, telas.css
│   ├── js/              mola.js (animação de mola), painel.js, aviso.js, app.js, produto.js, sacola.js, validacao.js, checkout.js, conta.js, admin.js
│   ├── fonts/           fonte Inter (licença livre OFL)
│   └── img/             icones.svg (ícones e silhuetas dos produtos), app/ (ícone provisório do site) e produtos/ (fotos enviadas pelo painel)
├── sql/                 scripts do banco de dados
│   ├── 00_criar_banco.sql
│   ├── 01_estrutura.sql
│   └── 02_dados.sql
└── README.md
```

## Site instalável (PWA)

No celular, o navegador oferece **"Adicionar à tela inicial"**. O site abre como um app, sem a barra do navegador, com o ícone provisório da Triade.
Se a internet cair, aparece a tela **Sem internet** (29), que volta sozinha quando a conexão retorna.
Se uma página demorar para carregar, aparece o **esqueleto** (31) no lugar do conteúdo.

> Isso só funciona em `http://localhost` (XAMPP) ou em endereço com `https://` (a InfinityFree oferece de graça em **SSL Certificates**). Para forçar o navegador a pegar uma versão nova do `sw.js`, troque o número em `VERSAO` dentro dele.

## Visual e animações

- Cores, espaçamentos, raios e textos vêm das variáveis do Figma, em `assets/css/tokens.css`, com os mesmos nomes (`bg/primary` vira `var(--color-bg-primary)`).
- Vidro (desfoque do fundo) só na interface que flutua: cabeçalho, barra de abas, botões principais, painéis e avisos.
- As animações de mola (`assets/js/mola.js`) continuam do ponto e da velocidade em que estavam, por isso um painel arrastado e solto rápido fecha no mesmo embalo.
- Se o celular estiver com **reduzir movimento** ligado, as animações viram um esmaecer simples. Com **reduzir transparência**, o vidro vira fundo sólido.
- A troca entre páginas é animada nos navegadores que suportam (Chrome e Android). Nos outros, a página só troca.

## Regras de negócio

Ficam todas em `includes/config.php`. Foram definidas no design e **ainda não foram confirmadas pela Triade**:

| Regra | Valor |
|---|---|
| Desconto no Pix | 5% |
| Cartão | até 6x sem juros, parcela mínima de R$ 30 |
| Frete grátis | PAC grátis acima de R$ 399 |
| Retirada | grátis no depósito em São Leopoldo |
| Trocas | 30 dias |

## Frete por região (simulado)

Não há ligação com os Correios. O RS foi dividido pelas faixas de CEP; quanto mais longe de São Leopoldo, mais caro e demorado. Os valores ficam em `includes/frete.php`.

| Região | CEPs | PAC | SEDEX | Transportadora |
|---|---|---|---|---|
| Grande Porto Alegre | 90000 a 94999 | R$ 14,90 · 2 a 4 dias | R$ 24,90 · 1 a 2 dias | R$ 19,90 · 2 a 3 dias |
| Serra, Litoral Norte e Vales | 95000 a 95999 | R$ 22,90 · 4 a 6 dias | R$ 34,90 · 2 a 3 dias | R$ 27,90 · 3 a 5 dias |
| Sul e Centro do estado | 96000 a 97999 | R$ 27,90 · 5 a 8 dias | R$ 42,90 · 2 a 4 dias | R$ 32,90 · 4 a 7 dias |
| Norte, Missões e Fronteira | 98000 a 99999 | R$ 31,90 · 6 a 9 dias | R$ 49,90 · 3 a 5 dias | R$ 36,90 · 5 a 8 dias |

Prazos em dias úteis. PAC grátis acima de R$ 399 em todo o RS. Retirada em São Leopoldo: grátis, pronta em 1 dia útil.

## Banco de dados

| Tabela | Guarda |
|---|---|
| `esportes`, `categorias`, `produtos` | catálogo em Esporte → Categoria → Produto |
| `produto_tamanhos` | estoque de cada tamanho (estoque 0 aparece riscado) |
| `produto_imagens` | fotos dos produtos enviadas pelo painel (sem foto, aparece a silhueta) |
| `clientes`, `enderecos`, `favoritos` | contas de clientes |
| `recuperacao_senha` | códigos para trocar a senha |
| `pedidos`, `pedido_itens`, `pedido_historico` | pedidos, itens e linha do tempo do rastreio |
| `administradores` | acesso ao painel da loja |

Para incluir um esporte novo (ex.: vôlei), use a aba Catálogo do painel. Não precisa mudar código.

## Segurança aplicada

- Senhas guardadas com `password_hash` (nunca em texto puro).
- Consultas ao banco com parâmetros (PDO), o que impede SQL injection.
- Todo texto mostrado na tela passa por `e()`, o que impede que alguém injete código na página.
- Formulários com código CSRF, que impede envio a partir de outro site.
- Pastas `includes/` e `sql/` bloqueadas para acesso pelo navegador.
- Painel da loja com login próprio, limite de 5 tentativas erradas e páginas fora do Google (`noindex`).
- Fotos enviadas conferidas pelo conteúdo e salvas com nome aleatório. A pasta das fotos não deixa rodar programas.
