<?php

namespace Arseno25\ExceptionLogger\Resources\ExceptionLogResource\RelationManagers;

use Arseno25\ExceptionLogger\Models\ExceptionLog;
use Arseno25\ExceptionLogger\Resources\ExceptionLogResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $recordTitleAttribute = 'content';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Textarea::make('content')
                    ->label('Comment')
                    ->rows(4)
                    ->required()
                    ->maxLength(5000),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->placeholder('System')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('content')
                    ->label('Comment')
                    ->wrap()
                    ->lineClamp(4),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Posted')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('addComment')
                    ->label('Add Comment')
                    ->modalHeading('Add Comment')
                    ->form([
                        Textarea::make('content')
                            ->label('Comment')
                            ->rows(4)
                            ->required()
                            ->maxLength(5000),
                        Select::make('mentions')
                            ->label('Mention users')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->getSearchResultsUsing(function (string $search): array {
                                $userModelClass = config('auth.providers.users.model', \App\Models\User::class);

                                return $userModelClass::query()
                                    ->when($search !== '', function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%");
                                    })
                                    ->orderBy('name')
                                    ->limit(10)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                if ($values === []) {
                                    return [];
                                }

                                $userModelClass = config('auth.providers.users.model', \App\Models\User::class);

                                return $userModelClass::query()
                                    ->whereIn('id', $values)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            }),
                    ])
                    ->action(function (array $data): void {
                        /** @var ExceptionLog $log */
                        $log = $this->getOwnerRecord();

                        $comment = $log->comments()->create([
                            'content' => $data['content'],
                            'user_id' => Auth::id(),
                        ]);

                        $mentionIds = $data['mentions'] ?? [];

                        if (empty($mentionIds)) {
                            return;
                        }

                        $userModelClass = config('auth.providers.users.model', \App\Models\User::class);

                        $users = $userModelClass::query()
                            ->whereIn('id', $mentionIds)
                            ->whereKeyNot(Auth::id())
                            ->get();

                        if ($users->isEmpty()) {
                            return;
                        }

                        $excerpt = mb_strimwidth($comment->content ?? '', 0, 160, '...');

                        foreach ($users as $user) {
                            FilamentNotification::make()
                                ->title('You were mentioned in an exception comment')
                                ->body($excerpt)
                                ->actions([
                                    Action::make('view')
                                        ->label('View Error')
                                        ->button()
                                        ->url(ExceptionLogResource::getUrl('view', ['record' => $log]))
                                        ->openUrlInNewTab()
                                        ->markAsRead(),
                                ])
                                ->sendToDatabase($user);
                        }
                    }),
            ])
            ->emptyStateHeading('No comments yet')
            ->emptyStateDescription('Use "Add Comment" to start an internal discussion.');
    }

    protected function canCreateAnother(): bool
    {
        return false;
    }

    protected function canEdit(Model $record): bool
    {
        return false;
    }

    protected function canDelete(Model $record): bool
    {
        return false;
    }
}
