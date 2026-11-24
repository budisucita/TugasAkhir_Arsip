<?php

namespace App\Filament\Resources\TemplateSuratResource\Pages;

use App\Filament\Resources\TemplateSuratResource;
use App\Models\TemplateSurat;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateTemplateSurat extends CreateRecord
{
    protected static string $resource = TemplateSuratResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            return TemplateSurat::create($data);
        } catch (\Throwable $e) {
            Log::error('TemplateSurat insert failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Notification::make()
                ->title('Gagal membuat Template Surat')
                ->body('Terjadi kesalahan saat menyimpan. Periksa format file dan koneksi.')
                ->danger()
                ->send();
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return TemplateSuratResource::getUrl('index');
    }
}
