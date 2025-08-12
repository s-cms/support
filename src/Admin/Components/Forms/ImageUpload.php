<?php

namespace SmartCms\Support\Admin\Components\Forms;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as InterventionImage;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageUpload
{
    protected ?string $optimize = null;

    public static function make(?string $name = null, string $directory = 'images', ?string $label = null): Component
    {
        return Group::make()->schema([
            Hidden::make($name . '.width'),
            Hidden::make($name . '.height'),
            Hidden::make($name . '.source'),
            ...app('lang')->adminLanguages()->map(function ($lang) use ($name) {
                return Hidden::make($name . '.' . $lang->slug);
            })->toArray(),
            FileUpload::make($name . '.source')->label($label ?? $name)
                ->imageEditor()
                ->image()
                ->openable(true)
                ->reorderable()
                ->disk('public')
                ->imagePreviewHeight('150')
                ->imageEditorAspectRatios([
                    '16:9',
                    '4:3',
                    '1:1',
                ])
                ->imageResizeUpscale(false)
                ->directory($directory)
                ->imagePreviewHeight('150')
                ->imageResizeMode('cover')
                // ->imageCropAspectRatio('16:9')
                ->live()
                ->afterStateUpdated(function (Set $set, $state, Get $get) use ($name) {
                    if (! $state instanceof TemporaryUploadedFile) {
                        return;
                    }
                    $image = InterventionImage::make($state->getRealPath());
                    $set($name . '.width', $image->width());
                    $set($name . '.height', $image->height());
                    foreach (app('lang')->adminLanguages() as $lang) {
                        $prev = $get($name . '.' . $lang->slug) ?? null;
                        if ($prev) {
                            continue;
                        }
                        $set($name . '.' . $lang->slug, $state->getClientOriginalName());
                    }
                })
                ->hintAction(
                    Action::make($name . '_settings')->label(__('support::admin.settings'))->icon(Heroicon::OutlinedCog8Tooth)->slideOver()->schema(function (Schema $form, Get $get) {
                        return $form->schema([
                            Section::make(__('support::admin.alt'))->schema(app('lang')->adminLanguages()->map(function ($lang) {
                                return TextInput::make($lang->slug)->label($lang->name);
                            })->toArray()),
                            Section::make(__('support::admin.size'))->schema([
                                TextInput::make('width')->label(__('support::admin.width'))->numeric(),
                                TextInput::make('height')->label(__('support::admin.height'))->numeric(),
                            ]),
                        ]);
                    })
                        ->action(function (array $data, Set $set, Get $get) use ($name) {
                            foreach ($data as $key => $value) {
                                $set($name . '.' . $key, $value);
                            }
                        })
                        ->fillForm(function (Get $get, Set $set) use ($name): array {
                            $data = $get($name) ?? [];

                            return $data;
                        })
                        ->visible(fn (Get $get) => $get($name . '.source'))
                )
                ->saveUploadedFileUsing(static function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                    try {
                        if (! $file->exists()) {
                            return null;
                        }
                    } catch (UnableToCheckFileExistence $exception) {
                        return null;
                    }

                    $compressedImage = null;
                    $filename = $component->getUploadedFileNameForStorage($file);
                    $optimize = 'webp';
                    $resize = null;
                    $maxImageWidth = 1200;
                    $maxImageHeight = 1200;
                    $shouldResize = false;
                    $imageHeight = null;
                    $imageWidth = null;
                    // $originalBinaryFile = $file->get();

                    if (
                        str_contains($file->getMimeType(), 'image') &&
                        ($optimize || $resize || $maxImageWidth || $maxImageHeight)
                    ) {
                        $image = InterventionImage::make($file);

                        if ($optimize) {
                            $quality = $optimize === 'jpeg' ||
                                $optimize === 'jpg' ? 70 : null;
                        }

                        if ($maxImageWidth && $image->width() > $maxImageWidth) {
                            $shouldResize = true;
                            $imageWidth = $maxImageWidth;
                        }

                        if ($maxImageHeight && $image->height() > $maxImageHeight) {
                            $shouldResize = true;
                            $imageHeight = $maxImageHeight;
                        }

                        if ($resize) {
                            $shouldResize = true;

                            if ($image->height() > $image->width()) {
                                $imageHeight = $image->height() - ($image->height() * ($resize / 100));
                            } else {
                                $imageWidth = $image->width() - ($image->width() * ($resize / 100));
                            }
                        }

                        if ($shouldResize) {
                            $image->resize($imageWidth, $imageHeight, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                        }

                        if ($optimize) {
                            $compressedImage = $image->encode($optimize, $quality);
                        } else {
                            $compressedImage = $image->encode();
                        }

                        $filename = self::formatFileName($filename, $optimize);
                    }

                    if ($compressedImage) {
                        Storage::disk($component->getDiskName())->put(
                            $component->getDirectory() . '/' . $filename,
                            $compressedImage->getEncoded()
                        );

                        return $component->getDirectory() . '/' . $filename;
                    }

                    if (
                        $component->shouldMoveFiles() &&
                        ($component->getDiskName() == (fn (): string => $this->disk)->call($file))
                    ) {
                        $newPath = trim($component->getDirectory() . '/' . $component->getUploadedFileNameForStorage($file), '/');

                        $component->getDisk()->move((fn (): string => $this->path)->call($file), $newPath);

                        return $newPath;
                    }

                    $storeMethod = $component->getVisibility() === 'public' ? 'storePubliclyAs' : 'storeAs';

                    return $file->{$storeMethod}(
                        $component->getDirectory(),
                        $component->getUploadedFileNameForStorage($file),
                        $component->getDiskName()
                    );
                }),
        ]);
    }

    public function optimize(string | Closure | null $optimize): static
    {
        // $this->optimize = $optimize;
        // $this->image();

        return $this;
    }

    public function getOptimization(): ?string
    {
        return $this->optimize;
    }

    public static function formatFilename(string $filename, ?string $format): string
    {
        if (! $format) {
            return $filename;
        }

        $extension = strrpos($filename, '.');

        if ($extension !== false) {
            return substr($filename, 0, $extension + 1) . $format;
        }

        return $filename;
    }
}
