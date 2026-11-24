<?php

namespace App\Filament\Resources\DokumentasiResource\Pages;

use App\Filament\Resources\DokumentasiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDokumentasi extends CreateRecord
{
    protected static string $resource = DokumentasiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
