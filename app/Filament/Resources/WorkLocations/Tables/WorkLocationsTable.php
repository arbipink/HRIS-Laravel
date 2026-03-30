<?php

namespace App\Filament\Resources\WorkLocations\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkLocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('resource.work_location.fields.name'))
                    ->searchable(),
                TextColumn::make('address')
                    ->label(__('resource.work_location.fields.address'))
                    ->searchable(),
                TextColumn::make('latitude')
                    ->label(__('resource.work_location.fields.latitude'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')
                    ->label(__('resource.work_location.fields.longitude'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('radius')
                    ->label(__('resource.work_location.fields.radius'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('resource.work_location.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('resource.work_location.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('resource.work_location.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([

            ]);
    }
}
