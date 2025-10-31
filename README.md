# Redmine Message Generator

Pacote Laravel para auxiliar na criação e envio de mensagens para tarefas no Redmine, com geração de texto via Gemini usando o diff do Git e diretrizes personalizadas.

## Recursos
- Command interativo `gen:redmine-message` que:
  - Busca detalhes da tarefa no Redmine (assunto, descrição e comentários anteriores);
  - Coleta o `git diff` (por padrão, `--staged`);
  - Carrega um arquivo de diretrizes (guideline) para formatar a mensagem;
  - Gera o texto via API do Gemini;
  - Opcionalmente envia a mensagem como nota para a tarefa no Redmine.
- Cliente HTTP para Redmine (`ArgusCS\RedmineMessage\Services\RedmineClient`).
- Cliente para executar comandos Git (`ArgusCS\RedmineMessage\Services\GitClient`).
- Cliente para integração com o Gemini (`ArgusCS\RedmineMessage\Services\GeminiClient`).
- Publicação de config em `config/messages.php`.

> Nota: A Facade `RedmineMessage` existe, mas atualmente não há binding registrado para o accessor `messages`. Portanto, o uso via Facade pode não funcionar até o binding ser ajustado.

## Requisitos
- PHP ^8.0
- Laravel 10 ou 11
- symfony/process ^5.4 | ^6.0 | ^7.0

## Instalação

Instale via Composer:

```bash
composer require argus-cs/redmine-message
```

Durante desenvolvimento local (path repository), adicione ao `composer.json` do seu app:

```json
{
  "repositories": [
    { "type": "path", "url": "../laravel/redmine-message", "options": { "symlink": true } }
  ]
}
```

E então:

```bash
composer require argus-cs/redmine-message:dev-main
```

Este pacote possui auto-discovery: o `ArgusCS\\RedmineMessage\\RedmineMessageServiceProvider` é registrado automaticamente.

## Publicar e configurar

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=messages
```

Isso criará `config/messages.php` no seu app. Configure as variáveis de ambiente conforme abaixo:

```env
# Gemini
GEMINI_URL="https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent"  # exemplo
GEMINI_API_KEY="sua_chave_gemini"

# Redmine
REDMINE_URL="https://redmine.seudominio.com"
REDMINE_API_KEY="sua_chave_redmine"
REDMINE_GUIDELINE="/caminho/para/guideline.md"  # arquivo com diretrizes para formatação
```

> Notas:
> - As chaves de configuração residem em `config/messages.php` e são mescladas sob a key `messages`.
> - A tag de publicação é `messages`.

## Uso

### 1) Via Command (recomendado)

```bash
php artisan gen:redmine-message
```

Fluxo do comando:
1. Solicita o número do ticket do Redmine;
2. Busca detalhes da tarefa no Redmine (incluindo journals/comentários);
3. Mostra os detalhes e pede confirmação para continuar;
4. Coleta o `git diff --staged`; se estiver vazio, o processo é interrompido;
5. Lê o arquivo de guideline indicado em `REDMINE_GUIDELINE`;
6. Monta um prompt contendo: diff, guideline, ticket, detalhes da tarefa, comentários anteriores e nome da branch;
7. Envia o prompt para o Gemini e exibe a mensagem gerada;
8. Pergunta se deseja enviar a mensagem como nota na tarefa do Redmine; se sim, envia via API.

> Dica: use `git add` para preparar (staged) as alterações que deseja considerar no diff antes de executar o comando.

### 2) Uso programático (clientes de serviço)

- Buscar detalhes de uma tarefa:

```php
use ArgusCS\RedmineMessage\Services\RedmineClient;

$redmine = new RedmineClient();
$task = $redmine->task('12345', 'issue'); // retorna a chave 'issue' do JSON
```

- Adicionar uma nota em uma tarefa:

```php
$redmine->addTaskNote('12345', "Mensagem gerada automaticamente...");
```

- Obter diff e branch via Git:

```php
use ArgusCS\RedmineMessage\Services\GitClient;

$git = new GitClient();
$diff = $git->diff(); // por padrão '--staged'
$branch = trim($git->nameBranch());
```

- Gerar conteúdo via Gemini:

```php
use ArgusCS\RedmineMessage\Services\GeminiClient;

$gemini = new GeminiClient();
$response = $gemini->generate($prompt);
$text = $response->json('candidates.0.content.parts.0.text');
```

## Como funciona (arquitetura)
- `RedmineMessageServiceProvider`:
  - Faz merge da config `config/messages.php` para a key `messages`;
  - Publica a config via tag `messages`;
  - Registra o comando `gen:redmine-message` quando em console.
- `GenMessage` (Command): orquestra a interação com Redmine, Git e Gemini, permite injeção de dependências (DI) para `RedmineClient`, `GitClient` e `GeminiClient` e oferece a opção de enviar a nota para o Redmine.
- `RedmineClient`:
  - GET `REDMINE_URL/issues/{ticket}.json?include=journals` para buscar tarefa;
  - PUT `REDMINE_URL/issues/{ticket}.json` com body `{ issue: { notes: "..." } }` para adicionar nota.
- `GeminiClient`: POST para `GEMINI_URL` com header `x-goog-api-key` e payload esperado pelo endpoint `generateContent`.
- `GitClient`: executa processos locais `git diff` e `git rev-parse`.

## Testes

```bash
composer install
vendor/bin/phpunit
```

- Há testes de unidade para `RedmineClient` e testes E2E para o comando `gen:redmine-message`.
- Os testes E2E usam DI para substituir `GitClient` e `GeminiClient` por fakes e `Http::fake()` para simular chamadas externas.

## Troubleshooting
- Mensagem: "Sem alterações encontradas." — certifique-se de ter arquivos staged (`git add ...`).
- Erro ao ler guideline — verifique se `REDMINE_GUIDELINE` aponta para um arquivo existente e acessível.
- Erro ao conectar no Gemini — confirme `GEMINI_URL` e `GEMINI_API_KEY` e se o endpoint usado é compatível (ex.: `generateContent`).
- Erro ao conectar no Redmine — confirme `REDMINE_URL` e `REDMINE_API_KEY` e se o usuário possui permissão para adicionar notas.

## Roadmap / Itens para avaliação
- [ ] Registrar binding para a Facade `RedmineMessage` (accessor `messages`).
- [ ] Remover import não utilizado no ServiceProvider (ex.: `SendRedmineMessage`).
- [x] Padronizar nomes e chaves de config sob `messages`.
- [ ] Tratar timeouts/retries nas chamadas HTTP (Gemini e Redmine) e permitir configurar.
- [ ] Opcional: permitir escolher o projeto do Redmine e outros campos no envio da nota.

## Licença
Distribuído sob a licença MIT. Consulte o arquivo `LICENSE`.