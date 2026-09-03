
em desenvolvimento


# FarmaCerta ERP

Sistema simples em PHP e MySQL para estoque, funcionários, vendas e recibos de farmácias.

## Instalação no USBWebserver

1. Coloque a pasta `farmacerta-erp` dentro da pasta `root` do USBWebserver.
2. Abra o phpMyAdmin e importe `bancodasfarmacias.sql`.
3. Confira usuário, senha e nome do banco em `gerente/conexaoDB.php`.
4. Acesse `http://localhost/farmacerta-erp/`.

## Acessos iniciais

- Gerente: login `gerente` e senha `123456`.
- Balconista: login `balconista` e senha `123456`.

As senhas estão em texto normal para deixar o código mais simples para estudo.
Em um sistema real, o recomendado é proteger as senhas com `password_hash`.

Troque esses acessos depois da instalação. O gerente pode cadastrar outros balconistas pelo sistema.

## Várias farmácias

Cada farmácia possui banco e dados separados. Para instalar outra:

1. Faça outra cópia da pasta do sistema.
2. No SQL, troque `farmacia1` pelo nome do novo banco, por exemplo `farmacia2`.
3. Importe o SQL.
4. Na cópia da nova farmácia, altere `$nomeBanco` em `gerente/conexaoDB.php`.

Assim, produtos, funcionários, vendas e recibos de uma farmácia nunca se misturam com os de outra.

## Funções

- Login por cargo e bloqueio de páginas sem permissão.
- Cadastro e desativação de balconistas.
- Cadastro, edição, busca, exclusão e reposição de produtos.
- Aviso de estoque baixo e sem estoque.
- Carrinho com atualização e remoção de itens.
- Dinheiro, PIX, débito e crédito.
- Troco automático e baixa segura do estoque.
- Recibos reais e históricos por balconista.
- Dashboard do gerente com dados do banco.
- Registro de movimentações do estoque.
- Tabelas de lotes e validade prontas para expansão.

## Multi-farmacias

O sistema usa um unico banco chamado `farmacerta`.
Cada usuario, produto, venda e movimentacao possui um `farmacia_id`.
O `farmacia_id` do usuario e salvo na sessao quando ele faz login.
Assim, gerente e balconista acessam somente os dados da propria farmacia.

### Usuarios de teste

Farmacia 1:
- Gerente: `gerente` / `123456`
- Balconista: `balconista` / `123456`

Farmacia 2:
- Gerente: `gerente2` / `123456`
- Balconista: `balconista2` / `123456`

Importe o arquivo `bancodasfarmacias.sql` no phpMyAdmin antes de testar.
