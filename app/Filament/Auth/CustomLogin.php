<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\Login;
use Filament\Support\Enums\Alignment;

class CustomLogin extends Login
{
    public function getHeading(): string
    {
        return 'Masuk ke Arsip Dharma Bhakti';
    }

    public function getSubheading(): ?string
    {
        return 'Silakan masukkan kredensial Anda untuk melanjutkan.';
    }
}
