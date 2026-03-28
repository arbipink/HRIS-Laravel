<?php

namespace App\Filament\Resources\WorkLocations;

use App\Filament\Resources\WorkLocations\Pages\CreateWorkLocation;
use App\Filament\Resources\WorkLocations\Pages\EditWorkLocation;
use App\Filament\Resources\WorkLocations\Pages\ListWorkLocations;
use App\Filament\Resources\WorkLocations\Pages\ViewWorkLocation;
use App\Filament\Resources\WorkLocations\Schemas\WorkLocationForm;
use App\Filament\Resources\WorkLocations\Schemas\WorkLocationInfolist;
use App\Filament\Resources\WorkLocations\Tables\WorkLocationsTable;
use App\Models\WorkLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkLocationResource extends Resource
{
    protected static ?string $model = WorkLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MapPin;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.work_location');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.settings');
    }

    public static function getModelLabel(): string
    {
        return __('models.singular.work_location');
    }

    public static function getPluralModelLabel(): string
    {
        return __('models.plural.work_location');
    }

    public static function form(Schema $schema): Schema
    {
        return WorkLocationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WorkLocationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkLocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkLocations::route('/'),
            'create' => CreateWorkLocation::route('/create'),
            'view' => ViewWorkLocation::route('/{record}'),
            'edit' => EditWorkLocation::route('/{record}/edit'),
        ];
    }
}
