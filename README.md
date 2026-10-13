# Triade Esporte · loja online

E-commerce mobile da Triade Esporte, loja de materiais esportivos de São Leopoldo/RS.
Trabalho acadêmico feito com **HTML5, CSS3, JavaScript puro, PHP 8 e MySQL**.

- Design: [Figma · Triade Esporte App Mobile](https://www.figma.com/design/fBpNCyeZ07WWK659mNd6yV)
- Situação: **etapa 1 (base visual)**. As telas da loja chegam nas próximas etapas.
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
Nesta etapa aparece uma página de conferência dizendo **"Banco de dados conectado"** e quantos produtos existem.

Para ver como fica no celular: no Chrome, aperte **F12** e depois **Ctrl+Shift+M**, e escolha um iPhone ou Android no topo.

### Contas de teste

| Tipo | E-mail | Senha |
|---|---|---|
| Cliente | `ana@exemplo.com` | `triade123` |
| Painel da loja (etapa futura) | `admin@triade.com.br` | `triade123` |

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
├── index.php            página inicial (provisória nesta etapa)
├── guia-de-estilo.php   todos os componentes visuais numa página
├── includes/            arquivos internos, bloqueados para o navegador
│   ├── bootstrap.php    carregado no topo de toda página
│   ├── config.php       banco de dados, regras de negócio e contatos
│   ├── db.php           conexão com o MySQL
│   ├── funcoes.php      funções de apoio (preço, parcelas, CPF, segurança)
│   ├── componentes.php  HTML dos componentes (card de produto, selos, painel...)
│   └── layout.php       moldura das páginas (cabeçalho e barra de abas)
├── assets/
│   ├── css/             tokens.css (cores e medidas do Figma), base.css, componentes.css
│   ├── js/              mola.js (animação de mola), painel.js, aviso.js, app.js
│   ├── fonts/           fonte Inter (licença livre OFL)
│   └── img/             icones.svg (ícones e silhuetas dos produtos)
├── sql/                 scripts do banco de dados
│   ├── 00_criar_banco.sql
│   ├── 01_estrutura.sql
│   └── 02_dados.sql
└── README.md
```

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

## Banco de dados

| Tabela | Guarda |
|---|---|
| `esportes`, `categorias`, `produtos` | catálogo em Esporte → Categoria → Produto |
| `produto_tamanhos` | estoque de cada tamanho (estoque 0 aparece riscado) |
| `produto_imagens` | fotos dos produtos (vazio até ter fotos reais) |
| `clientes`, `enderecos`, `favoritos` | contas de clientes |
| `recuperacao_senha` | códigos para trocar a senha |
| `pedidos`, `pedido_itens`, `pedido_historico` | pedidos, itens e linha do tempo do rastreio |
| `administradores` | acesso ao painel da loja (etapa futura) |

Para incluir um esporte novo (ex.: vôlei), basta inserir uma linha em `esportes` e as categorias dele em `categorias`. Não precisa mudar código.

## Segurança aplicada

- Senhas guardadas com `password_hash` (nunca em texto puro).
- Consultas ao banco com parâmetros (PDO), o que impede SQL injection.
- Todo texto mostrado na tela passa por `e()`, o que impede que alguém injete código na página.
- Formulários com código CSRF, que impede envio a partir de outro site.
- Pastas `includes/` e `sql/` bloqueadas para acesso pelo navegador.
