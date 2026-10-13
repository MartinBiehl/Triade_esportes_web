# Triade Esporte · loja online

E-commerce mobile da Triade Esporte, loja de materiais esportivos de São Leopoldo/RS.
Trabalho acadêmico feito com **HTML5, CSS3, JavaScript puro, PHP 8 e MySQL**.

- Design: [Figma · Triade Esporte App Mobile](https://www.figma.com/design/fBpNCyeZ07WWK659mNd6yV)
- Situação: **pronto para entrega** (etapas 0 a 9 do plano). A loja, a compra completa, a conta do cliente e o painel da loja funcionam. Pix, cartão, boleto e frete são simulados.
- Para testar tudo em ordem, siga o [roteiro de teste](#roteiro-de-teste).
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
2. No painel da hospedagem, em **MySQL Databases**, crie um banco com o nome `triade`. Anote o **MySQL Host Name** (`sqlXXX.infinityfree.com`), o **DB Name** (`if0_..._triade`) e o **User Name** (`if0_...`). A senha do banco é a senha da hospedagem, que aparece em **Account Details**.
3. Ainda em **MySQL Databases**, clique em **phpMyAdmin** ao lado do banco. Confira se o nome do banco aparece no topo (se não, clique nele na lista da esquerda) e importe **só** `sql/01_estrutura.sql` e depois `sql/02_dados.sql`. O `00` não funciona lá: o banco já foi criado no passo 2.
4. Pelo **File Manager** do painel (ou por FTP com o FileZilla, dados em **FTP Details**), apague o arquivo padrão da pasta `htdocs` e envie para dentro dela o **conteúdo** da pasta do projeto (as pastas `admin`, `api`, `assets`... e os arquivos soltos), não a pasta inteira. A pasta `.git` não precisa ir.
5. **Na hospedagem**, dentro de `htdocs/includes`, crie o arquivo `config.local.php` com o conteúdo de `config.local.exemplo.php`, preenchido com os dados do passo 2. Não crie esse arquivo no projeto do seu computador: o XAMPP passaria a procurar o banco da InfinityFree e pararia de funcionar.
6. Acesse o seu endereço. Deve abrir a tela Início da loja. Se aparecer "Banco de dados não conectado", quase sempre é erro de digitação no `config.local.php` (host ou senha).
7. Confira a proteção: abra `seu-endereco/includes/config.php`. Tem que dar **403 (proibido)**. Se mostrar a página em branco ou baixar o arquivo, avise antes de divulgar o link.
8. Entre em `seu-endereco/admin` e troque a senha do painel em **Trocar senha do painel**.

Cuidados na InfinityFree:
- O site precisa de **PHP 8.2 ou mais novo**. A InfinityFree já usa PHP 8. Se o painel dela oferecer escolha de versão, não escolha uma 7.x.
- Ative o **SSL** grátis (em **SSL Certificates**) e use o endereço com `https://`. Sem ele o site não pode ser instalado no celular.
- A InfinityFree mostra uma tela de verificação ("checking your browser") no primeiro acesso. É dela, não do site.
- Fotos enviadas pelo painel no XAMPP **não vão sozinhas**: envie também a pasta `assets/img/produtos`. As fotos ficam fora do GitHub.
- O `modo_debug` do `config.local.php` fica `false` lá, para não mostrar detalhes de erro para visitantes.

> O `config.local.php` tem a senha do banco e por isso **não vai para o GitHub** (está no `.gitignore`). Ele só existe na hospedagem. Ao enviar atualizações, não apague nem substitua esse arquivo lá.

---

## Roteiro de teste

Comece com o banco zerado (importe de novo o `01` e o `02`). Use o Chrome no modo celular (**F12** e **Ctrl+Shift+M**).

**1. Compra sem conta, no cartão**
1. No Início, abra um produto, escolha o tamanho e toque em **Adicionar à sacola**.
2. Na página do produto, digite um CEP em **Calcular frete** (ex.: `96010-000`, Pelotas). Aparecem preço e prazo da região Sul.
3. Na Sacola, mude a quantidade e deslize um item para o lado para remover (o aviso tem **Desfazer**).
4. Toque em **Continuar** e preencha os dados. Use um CPF válido, como `529.982.247-25`.
5. No pagamento, escolha cartão `4111 1111 1111 1111` com validade futura. O pedido fica **Pagamento aprovado**.
6. Repita com um cartão terminado em `0002`: aparece **Pagamento recusado** e nada é gravado.

**2. Compra no Pix e no boleto**
1. Faça uma compra no Pix e toque em **Simular pagamento aprovado**.
2. Faça outra no Pix e não pague: depois de 30 minutos ela é cancelada sozinha e o estoque volta.
3. Faça uma no boleto: o pedido fica **Aguardando pagamento** até a loja confirmar no painel.

**3. Conta do cliente**
1. Em Conta, entre com `ana@exemplo.com` / `triade123`.
2. Abra **Meus pedidos**: o TR-20481 está a caminho, com código de rastreio e linha do tempo.
3. Teste endereços (adicionar, tornar principal, excluir), favoritos (coração nos produtos) e **Esqueci minha senha** (o link aparece na própria tela).

**4. Painel da loja**
1. Abra `/admin` e entre com `admin@triade.com.br` / `triade123`.
2. Em **Pedidos > Para separar**, abra o pedido do cartão do passo 1. Toque em **Começar a separar**, depois **Marcar como enviado** com um código como `QB123456789BR` e, por fim, **Marcar como entregue**.
3. Volte à tela do pedido do cliente: o rastreio andou e o código aparece.
4. Cancele o pedido do boleto. As peças voltam ao estoque.
5. Em **Produtos > Novo**, cadastre um produto com tamanhos, estoque e uma foto. Ele aparece na loja na hora. Desligue **Aparece na loja** e ele some.
6. Em **Catálogo**, crie um esporte e uma categoria. O esporte só aparece na loja quando tiver produtos.

**5. Extras**
- Trocas e devoluções, Sobre e contato.
- Desligue a internet (F12 > Network > Offline) e navegue: aparece a tela **Sem internet**.
- No celular de verdade, use **Adicionar à tela inicial**. Só funciona em `localhost` ou `https`.

## O que é simulado ou ficou de fora

- **Pagamentos:** nenhum dinheiro é cobrado. O Pix tem QR Code e código de exemplo, o cartão é conferido só pelo formato e o boleto não gera documento.
- **Frete:** sem ligação com os Correios. Os preços e prazos são de uma tabela fixa por região.
- **E-mails:** o site não manda e-mail. A recuperação de senha mostra o link na tela.
- **Estorno:** cancelar um pedido pago devolve o estoque, mas não o dinheiro. O painel só lembra que o estorno precisa ser feito.
- **Conteúdo:** fotos, logo e contatos são provisórios, porque a Triade ainda não enviou os reais. Os produtos e preços também são de exemplo.
- **Fora do escopo do design:** cupons, avaliações, programa de fidelidade, personalização de camisa e modo escuro.
- **Painel:** tem um único login de loja. Não há cadastro de vários funcionários com permissões diferentes.

## Problemas comuns

| Sintoma | O que fazer |
|---|---|
| O Apache não fica verde no XAMPP | Outro programa está usando a porta 80 (Skype, IIS). Feche-o, ou mude a porta do Apache em **Config > httpd.conf** e acesse `localhost:8080`. |
| O MySQL não fica verde | Outro MySQL já está instalado na porta 3306. Pare o outro serviço no Windows. |
| "Banco de dados não conectado" | Confira se o MySQL está ligado e se os scripts `00`, `01` e `02` foram importados, nessa ordem. |
| Erro no phpMyAdmin ao importar o `01` | Clique no banco `triade` na esquerda **antes** de importar. |
| O site mostra uma versão velha depois de atualizar o código | Aperte **Ctrl+Shift+R**. Se continuar, em F12 > Application > Service Workers, clique em **Unregister**. |
| A foto não sobe no painel | Ela precisa ser JPG, PNG ou WebP e ter até 3 MB. |

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

Os valores ficam em `includes/config.php`. Foram definidos no design e **ainda não foram confirmados pela Triade**: se a empresa mudar algum, troque só lá.

| Regra | Como funciona |
|---|---|
| Desconto no Pix | 5% sobre os produtos. O frete não tem desconto. |
| Cartão | Até 6x sem juros, com parcela mínima de R$ 30 (ex.: R$ 89,90 vai até 2x). |
| Frete grátis | PAC grátis quando os produtos somam **R$ 399 ou mais** (antes do desconto do Pix), em todo o RS. SEDEX e transportadora continuam pagos. |
| Entrega | Só no Rio Grande do Sul (CEP começando com 9). Preço e prazo por região, tabela abaixo. |
| Retirada | Grátis no depósito em São Leopoldo, pronta em 1 dia útil. |
| Estoque | Sai quando o pedido é feito e volta se o pedido for cancelado. O cliente não consegue pôr na sacola mais do que tem. |
| Pix | O código vale 30 minutos. Depois disso o pedido é cancelado sozinho e as peças voltam para a loja. |
| Boleto | O pedido fica aguardando até a loja confirmar o pagamento no painel (prazo informado: até 2 dias úteis). |
| Trocas | 30 dias a partir da entrega (ou da retirada). A tela do pedido mostra até que dia vale. |
| Compra sem conta | Pode. Quem cria a conta logo depois da compra fica com o pedido na conta. |

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
