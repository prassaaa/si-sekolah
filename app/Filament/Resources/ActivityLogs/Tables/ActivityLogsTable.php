<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('log_name')
                    ->label('Log Name')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                TextColumn::make('properties')
                    ->label('Changes')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return '-';
                        }
                        $attributes = $state['attributes'] ?? [];
                        $old = $state['old'] ?? [];

                        $changes = [];
                        foreach ($attributes as $key => $value) {
                            if (isset($old[$key]) && $old[$key] != $value) {
                                $changes[] = "{$key}: {$old[$key]} → {$value}";
                            } elseif (!isset($old[$key])) {
                                $changes[] = "{$key}: {$value}";
                            }
                        }

                        return !empty($changes) ? implode(', ', $changes) : '-';
                    })
                    ->wrap()
                    ->limit(50)
                    ->tooltip(function ($state): ?string {
                        if (!$state) {
                            return null;
                        }
                        return json_encode($state, JSON_PRETTY_PRINT);
                    }),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->default(fn () => now()),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('description')
                    ->label('Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options(function () {
                        return \Spatie\Activitylog\Models\Activity::query()
                            ->distinct()
                            ->pluck('subject_type', 'subject_type')
                            ->map(fn ($value) => class_basename($value))
                            ->toArray();
                    }),
                SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(function () {
                        return \App\Models\User::query()
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
