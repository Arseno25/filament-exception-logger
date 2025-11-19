# Exception Logger

Log your Laravel exceptions into a database and manage them from a Filament panel.

This plugin provides:

- A dedicated `ExceptionLog` model and resource to browse your errors.
- A dashboard widget to visualize exceptions over time.
- **Automatic critical exception detection** - Automatically classifies exceptions as CRITICAL based on exception type, keywords, or HTTP status codes.
- **Multi-channel notifications** - Send alerts via Telegram, Slack, Discord, or Email.
- Optional "Ask AI Solution" action to analyze an exception using an AI provider.

---

## Installation

Install the package via composer:

```bash
composer require arseno25/exception-logger
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="exception-logger-migrations"
php artisan migrate
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="exception-logger-config"
```

Optionally, you can publish the views to customize the UI:

```bash
php artisan vendor:publish --tag="exception-logger-views"
```

---

## Configure the logging channel

Add a custom channel to `config/logging.php`:

```php
use Arseno25\ExceptionLogger\Logging\DatabaseLoggerHandler;

return [
    'channels' => [
        // ...
        'exception_logger' => [
            'driver' => 'monolog',
            'level' => 'error',
            'handler' => DatabaseLoggerHandler::class,
        ],
    ],
];
```

Then add the channel to your default `stack` so it is used automatically:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'exception_logger'],
        'ignore_exceptions' => false,
    ],
    // ...
],
```

From now on, any error sent to Laravel's logger will be stored in the `exception_logger_table` table.

---

## Critical Exception Detection

The package automatically detects and classifies critical exceptions based on configurable criteria. Exceptions are automatically marked as **CRITICAL** if they match any of the following:

1. **Exception class** - Matches a configured critical exception class
2. **Message keywords** - Contains critical keywords in the exception message
3. **HTTP status code** - HTTP exceptions with status code >= 500

Configure critical detection in `config/exception-logger.php`:

```php
'critical' => [
    // List of exception classes considered CRITICAL
    'exceptions' => [
        \Illuminate\Database\QueryException::class,
        \PDOException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
    ],

    // Keywords in exception message that indicate CRITICAL
    'keywords' => [
        'database',
        'connection',
        'timeout',
        'memory',
        'fatal',
        'segmentation',
        'out of memory',
    ],
],
```

The system uses a level hierarchy to ensure the highest severity level is used:

- `DEBUG` < `INFO` < `NOTICE` < `WARNING` < `ERROR` < `CRITICAL` < `ALERT` < `EMERGENCY`

---

## Add to Filament panel

Register the plugin in your Filament panel provider, for example in `App\Providers\Filament\AdminPanelProvider`:

```php
use Arseno25\ExceptionLogger\ExceptionLoggerPlugin;
use Filament\Panel;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->plugins([
                ExceptionLoggerPlugin::make(),
            ]);
    }
}
```

### What the plugin registers

The plugin will:

- Register the `ExceptionLogResource` under your chosen navigation group.
- Register the `ExceptionStatsOverview` chart widget for your dashboard.
- Register the CSS for the AI Solution modal.

If you want to explicitly control which widgets appear on a given dashboard, you can also register the widget manually:

```php
use Arseno25\ExceptionLogger\ExceptionLoggerPlugin;
use Arseno25\ExceptionLogger\Widgets\ExceptionStatsOverview;
use Filament\Pages\Dashboard;
use Filament\Panel;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // ...
            ->widgets([
                ExceptionStatsOverview::class,
            ]);
    }
}
```

---

## AI Solution (optional)

The “Ask AI Solution” action appears on the record view page of `ExceptionLogResource`.  
It sends the exception data (message, file, line, context) to your configured AI provider and displays a Markdown response in a Filament modal.

Configure AI in `config/exception-logger.php`:

```php
return [
    'ai' => [
        'enabled' => env('EXCEPTION_LOGGER_AI_ENABLED', false),
        'api_key' => env('EXCEPTION_LOGGER_AI_API_KEY'),
        'base_url' => env('EXCEPTION_LOGGER_AI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('EXCEPTION_LOGGER_AI_MODEL', 'gpt-4.1-mini'),
        'temperature' => 0.5,
    ],
];
```

And in your `.env`:

```env
EXCEPTION_LOGGER_AI_ENABLED=true
EXCEPTION_LOGGER_AI_API_KEY=sk-xxxx
EXCEPTION_LOGGER_AI_BASE_URL=https://api.openai.com/v1
EXCEPTION_LOGGER_AI_MODEL=gpt-4.1-mini
```

If AI is disabled or misconfigured, the action will gracefully show an error message instead of crashing the page.

---

## Notifications (optional)

You can configure multiple notification channels to receive alerts when exceptions occur. All channels support throttling to prevent spam.

### Telegram

Send notifications to a Telegram chat:

```php
'telegram' => [
    'enabled' => env('EXCEPTION_LOGGER_TELEGRAM_ENABLED', false),
    'token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
    'throttle_minutes' => 5,
],
```

```env
EXCEPTION_LOGGER_TELEGRAM_ENABLED=true
TELEGRAM_BOT_TOKEN=xxxx:yyyy
TELEGRAM_CHAT_ID=123456789
```

### Slack

Send notifications to a Slack channel via webhook:

```php
'slack' => [
    'enabled' => env('EXCEPTION_LOGGER_SLACK_ENABLED', false),
    'webhook_url' => env('EXCEPTION_LOGGER_SLACK_WEBHOOK_URL'),
    'channel' => env('EXCEPTION_LOGGER_SLACK_CHANNEL', '#exceptions'),
    'username' => env('EXCEPTION_LOGGER_SLACK_USERNAME', 'Exception Logger'),
    'throttle_minutes' => 5,
],
```

```env
EXCEPTION_LOGGER_SLACK_ENABLED=true
EXCEPTION_LOGGER_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
EXCEPTION_LOGGER_SLACK_CHANNEL=#exceptions
EXCEPTION_LOGGER_SLACK_USERNAME=Exception Logger
```

### Discord

Send notifications to a Discord channel via webhook:

```php
'discord' => [
    'enabled' => env('EXCEPTION_LOGGER_DISCORD_ENABLED', false),
    'webhook_url' => env('EXCEPTION_LOGGER_DISCORD_WEBHOOK_URL'),
    'username' => env('EXCEPTION_LOGGER_DISCORD_USERNAME', 'Exception Logger'),
    'throttle_minutes' => 5,
],
```

```env
EXCEPTION_LOGGER_DISCORD_ENABLED=true
EXCEPTION_LOGGER_DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/YOUR/WEBHOOK/URL
EXCEPTION_LOGGER_DISCORD_USERNAME=Exception Logger
```

### Email

Send notifications via email (supports multiple recipients):

```php
'email' => [
    'enabled' => env('EXCEPTION_LOGGER_EMAIL_ENABLED', false),
    'to' => env('EXCEPTION_LOGGER_EMAIL_TO'),
    'from' => [
        'address' => env('EXCEPTION_LOGGER_EMAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'name' => env('EXCEPTION_LOGGER_EMAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Exception Logger')),
    ],
    'subject_prefix' => env('EXCEPTION_LOGGER_EMAIL_SUBJECT_PREFIX', '[Exception Alert]'),
    'throttle_minutes' => 5,
],
```

```env
EXCEPTION_LOGGER_EMAIL_ENABLED=true
EXCEPTION_LOGGER_EMAIL_TO=admin@example.com,dev@example.com
EXCEPTION_LOGGER_EMAIL_FROM_ADDRESS=noreply@example.com
EXCEPTION_LOGGER_EMAIL_FROM_NAME=Exception Logger
EXCEPTION_LOGGER_EMAIL_SUBJECT_PREFIX=[Exception Alert]
```

**Note:** For email, you can specify multiple recipients by separating them with commas, or use an array in the config file.

All notification channels use cache-based throttling (`throttle_minutes`) to avoid spamming the same message repeatedly. Each channel has independent throttling, so you can enable multiple channels simultaneously.

---

## Pruning old logs

The retention rules are configured in `config/exception-logger.php`:

```php
'pruning' => [
    'enabled' => true,
    'retention_days' => 30,
],
```

The `ExceptionLog` model uses Laravel’s `Prunable` trait. To run pruning automatically, schedule the `model:prune` command in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('model:prune')->daily();
}
```

When `enabled` is `true`, logs older than `retention_days` will be deleted when this command runs.

---

## Usage in Filament

Once installed and configured:

1. Open your Filament panel.
2. Navigate to the **Exception Logs** resource.
3. Inspect each record’s overview, error details, context payload and user agent.
4. Optionally, update the `status` of a log (`New`, `In Progress`, `Resolved`, `Ignored`).
5. On the record view page, click **“Ask AI Solution”** to open the AI analysis modal.

You can also view the `ExceptionStatsOverview` widget on your dashboard to see how many exceptions occurred in the last 7 days.

---

## Customization

You can customize the UI in two ways:

- **Views** – publish the package views and edit `exception-logger::ai-solution` or others as needed:

  ```bash
  php artisan vendor:publish --tag="exception-logger-views"
  ```

- **CSS** – override the CSS rules for the AI solution modal in your own stylesheets by targeting the `.exception-logger-ai-solution-*` classes.

---

## Testing

```bash
composer test
```

---

## Contributing

Contributing is pretty chill and is highly appreciated! Just send a PR and/or create an issue!

---

## Credits

- [Arseno25](https://github.com/Arseno25)
- [All Contributors](../../contributors)

---

## License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
