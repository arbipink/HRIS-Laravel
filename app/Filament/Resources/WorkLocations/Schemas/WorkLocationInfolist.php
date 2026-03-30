<?php

namespace App\Filament\Resources\WorkLocations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WorkLocationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('resource.work_location.fields.name')),
                TextEntry::make('address')
                    ->label(__('resource.work_location.fields.address'))
                    ->placeholder('-'),
                TextEntry::make('latitude')
                    ->label(__('resource.work_location.fields.latitude'))
                    ->numeric(),
                TextEntry::make('longitude')
                    ->label(__('resource.work_location.fields.longitude'))
                    ->numeric(),
                TextEntry::make('radius')
                    ->label(__('resource.work_location.fields.radius'))
                    ->numeric(),
                IconEntry::make('is_active')
                    ->label(__('resource.work_location.fields.is_active'))
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label(__('resource.work_location.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('resource.work_location.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
