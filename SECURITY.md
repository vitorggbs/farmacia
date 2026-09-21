# Política de segurança

Não publique vulnerabilidades em canais públicos. Registre o problema com passos de reprodução, impacto e versão afetada junto ao responsável pelo projeto.

## Operação segura

- use HTTPS e banco em rede privada;
- aplique privilégios mínimos ao usuário MySQL e à identidade do Google Cloud;
- mantenha PHP, Apache, MySQL e dependências atualizados;
- altere senhas imediatamente após suspeita de exposição;
- criptografe backups e teste restauração regularmente;
- monitore falhas de login, cancelamentos, alterações de estoque e exportações;
- não habilite `display_errors` em produção.

Tokens CSRF não substituem validação de autorização. Toda consulta operacional deve continuar limitada pelo `farmacia_id` da sessão.
