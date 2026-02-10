<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Profile extends Page
{
    protected string $view = 'filament.pages.profile';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;
}
