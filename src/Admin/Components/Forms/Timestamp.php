<?php

namespace SmartCms\Support\Admin\Components\Forms;

use Filament\Infolists\Components\TextEntry;

class Timestamp
{
    public static function make(string $column, ?string $label = null): TextEntry
    {
        return TextEntry::make($column)
            ->inlineLabel()
            ->label(function () use ($column, $label): ?string {
                if ($label) {
                    return $label;
                }

                return match ($column) {
                    'created_at' => __('support::admin.created_at'),
                    'updated_at' => __('support::admin.updated_at'),
                    'deleted_at' => __('support::admin.deleted_at'),
                    default => null,
                };
            })
            ->state(fn($record): string => $record?->$column ? $record->$column->format('d.m.Y H:i') : '-');
    }
}
