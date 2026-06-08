<?php

namespace App\Filament\Navigation;

enum AdminNavigationGroup: string
{
    case Panou = 'Panou principal';
    case Proprietati = 'Proprietăți';
    case Rezervari = 'Rezervări';
    case Reduceri = 'Reduceri';
    case Administrare = 'Administrare';
}
