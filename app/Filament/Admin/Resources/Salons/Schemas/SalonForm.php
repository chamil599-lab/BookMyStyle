<?php

namespace App\Filament\Admin\Resources\Salons\Schemas;

use App\Models\User;
use App\Support\ManagementAccess;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;

class SalonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Tabs::make('Salon Details')
                            ->tabs([
                                Tabs\Tab::make('Basic Information')
                                    ->icon('heroicon-o-information-circle')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Salon Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Salon name'),

                                        TextInput::make('slug')
                                            ->label('Slug')
                                            ->maxLength(255)
                                            ->placeholder('my-salon')
                                            ->helperText('URL-friendly identifier. Leave blank to auto-generate.'),

                                        TextInput::make('owner_name')
                                            ->label('Owner Name')
                                            ->required()
                                            ->maxLength(255),

                                        Select::make('owner_id')
                                            ->label('Owner account')
                                            ->options(fn () => User::role('owner')->where('is_approved', true)->pluck('name', 'id'))
                                            ->visible(fn ($operation) => $operation === 'create' && ManagementAccess::admin(auth()->user()))
                                            ->required(fn ($operation) => $operation === 'create' && ManagementAccess::admin(auth()->user()))
                                            ->searchable(),

                                        TextInput::make('location')
                                            ->label('Location')
                                            ->maxLength(255),

                                        TextInput::make('map_url')
                                            ->label('Map URL')
                                            ->url()
                                            ->maxLength(2048)
                                            ->placeholder('https://maps.app.goo.gl/...')
                                            ->helperText('Paste the salon location link from your map app. Leave blank to use the address for directions.')
                                            ->columnSpanFull(),

                                        Textarea::make('description')
                                            ->label('Description')
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->placeholder('Describe this salon...'),
                                    ]),

                                Tabs\Tab::make('Contact & Hours')
                                    ->icon('heroicon-o-phone')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('phone')
                                            ->label('Phone')
                                            ->tel(),

                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email(),

                                        TimePicker::make('open_time')
                                            ->label('Opening Time'),

                                        TimePicker::make('close_time')
                                            ->label('Closing Time'),

                                        TextInput::make('facebook_page')
                                            ->label('Facebook Page URL')
                                            ->url()
                                            ->placeholder('https://facebook.com/yoursalon')
                                            ->columnSpanFull(),

                                        TextInput::make('instagram_page')
                                            ->label('Instagram Page URL')
                                            ->url()
                                            ->placeholder('https://instagram.com/yoursalon')
                                            ->columnSpanFull(),

                                        TextInput::make('tiktok_page')
                                            ->label('TikTok Page URL')
                                            ->url()
                                            ->placeholder('https://tiktok.com/@yoursalon')
                                            ->columnSpanFull(),
                                    ]),

                                Tabs\Tab::make('Media')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('cover')
                                            ->collection('cover')
                                            ->label('Cover Image')
                                            ->image()
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('16:9')
                                            ->imageEditor()
                                            ->deletable(true)
                                            ->downloadable(true)
                                            ->openable(true)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                                            ->maxSize(1024)
                                            ->rules(['dimensions:max_width=6000,max_height=6000'])
                                            ->helperText('Recommended size: 1920x1080px. Max: 1MB. Formats: JPG, PNG, WebP')
                                            ->columnSpanFull()
                                            ->visibility('public')
                                            ->deleteUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): void {
                                                $record = $component->getRecord();
                                                if (! $record) {
                                                    return;
                                                }
                                                Gate::authorize('update', $record->fresh());
                                                $record->getMedia($component->getCollection())
                                                    ->first(fn ($media) => $media->uuid === $file)
                                                    ?->delete();
                                            }),

                                        SpatieMediaLibraryFileUpload::make('gallery')
                                            ->collection('gallery')
                                            ->label('Gallery Images')
                                            ->image()
                                            ->multiple()
                                            ->reorderable()
                                            ->appendFiles()
                                            ->maxFiles(10)
                                            ->imageEditor()
                                            ->deletable(true)
                                            ->downloadable(true)
                                            ->openable(true)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                                            ->maxSize(1024)
                                            ->rules(['dimensions:max_width=6000,max_height=6000'])
                                            ->helperText('Maximum 10 images. Max file size: 1MB each. Drag to reorder.')
                                            ->columnSpanFull()
                                            ->visibility('public')
                                            ->deleteUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, string $file): void {
                                                $record = $component->getRecord();
                                                if (! $record) {
                                                    return;
                                                }
                                                Gate::authorize('update', $record->fresh());
                                                $record->getMedia($component->getCollection())
                                                    ->first(fn ($media) => $media->uuid === $file)
                                                    ?->delete();
                                            }),
                                    ]),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                Toggle::make('show_price')
                                    ->label('Show Prices to Customers')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false)
                                    ->helperText('Enable to display prices to customers on the website/app'),

                                TextInput::make('number_of_staff')
                                    ->label('Number of Staff')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->default(0)
                                    ->helperText('Lower number means fewer available booking slots.'),
                            ]),
                    ])
                    ->columnSpan(1),

            ]);
    }
}
