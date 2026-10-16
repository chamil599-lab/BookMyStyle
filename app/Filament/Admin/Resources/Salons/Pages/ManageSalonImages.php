<?php

namespace App\Filament\Admin\Resources\Salons\Pages;

use App\Filament\Admin\Resources\Salons\SalonResource;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Form;
use BackedEnum;
use Filament\Resources\Pages\EditRecord;

class ManageSalonImages extends EditRecord
{
    protected static string $resource = SalonResource::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $title = 'Manage Salon Images';

    public static function getNavigationLabel(): string
    {
        return 'Images';
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Cover Image')
                ->description('Upload a cover image for your salon (16:9 aspect ratio recommended)')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('cover')
                        ->collection('cover')
                        ->label('Cover Image')
                        ->image()
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->imageEditor()
                        ->deletable()
                        ->downloadable()
                        ->openable()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                        ->maxSize(1024) // 1MB
                        ->helperText('Maximum file size: 1MB. Accepted formats: JPG, PNG, WebP')
                        ->columnSpanFull()
                        ->visibility('public')
                        ->deleteUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): void {
                            $record = $component->getRecord();
                            if (! $record) {
                                return;
                            }
                            $record->getMedia($component->getCollection())
                                ->first(fn ($media) => $media->uuid === $file)
                                ?->delete();
                        }),
                ]) ,

            Section::make('Gallery Images')
                ->description('Upload multiple images to showcase your salon')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('gallery')
                        ->collection('gallery')
                        ->label('Gallery Images')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->appendFiles()
                        ->maxFiles(10)
                        ->imageEditor()
                        ->deletable()
                        ->downloadable()
                        ->openable()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                        ->maxSize(1024) // 1MB per file
                        ->helperText('Maximum 10 images. Max file size: 1MB each. Accepted formats: JPG, PNG, WebP')
                        ->columnSpanFull()
                        ->visibility('public')
                        ->deleteUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): void {
                            $record = $component->getRecord();
                            if (! $record) {
                                return;
                            }
                            $record->getMedia($component->getCollection())
                                ->first(fn ($media) => $media->uuid === $file)
                                ?->delete();
                        }),
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
