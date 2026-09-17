# FarmaCerta

Sistema acadêmico de gestão para uma rede de farmácias, feito em PHP, MySQL, HTML, CSS e JavaScript.

## Estrutura
O sistema usa **um único banco MySQL (`farmacerta`)**. Cada registro operacional possui `farmacia_id`, mantendo os dados separados por unidade.

## Perfis
- Administrador: gerencia unidades, auditoria, visão geral e backup.
- Gerente: produtos, categorias, estoque, lotes/validade, fornecedores, entradas, funcionários, clientes, recibos, relatórios, auditoria e controle fiscal.
- Balconista: produtos, clientes, carrinho e histórico de vendas.

## Funcionalidades principais
- Multi-farmácia por `farmacia_id`
- Estoque e movimentações
- Lotes e validade com saída dos lotes que vencem primeiro
- Categorias de produtos
- Fornecedores e histórico de entradas
- Cadastro e edição de clientes pelo balconista
- Histórico do cliente e produtos mais comprados
- Venda e cancelamento com devolução ao estoque
- Relatórios por período, impressão e CSV
- Auditoria
- Backup SQL pelo administrador
- Registro de dados fiscais de NF-e/NFC-e

## Banco
Para instalação nova, importe `bancodasfarmacias.sql`.
Para uma instalação anterior, faça backup e use `atualizar_banco.sql` uma vez.

## Fiscal
A tela Fiscal guarda número, série, chave, protocolo e status. A emissão oficial de NF-e/NFC-e na SEFAZ exige certificado digital e integração com uma API/provedor fiscal, que depende das credenciais da farmácia.
