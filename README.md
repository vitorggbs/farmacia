# FarmaCerta

Sistema acadêmico multi-farmácia em PHP 8.2 e MySQL 8, com perfis de administrador, gerente e balconista.

## Recursos

- produtos, estoque, lotes e validade (FEFO);
- vendas transacionais, recibos e cancelamento com estorno;
- clientes, fornecedores, funcionários e entradas de mercadoria;
- relatórios, auditoria, backup e registro fiscal;
- isolamento de unidades e categorias por `farmacia_id`;
- PWA e assistente interno opcional via Vertex AI.

## Executar localmente

1. Copie `.env.example` para `.env` e troque todas as senhas.
2. Execute `docker compose up --build -d`.
3. Crie o primeiro administrador:

```bash
docker compose exec \
  -e ADMIN_NAME="Administrador" \
  -e ADMIN_LOGIN="admin" \
  -e ADMIN_PASSWORD="uma-senha-forte-com-12-caracteres" \
  app php criar_admin.php
```

4. Acesse `http://localhost:8080`.

O SQL não contém usuários ou senhas padrão. Gerentes e balconistas devem ser criados pela interface depois do primeiro acesso.

## Produção e Cloud SQL

Configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD`. Para Cloud SQL por socket, defina `DB_SOCKET=/cloudsql/projeto:regiao:instancia`; quando essa variável existe, ela tem prioridade sobre host e porta.

Para o agente de IA, configure `GCP_PROJECT_ID`, `VERTEX_LOCATION` e `VERTEX_MODEL`, além da identidade da carga de trabalho com permissão mínima para chamar o Vertex AI. Não coloque chaves ou senhas no repositório.

Em produção use apenas HTTPS. Cookies recebem `HttpOnly`, `SameSite=Lax` e `Secure` automaticamente quando a requisição chega por HTTPS (inclusive via `X-Forwarded-Proto`).

## Banco de dados

Toda a estrutura SQL está reunida em um único arquivo: `bancodasfarmacias.sql`. Ele contém todas as tabelas, relacionamentos, índices, categorias iniciais e a tabela de proteção contra tentativas de login.

Use esse arquivo somente para uma instalação nova e vazia. Não o importe por cima de um banco em produção. Para atualizar uma instalação antiga, faça backup e compare a estrutura antes de criar uma migração específica para os dados existentes.

Depois de migrar senhas antigas, remova ou bloqueie por regra de implantação o arquivo `migrar_senhas_bcrypt.php`.

## Verificações

```bash
docker compose exec app php tests/security_check.php
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Antes de publicar, teste manualmente login, venda simultânea, lote vencido, cancelamento, isolamento entre duas farmácias, permissões de perfil, backup e restauração.

## Segurança e privacidade

- formulários autenticados e API usam token CSRF;
- alterações de estado usam POST;
- consultas com entrada do usuário usam prepared statements;
- login é limitado a cinco falhas por login/IP em quinze minutos;
- nenhuma credencial padrão é distribuída;
- erros internos são gravados no log e não enviados ao navegador.

O sistema trata CPF, contato, compras e dados trabalhistas. Uma operação real deve definir base legal, aviso de privacidade, retenção, atendimento aos direitos do titular, acesso mínimo, criptografia de backups e procedimento de incidentes. Backups SQL contêm dados pessoais e hashes de senha: mantenha-os criptografados e com acesso restrito.

O módulo fiscal apenas registra dados de NF-e/NFC-e; emissão oficial exige certificado digital e integração homologada com SEFAZ/provedor fiscal.
