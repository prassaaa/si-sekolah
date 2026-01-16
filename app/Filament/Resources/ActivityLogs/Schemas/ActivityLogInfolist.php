<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Information')
                    ->schema([
                        TextEntry::make('log_name')
                            ->label('Log Name')
                            ->badge(),
                        TextEntry::make('description')
                            ->label('Event')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('subject_type')
                            ->label('Subject Type')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                        TextEntry::make('subject_id')
                            ->label('Subject ID'),
                        TextEntry::make('causer.name')
                            ->label('User')
                            ->default('-'),
                        TextEntry::make('causer.email')
                            ->label('User Email')
                            ->default('-'),
                        TextEntry::make('created_at')
                            ->label('Date & Time')
                            ->dateTime('d M Y H:i:s'),
                    ])
                    ->columns(2),
                Section::make('Properties')
                    ->schema([
                        KeyValueEntry::make('properties.attributes')
                            ->label('New Values')
                            ->visible(fn ($record) => !empty($record->properties['attributes'] ?? [])),
                        KeyValueEntry::make('properties.old')
                            ->label('Old Values')
                            ->visible(fn ($record) => !empty($record->properties['old'] ?? [])),
                    ]),
            ]);
    }
}
