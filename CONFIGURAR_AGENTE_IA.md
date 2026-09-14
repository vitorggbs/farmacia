# FarmaCerta IA — configuração no Google Cloud

O botão do agente aparece automaticamente para:
- Administrador
- Gerente
- Balconista

O chat usa **Gemini no Vertex AI** e autentica usando a própria conta de serviço do Cloud Run. Não é necessário colocar uma chave de API dentro do código.

## 1. Ative a Vertex AI API
No projeto `farmacia-508118`, ative a API **Vertex AI API** (`aiplatform.googleapis.com`).

## 2. Permissão da conta de serviço do Cloud Run
Na conta de serviço usada pelo seu serviço do Cloud Run, adicione a função:

`Vertex AI User` (`roles/aiplatform.user`)

## 3. Variáveis opcionais no Cloud Run
O código já possui valores padrão, mas você pode configurar:

- `GCP_PROJECT_ID=farmacia-508118`
- `VERTEX_LOCATION=us-central1`
- `VERTEX_MODEL=gemini-2.5-flash`

A variável `DB_PASSWORD` continua necessária para o Cloud SQL, como já era no projeto.

## 4. Faça um novo deploy
Como o Dockerfile agora instala a extensão `curl` do PHP, é necessário gerar uma nova imagem/deploy do Cloud Run.

## Segurança implementada
- O agente é somente leitura.
- Administrador vê somente resumos gerais das unidades.
- Gerente e balconista recebem contexto apenas da própria farmácia.
- Senhas e credenciais não são enviadas ao modelo.
- O agente não executa SQL gerado pela IA e não altera o banco.
