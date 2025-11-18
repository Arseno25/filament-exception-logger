<?php

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Arseno25\ExceptionLogger\Resources\Pages\ViewExceptionLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

it('returns error view when AI is disabled or api key missing', function () {
    Config::set('exception-logger.ai', [
        'enabled' => false,
        'api_key' => null,
        'base_url' => 'https://api.openai.com/v1',
        'model' => 'gpt-4.1-mini',
    ]);

    $record = ExceptionLog::create([
        'level' => 'ERROR',
        'message' => 'Test message',
        'context' => [],
        'method' => 'GET',
        'url' => 'http://localhost/test',
        'ip' => '127.0.0.1',
        'user_agent' => 'phpunit',
    ]);

    $page = new class($record) extends ViewExceptionLog {
        public function __construct(public $testRecord)
        {
        }

        public function testHeaderActions()
        {
            return $this->getHeaderActions();
        }

        public function getRecord(): \Illuminate\Database\Eloquent\Model
        {
            return $this->testRecord;
        }
    };

    $action = collect($page->testHeaderActions())->first();
    $view = $action->getModalContent();

    expect($view->getName())->toBe('exception-logger::ai-solution')
        ->and($view->getData()['content'])->toBeNull()
        ->and($view->getData()['error'])->toBe('AI Feature is disabled or API Key is missing.');
});

it('returns content when AI call succeeds', function () {
    Config::set('exception-logger.ai', [
        'enabled' => true,
        'api_key' => 'test-key',
        'base_url' => 'https://api.openai.com/v1',
        'model' => 'gpt-4.1-mini',
    ]);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => 'AI solution content']],
            ],
        ], 200),
    ]);

    $record = ExceptionLog::create([
        'level' => 'ERROR',
        'message' => 'Test message',
        'context' => [],
        'method' => 'GET',
        'url' => 'http://localhost/test',
        'ip' => '127.0.0.1',
        'user_agent' => 'phpunit',
    ]);

    $page = new class($record) extends ViewExceptionLog {
        public function __construct(public $testRecord)
        {
        }

        public function testHeaderActions()
        {
            return $this->getHeaderActions();
        }

        public function getRecord(): \Illuminate\Database\Eloquent\Model
        {
            return $this->testRecord;
        }
    };

    $action = collect($page->testHeaderActions())->first();
    $view = $action->getModalContent();

    expect($view->getName())->toBe('exception-logger::ai-solution')
        ->and($view->getData()['error'])->toBeNull()
        ->and($view->getData()['content'])->toBe('AI solution content');
});


