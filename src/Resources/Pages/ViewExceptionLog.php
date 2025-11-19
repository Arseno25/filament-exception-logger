<?php

namespace Arseno25\ExceptionLogger\Resources\Pages;

use Arseno25\ExceptionLogger\Resources\ExceptionLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Http;

class ViewExceptionLog extends ViewRecord
{
    protected static string $resource = ExceptionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analyze_error')
                ->label('Ask AI Solution')
                ->icon('heroicon-m-sparkles')
                ->color('info')
                ->modalHeading('AI Diagnosis & Solution')
                ->modalWidth('5xl')
                ->modalSubmitAction(false)
                ->modalCancelAction(fn ($action) => $action->label('Close'))
                ->modalContent(function ($record) {
                    $config = config('exception-logger.ai');

                    // 1. Cek Config
                    if (empty($config['enabled']) || empty($config['api_key'])) {
                        return view('exception-logger::ai-solution', [
                            'content' => null,
                            'error' => 'AI Feature is disabled or API Key is missing.',
                        ]);
                    }

                    // Pastikan record tersedia baik saat dipanggil dari Filament maupun dari test
                    $record ??= $this->getRecord();

                    // 2. Siapkan Request
                    $baseUrl = rtrim($config['base_url'], '/');
                    $endpoint = "$baseUrl/chat/completions";
                    $contextSnippet = json_encode(array_slice($record->context ?? [], 0, 2));

                $prompt = "Act as a Senior Laravel Developer with 10 years of experience and expert in Laravel, PHP, and Laravel Filament. Analyze this exception:\n" .
                        "Message: {$record->message}\n".
                        "File: {$record->file}:{$record->line}\n".
                        "Context: {$contextSnippet}\n\n".
                        'Explain the root cause concisely and provide the code fix. Use Markdown.';

                    try {
                        // 3. Call API
                        $response = Http::withToken($config['api_key'])
                            ->timeout(45)
                            ->withHeaders(['Content-Type' => 'application/json'])
                            ->post($endpoint, [
                                'model' => $config['model'],
                                'messages' => [
                                    ['role' => 'system', 'content' => 'You are a helpful code debugging assistant.'],
                                    ['role' => 'user', 'content' => $prompt],
                                ],
                                'temperature' => 0.5,
                            ]);

                        if ($response->failed()) {
                            return view('exception-logger::ai-solution', [
                                'content' => null,
                                'error' => 'API Error: '.$response->body(),
                            ]);
                        }

                        // 4. Return View dengan Content Sukses
                        return view('exception-logger::ai-solution', [
                            'content' => $response->json('choices.0.message.content'),
                            'error' => null,
                        ]);

                    } catch (\Exception $e) {
                        return view('exception-logger::ai-solution', [
                            'content' => null,
                            'error' => 'Connection Error: '.$e->getMessage(),
                        ]);
                    }
                }),
        ];
    }
}
