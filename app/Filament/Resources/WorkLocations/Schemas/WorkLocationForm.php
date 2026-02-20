<?php

namespace App\Filament\Resources\WorkLocations\Schemas;

use Filament\Forms\Components\TextInput;
use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use Filament\Schemas\Components\Utilities\Set;

class WorkLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('address'),
                TextInput::make('latitude')
                    ->required()
                    ->numeric()
                    ->live(onBlur:true),
                TextInput::make('longitude')
                    ->required()
                    ->numeric()
                    ->live(onBlur:true),
                TextInput::make('radius')
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
                    ->required()
                    ->default(true),
            ]);
    }
}
