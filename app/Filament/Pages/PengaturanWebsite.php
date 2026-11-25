<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PengaturanWebsite extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.pengaturan-website';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Website';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        // Hanya admin yang boleh membuka dan berinteraksi dengan halaman pengaturan.
        return Auth::user()?->role === 'admin';
    }

    public ?array $data = [];

    public function mount(): void
    {
        // Ambil semua data dari tabel settings dan masukkan ke form
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('judul_website')
                    ->label('Judul Website'),
                    // ->required(),
                TextInput::make('lokasi_map')
                        ->label('Embed URL Google Maps')
                        ->helperText('Buka Google Maps, cari lokasi, klik "Share", pilih "Embed a map", lalu salin URL dari dalam atribut src="...".')
                        ->nullable()
                        ->rules(['url', 'regex:/^https:\/\/(www\.|maps\.)?google\.com\/maps[\/?].+/'])
                        ->validationMessages([
                            'url' => 'Format URL tidak valid. Sertakan skema https:// lengkap.',
                            'regex' => 'Hanya URL embed Google Maps resmi (https://google.com/maps/... atau https://maps.google.com/maps?... ) yang diizinkan.',
                        ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Catat identitas pengguna yang terakhir mengubah pengaturan website.
        $userId = Auth::id();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'user_id' => $userId],
            );
        }

        // Hapus cache agar halaman depan menampilkan data terbaru
        Cache::forget('settings');

        Notification::make()
            ->success()
            ->title(__('filament-panels::resources/pages/edit-record.notifications.saved.title'))
            ->send();
    }
}
