<?php

namespace SmartCms\Support\Admin\Components\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;

class SaveAction
{
    public static function make(Page $page): Action
    {
        return Action::make(__('support::admin.save'))
            ->label(__('support::admin.save'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->formId('form')
            ->action(function () use ($page) {
                if ($page instanceof CreateRecord) {
                    return $page->create();
                }
                if (method_exists($page, 'getOwnerRecord')) {
                    $page->getOwnerRecord()->touch();
                    Notification::make('saved')
                        ->title(__('support::admin.saved'))
                        ->success()
                        ->send();

                    return;
                }
                $page->save(true, true);
                $page->record->touch();
            });
    }
}
