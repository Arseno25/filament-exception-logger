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

    // Create a simple ExceptionLog-like object
    $record = new \stdClass;
    $record->level = 'ERROR';
    $record->message = 'Test message';
    $record->context = [];
    $record->method = 'GET';
    $record->url = 'http://localhost/test';
    $record->ip = '127.0.0.1';
    $record->user_agent = 'phpunit';
    $record->created_at = now();
    $record->updated_at = now();

    $page = new class($record) extends ViewExceptionLog
    {
        public function __construct(public $testRecord) {}

        public function testHeaderActions()
        {
            return $this->getHeaderActions();
        }

        public function getRecord(): \Illuminate\Database\Eloquent\Model
        {
            // Create a mock ExceptionLog model without database operations
            $model = new class extends ExceptionLog
            {
                protected $attributes = [];

                public function __construct()
                {
                    // Don't call parent constructor to avoid database operations
                }

                public function __get($key)
                {
                    return $this->attributes[$key] ?? null;
                }

                public function __set($key, $value)
                {
                    $this->attributes[$key] = $value;
                }

                public function __isset($key)
                {
                    return isset($this->attributes[$key]);
                }

                public function save(array $options = [])
                {
                    return true; // Mock save
                }

                public function delete()
                {
                    return true; // Mock delete
                }

                public function toArray()
                {
                    return $this->attributes;
                }
            };

            // Set all properties from testRecord
            foreach ($this->testRecord as $key => $value) {
                $model->$key = $value;
            }

            return $model;
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

    // Create a simple ExceptionLog-like object
    $record = new \stdClass;
    $record->level = 'ERROR';
    $record->message = 'Test message';
    $record->context = [];
    $record->method = 'GET';
    $record->url = 'http://localhost/test';
    $record->ip = '127.0.0.1';
    $record->user_agent = 'phpunit';
    $record->created_at = now();
    $record->updated_at = now();

    $page = new class($record) extends ViewExceptionLog
    {
        public function __construct(public $testRecord) {}

        public function testHeaderActions()
        {
            return $this->getHeaderActions();
        }

        public function getRecord(): \Illuminate\Database\Eloquent\Model
        {
            // Create a mock ExceptionLog model without database operations
            $model = new class extends ExceptionLog
            {
                protected $attributes = [];

                public function __construct()
                {
                    // Don't call parent constructor to avoid database operations
                }

                public function __get($key)
                {
                    return $this->attributes[$key] ?? null;
                }

                public function __set($key, $value)
                {
                    $this->attributes[$key] = $value;
                }

                public function __isset($key)
                {
                    return isset($this->attributes[$key]);
                }

                public function save(array $options = [])
                {
                    return true; // Mock save
                }

                public function delete()
                {
                    return true; // Mock delete
                }

                public function toArray()
                {
                    return $this->attributes;
                }
            };

            // Set all properties from testRecord
            foreach ($this->testRecord as $key => $value) {
                $model->$key = $value;
            }

            return $model;
        }
    };

    $action = collect($page->testHeaderActions())->first();
    $view = $action->getModalContent();

    expect($view->getName())->toBe('exception-logger::ai-solution')
        ->and($view->getData()['error'])->toBeNull()
        ->and($view->getData()['content'])->toBe('AI solution content');
});
