<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    /**
     * @var array<string, string>
     */
    protected array $extraBodyAttributes = [
        'class' => 'lh-login-colors',
    ];

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): ?string
    {
        return null;
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}
