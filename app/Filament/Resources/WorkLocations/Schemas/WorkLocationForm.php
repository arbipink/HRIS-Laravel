<?php

namespace App\Filament\Resources\WorkLocations\Schemas;

use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class WorkLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('resource.work_location.fields.name'))
                    ->required(),
                TextInput::make('address')
                    ->label(__('resource.work_location.fields.address')),
                TextInput::make('latitude')
                    ->label(__('resource.work_location.fields.latitude'))
                    ->required()
                    ->numeric()
                    ->live(onBlur: true),
                TextInput::make('longitude')
                    ->label(__('resource.work_location.fields.longitude'))
                    ->required()
                    ->numeric()
                    ->live(onBlur: true),
                TextInput::make('radius')
                    ->label(__('resource.work_location.fields.radius'))
                    ->required()
                    ->numeric()
                    ->default(100),
                MapPicker::make('location')
                    ->height(300)
                    ->center(0, 0)
                    ->zoom(11)
                    ->autoCenter()
                    ->columnSpanFull()
                    ->latitudeFieldName('latitude')
                    ->longitudeFieldName('longitude')
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (is_array($state)) {
                            $set('latitude', $state['lat'] ?? $state['latitude'] ?? null);
                            $set('longitude', $state['lng'] ?? $state['longitude'] ?? null);
                        }
                    }),
                Toggle::make('is_active')
                    ->label(__('resource.work_location.fields.is_active'))
                    ->required()
                    ->default(true),
            ]);
    }
}
