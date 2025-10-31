# Redmine Message (Laravel Package)

Pacote Laravel para enviar mensagens/issues ao Redmine. Inclui:
- Configuração própria (`config/redmine-message.php`)
- Service Provider com auto-discovery
- Serviço `RedmineClient`
- Facade `RedmineMessage`
- Command `redmine:send`

## Instalação

Adicione ao seu projeto via composer (local path ou VCS):

```bash
composer require argus-cs/redmine-message
```

Se estiver desenvolvendo localmente, você pode adicionar no `composer.json` do seu app:

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

> O package possui auto-discovery: o `RedmineMessageServiceProvider` será registrado automaticamente.

## Publicar config

```bash
php artisan vendor:publish --tag=redmine-message-config
```

Isso criará `config/redmine-message.php` no seu app. Ajuste as variáveis de ambiente:

- `REDMINE_ENABLED` (default: true)
- `REDMINE_ENDPOINT` (ex.: `https://redmine.seudominio.com`)
- `REDMINE_API_TOKEN`
- `REDMINE_DEFAULT_PROJECT`
- `REDMINE_TIMEOUT` (segundos)

## Uso

### Via Facade

```php
use ArgusCS\RedmineMessage\Facades\RedmineMessage;

RedmineMessage::send(
    subject: 'Bug crítico',
    message: 'Descrição do bug...'
);

RedmineMessage::send(
    'Melhoria',
    'Detalhes da melhoria...',
    ['project' => 123]
);
```

### Via Service Container

```php
use ArgusCS\RedmineMessage\Services\RedmineClient;

$client = app(RedmineClient::class);
$result = $client->send('Título', 'Mensagem');
```

### Via Command

```bash
php artisan redmine:send "Assunto" "Mensagem" --project=123
```

## Como funciona

- O `ServiceProvider` mescla a config padrão, publica o arquivo e registra o `RedmineClient` como singleton (alias `redmine-message`).
- O `RedmineClient` lê as opções de config e faz uma requisição POST para `endpoint/issues.json` com o token em `X-Redmine-API-Key`.
- O command `redmine:send` usa o serviço para criar uma issue.

## Notas

- Ajuste o payload conforme a API do seu Redmine.
- Dependências usadas: `illuminate/support`, `illuminate/console`, `illuminate/http`.
- Requer PHP 8.1+ e Laravel 10/11.