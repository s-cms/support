<?php

namespace SmartCms\Support\Admin\Components\Layout;

use Filament\Schemas\Components\Section;
use SmartCms\Support\Admin\Components\Forms\CreatedAt;
use SmartCms\Support\Admin\Components\Forms\StatusField;
use SmartCms\Support\Admin\Components\Forms\UpdatedAt;

class Aside
{
    public static function make(bool $isStatus = false)
    {
        $schema = [
            CreatedAt::make(),
            UpdatedAt::make(),
        ];
        if ($isStatus) {
            $schema[] = StatusField::make();
        }

        return Section::make($schema)->columnSpan(1);
    }
}
