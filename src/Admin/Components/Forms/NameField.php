<?php

namespace SmartCms\Support\Admin\Components\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class NameField
{
    public static function make(string $name = 'name'): TextInput
    {
        /**@phpstan-ignore-next-line */
        return TextInput::make($name . '.' . main_lang())
            ->label(__('support::admin.name'))
            ->suffixAction(self::getTranslateAction($name))
            ->required()->reactive();
    }

    public static function getTranslateAction(string $name = 'name'): Action
    {
        return Action::make($name . '_translate')
            ->hidden(function (string $operation) {
                return app('lang')->adminLanguages()->count() <= 1 || $operation != 'edit';
            })
            ->modalWidth(Width::TwoExtraLarge)
            ->badge(function ($record) use ($name) {
                if (! $record) {
                    return 0;
                }

                return count($record->getTranslations($name)) - 1;
            })
            ->badgeColor(function ($record) use ($name) {
                if (! $record) {
                    return 'danger';
                }
                if (count($record->getTranslations($name)) > 1) {
                    return 'info';
                }

                return 'danger';
            })
            ->icon(function (): string {
                return 'heroicon-o-language';
            })
            /**@phpstan-ignore-next-line */
            ->schema(app('lang')->adminLanguages()->where('id', '!=', main_lang_id())->map(function ($lang) {
                return TextInput::make($lang->slug)->label($lang->name);
            })->toArray())
            ->fillForm(function ($record) use ($name) {
                /**@phpstan-ignore-next-line */
                return app('lang')->adminLanguages()->where('id', '!=', main_lang_id())->mapWithKeys(function ($lang) use ($record, $name) {
                    return [$lang->slug => $record->getTranslation($name, $lang->slug)];
                })->toArray();
            })
            ->action(function (Model $record, $data) use ($name) {
                foreach ($data as $key => $value) {
                    $record->setTranslation($name, $key, $value);
                }
                $record->save();
            });
    }
}
