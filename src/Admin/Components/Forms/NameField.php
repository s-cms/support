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
            ->badge(function () {
                return app('lang')->adminLanguages()->where('id', '!=', main_lang_id())->count();
            })
            ->badgeColor(function ($record) use ($name) {
                if (! $record) {
                    return 'danger';
                }
                $activeSlugs = app('lang')->adminLanguages()->where('id', '!=', main_lang_id())->pluck('slug')->toArray();
                $translations = $record->getTranslations($name);
                if (! is_array($translations) || empty($translations)) {
                    return 'danger';
                }
                $filledCount = count(array_filter(
                    array_intersect_key($translations, array_flip($activeSlugs)),
                    fn ($value) => ! empty($value)
                ));

                return $filledCount >= count($activeSlugs) ? 'success' : 'danger';
            })
            ->icon(function (): string {
                return 'heroicon-o-language';
            })
            ->schema(function () use ($name) {
                return app('lang')->adminLanguages()->where('id', '!=', main_lang_id())->map(function ($lang) use ($name) {
                    return TextInput::make($lang->slug)
                        ->label($lang->name)
                        ->placeholder(fn ($record) => $record?->getTranslation($name, main_lang()) ?? '');
                })->toArray();
            })
            ->fillForm(function ($record) use ($name) {
                return app('lang')->adminLanguages()->where('id', '!=', main_lang_id())->mapWithKeys(function ($lang) use ($record, $name) {
                    $translations = $record->getTranslations($name);
                    if (! is_array($translations)) {
                        $translations = [];
                    }

                    return [$lang->slug => $translations[$lang->slug] ?? ''];
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
