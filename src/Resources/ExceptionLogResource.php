<?php

namespace Arseno25\ExceptionLogger\Resources;

use Arseno25\ExceptionLogger\Enums\ExceptionLogStatus;
use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Arseno25\ExceptionLogger\Resources\Pages\ListExceptionLogs;
use Arseno25\ExceptionLogger\Resources\Pages\ViewExceptionLog;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Novadaemon\FilamentPrettyJson\Infolist\PrettyJsonEntry;

class ExceptionLogResource extends Resource
{
    protected static ?string $model = ExceptionLog::class;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Error Logs';

    protected static string|null|\UnitEnum $navigationGroup = 'System';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->label('Time'),

                Tables\Columns\TextColumn::make('level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'danger',
                        'WARNING' => 'warning',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('message')
                    ->limit(60)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->message),

                Tables\Columns\SelectColumn::make('status')
                    ->options(ExceptionLogStatus::class)
                    ->selectablePlaceholder(false)
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'ERROR' => 'Error',
                        'CRITICAL' => 'Critical',
                        'WARNING' => 'Warning',
                        'INFO' => 'Info',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make()
                    ->schema([
                        Section::make('Overview')
                            ->schema([
                                TextEntry::make('level')->badge(),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('method')->weight('bold'),
                                TextEntry::make('ip')->label('IP Address'),
                                TextEntry::make('url')->columnSpanFull()->url(fn ($record) => $record->url, true),
                            ])->columns(4),

                        Section::make('Error Detail')
                            ->schema([
                                TextEntry::make('message')
                                    ->color('danger')
                                    ->fontFamily('mono')
                                    ->columnSpanFull(),

                                TextEntry::make('file')
                                    ->fontFamily('mono')
                                    ->label('File Path'),

                                TextEntry::make('line')
                                    ->label('Line Number'),
                            ])->columns(2),

                        Section::make('Context Payload')
                            ->schema([
                                PrettyJsonEntry::make('context')
                                    ->hidden(fn ($record) => empty($record->context)),

                                TextEntry::make('user_agent')
                                    ->label('User Agent'),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpan(1)
                    ->extraAttributes(['class' => 'space-y-6']),

                Section::make('Source Code Preview')
                    ->schema([
                        ViewEntry::make('code_snippet')
                            ->view('exception-logger::code-snippet-wrapper')
                            ->columnSpanFull()
                            ->hidden(fn ($record) => ! $record->file || ! $record->line || ! file_exists($record->file)),
                    ])
                    ->collapsible()
                    ->collapsed(false)
                    ->columnSpan(1),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExceptionLogs::route('/'),
            'view' => ViewExceptionLog::route('/{record}'),
        ];
    }
}
