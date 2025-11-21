# Exception Logger for Laravel Filament

Log your Laravel exceptions into a database and manage them from a beautiful Filament admin interface.

## ✨ Features

- **Database Exception Logging** - Automatically logs all exceptions with full context
- **Critical Detection** - Intelligent classification of critical exceptions
- **Multi-Channel Notifications** - Send alerts via Telegram, Slack, Discord, or Email
- **Filament Admin Interface** - Complete exception management with dashboard widget
- **AI-Powered Analysis** - Optional AI solution analysis for error resolution
- **Team Collaboration** - Comment system with user mentions
- **Automatic Pruning** - Configurable log retention policies

## 📋 Requirements

- PHP 8.2+
- Laravel 11.0+ or 12.0+
- Filament 4.0+

## 🚀 Installation

### 1. Install the package

```bash
composer require arseno25/exception-logger
```

### 2. Publish and run migrations

```bash
php artisan vendor:publish --tag="exception-logger-migrations"
php artisan migrate
```

### 3. Publish configuration

```bash
php artisan vendor:publish --tag="exception-logger-config"
```

### 4. Configure logging channel

Add to `config/logging.php`:

```php
use Arseno25\ExceptionLogger\Logging\DatabaseLoggerHandler;

'channels' => [
    // ...
    'exception_logger' => [
        'driver' => 'monolog',
        'level' => 'error',
        'handler' => DatabaseLoggerHandler::class,
    ],
],
```

Add to your default stack:

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'exception_logger'],
    'ignore_exceptions' => false,
],
```

### 5. Add to Filament panel

Register in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Arseno25\ExceptionLogger\ExceptionLoggerPlugin;

->plugins([
    ExceptionLoggerPlugin::make(),
])
```

This will automatically register:
- **Exception Logs** resource page for managing exceptions
- **Exception Stats Overview** widget showing 7-day error trends

### 6. Access Exception Management

After installation:
1. Navigate to **Exception Logs** in your Filament admin panel
2. View the **Exception Stats Overview** widget on your dashboard
3. Configure widget permissions and visibility as needed

## 🚨 Critical Exception Detection

Automatically detects critical exceptions based on:

- **Exception classes** - QueryException, PDOException, HttpException, etc.
- **Message keywords** - database, connection, timeout, memory, fatal, etc.
- **HTTP status codes** - 5xx errors

Configure in `config/exception-logger.php`:

```php
'critical' => [
    'exceptions' => [
        \Illuminate\Database\QueryException::class,
        \PDOException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
    ],
    'keywords' => [
        'database', 'connection', 'timeout', 'memory', 'fatal',
    ],
],
```

## 📢 Notifications

Configure notifications in `config/exception-logger.php`:

### Telegram

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
TELEGRAM_BOT_TOKEN=123456:ABCDEF123456789
TELEGRAM_CHAT_ID=-1001234567890
```

### Slack

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
```

### Discord

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
```

### Email

```php
'email' => [
    'enabled' => env('EXCEPTION_LOGGER_EMAIL_ENABLED', false),
    'to' => env('EXCEPTION_LOGGER_EMAIL_TO'),
    'from' => [
        'address' => env('EXCEPTION_LOGGER_EMAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'name' => env('EXCEPTION_LOGGER_EMAIL_FROM_NAME', 'Exception Logger'),
    ],
    'subject_prefix' => env('EXCEPTION_LOGGER_EMAIL_SUBJECT_PREFIX', '[Exception Alert]'),
    'throttle_minutes' => 5,
],
```

```env
EXCEPTION_LOGGER_EMAIL_ENABLED=true
EXCEPTION_LOGGER_EMAIL_TO=admin@example.com,dev@example.com
EXCEPTION_LOGGER_EMAIL_FROM_ADDRESS=noreply@example.com
```

## 🤖 AI Solution Analysis

Optional AI-powered error analysis:

```php
'ai' => [
    'enabled' => env('EXCEPTION_LOGGER_AI_ENABLED', false),
    'api_key' => env('EXCEPTION_LOGGER_AI_API_KEY'),
    'base_url' => env('EXCEPTION_LOGGER_AI_BASE_URL', 'https://api.openai.com/v1'),
    'model' => env('EXCEPTION_LOGGER_AI_MODEL', 'gpt-3.5-turbo'),
    'temperature' => 0.5,
],
```

```env
EXCEPTION_LOGGER_AI_ENABLED=true
EXCEPTION_LOGGER_AI_API_KEY=sk-your-api-key
EXCEPTION_LOGGER_AI_MODEL=gpt-3.5-turbo
```

## 📊 Custom Widget Usage

### Adding Widget to Custom Dashboard

If you want to add the exception stats widget to a specific Filament dashboard:

```php
use Arseno25\ExceptionLogger\Widgets\ExceptionStatsOverview;

class MyCustomDashboard extends Filament\Pages\Dashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            ExceptionStatsOverview::class,
        ];
    }
}
```

### Creating Custom Exception Widget

You can extend the base widget for custom functionality:

```php
<?php

namespace App\Filament\Widgets;

use Arseno25\ExceptionLogger\Widgets\ExceptionStatsOverview;
use Flowframe\Trend\Trend;

class CustomExceptionWidget extends ExceptionStatsOverview
{
    protected ?string $heading = 'Custom Error Analytics';

    protected function getData(): array
    {
        // Custom data logic - last 30 days instead of 7
        $data = Trend::model(ExceptionLog::class)
            ->between(
                start: now()->subDays(30),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Exceptions (30 Days)',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#8b5cf6', // Purple
                    'backgroundColor' => '#ede9fe',
                    'fill' => true,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
}
```

## 🗂️ Log Pruning

Configure automatic log cleanup:

```php
'pruning' => [
    'enabled' => true,
    'retention_days' => 30,
],
```

Schedule pruning in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('model:prune')->daily();
}
```

## 💻 Usage in Filament

Once installed:

1. **Exception Logs Page**: Navigate to **Exception Logs** in your Filament panel to manage all exceptions
2. **Dashboard Widget**: View real-time exception trends in the **Exception Stats Overview** widget
3. **Exception Management**: Update exception status (New, In Progress, Resolved, Ignored)
4. **AI Analysis**: Use **"Ask AI Solution"** for intelligent error analysis
5. **Collaboration**: Add comments and mention team members for collaboration
6. **Custom Widgets**: Create custom exception widgets for specific dashboards (see examples above)

## 🤝 Contributing

We welcome contributions from the community! Whether you're fixing bugs, adding features, or improving documentation, your help is appreciated.

### Development Setup

#### 1. Fork and Clone

```bash
git clone https://github.com/your-username/exception-logger.git
cd exception-logger
```

#### 2. Install Dependencies

```bash
composer install
npm install && npm run build
```

#### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

#### 4. Run Tests

```bash
composer test
```

## 👨‍💻 Credits

### Lead Developer

- **[Arseno25](https://github.com/Arseno25)** - Creator and maintainer

### Core Contributors

Thanks to everyone who has contributed to making this package better:

- [All Contributors](https://github.com/Arseno25/filament-exception-logger/graphs/contributors)

### Inspiration & References

- **Laravel Framework** - For the excellent ecosystem and conventions
- **Filament** - For the amazing admin panel framework
- **Monolog** - For the powerful logging abstraction
- **OpenAI** - For the AI capabilities that power intelligent analysis

## 📄 License

The MIT License (MIT). Please see the [License File](LICENSE.md) for more information.
