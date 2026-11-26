<?php

namespace App\Filament\Resources\KeuanganResource\Pages;

use App\Filament\Resources\KeuanganResource;
use App\Filament\Widgets\StatsOverview;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Support\KeuanganExcelImporter;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListKeuangans extends ListRecords
{
    protected static string $resource = KeuanganResource::class;

    /**
     * Ringkasan pratinjau hasil parsing berkas Excel pada Langkah 1 wizard
     * impor, ditampilkan pada Langkah 2 sebelum data benar-benar disimpan.
     *
     * @var array<string, mixed>|null
     */
    public ?array $importPreview = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak_laporan')
                ->label('Cetak Laporan')
                ->icon('heroicon-o-printer')
                ->modalHeading('Cetak Laporan Keuangan')
                ->form([
                    Forms\Components\Select::make('kegiatan_id')
                        ->label('Pilih Kegiatan')
                        ->relationship('kegiatan', 'nama_kegiatan')
                        ->searchable()
                        ->preload()
                        ->placeholder('Semua Kegiatan'),
                    Forms\Components\Select::make('jenis_transaksi')
                        ->label('Jenis Transaksi')
                        ->options([
                            'pemasukan' => 'Pemasukan',
                            'pengeluaran' => 'Pengeluaran',
                        ])
                        ->placeholder('Semua Transaksi'),
                    Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Dari Tanggal'),
                    Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Sampai Tanggal'),
                 ])
                ->action(function (array $data) {
                    $url = route('laporan.keuangan.print') . '?' . http_build_query(array_filter($data));
                    $this->js('window.open("'.$url.'", "_blank")');
                }),
            Actions\Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->modalHeading('Export Laporan Keuangan (.xlsx)')
                ->form([
                    Forms\Components\Select::make('kegiatan_id')
                        ->label('Pilih Kegiatan')
                        ->relationship('kegiatan', 'nama_kegiatan')
                        ->searchable()
                        ->preload()
                        ->placeholder('Semua Kegiatan'),
                    Forms\Components\Select::make('jenis_transaksi')
                        ->label('Jenis Transaksi')
                        ->options([
                            'pemasukan' => 'Pemasukan',
                            'pengeluaran' => 'Pengeluaran',
                        ])
                        ->placeholder('Semua Transaksi'),
                    Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Dari Tanggal'),
                    Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Sampai Tanggal'),
                 ])
                ->action(function (array $data) {
                    $url = route('laporan.keuangan.export') . '?' . http_build_query(array_filter($data));
                    $this->js('window.open("'.$url.'", "_blank")');
                }),
            Actions\Action::make('impor_excel')
                ->label('Impor Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->authorize('create', Keuangan::class)
                ->modalHeading('Impor Data Keuangan dari Excel')
                ->modalDescription('Unggah berkas .xlsx berformat "Buku Kas". Data baru disimpan setelah Anda mengonfirmasi pada langkah kedua.')
                ->modalWidth('5xl')
                ->form([
                    Forms\Components\Wizard::make([
                        Forms\Components\Wizard\Step::make('Unggah & Validasi')
                            ->description('Pilih kegiatan tujuan lalu unggah berkas Excel.')
                            ->schema([
                                Forms\Components\Select::make('kegiatan_id')
                                    ->label('Kegiatan Tujuan')
                                    ->relationship('kegiatan', 'nama_kegiatan')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\FileUpload::make('file')
                                    ->label('Berkas Excel (.xlsx)')
                                    ->acceptedFileTypes([
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/vnd.ms-excel',
                                    ])
                                    ->maxSize(10240)
                                    ->storeFiles(false)
                                    ->required(),
                            ])
                            ->afterValidation(function ($get): void {
                                $this->importPreview = $this->analyzeImport(
                                    $get('file'),
                                    (int) $get('kegiatan_id'),
                                );
                            }),
                        Forms\Components\Wizard\Step::make('Konfirmasi & Simpan')
                            ->description('Tinjau ringkasan berikut sebelum menyimpan.')
                            ->schema([
                                Forms\Components\Placeholder::make('pratinjau_impor')
                                    ->hiddenLabel()
                                    ->content(fn (): HtmlString => $this->renderImportPreview()),
                            ]),
                    ]),
                ])
                ->action(function (array $data): void {
                    // Pertahanan berlapis: verifikasi ulang hak akses pada saat
                    // eksekusi, bukan hanya saat tombol ditampilkan.
                    Gate::authorize('create', Keuangan::class);

                    $kegiatanId = (int) ($data['kegiatan_id'] ?? 0);
                    $result = $this->analyzeImport($data['file'] ?? null, $kegiatanId);

                    if ($result['fatal'] !== null) {
                        Notification::make()
                            ->danger()
                            ->title('Impor gagal')
                            ->body($result['fatal'])
                            ->send();

                        return;
                    }

                    $saved = 0;

                    if ($result['rows'] !== []) {
                        $saved = app(KeuanganExcelImporter::class)->persist(
                            $result['rows'],
                            $kegiatanId,
                            (int) auth()->id(),
                        );
                    }

                    Cache::forget(StatsOverview::CACHE_KEY);

                    Notification::make()
                        ->success()
                        ->title('Impor Excel selesai')
                        ->body(sprintf(
                            'Berhasil disimpan: %d baris. Dilewati: %d baris. Kesalahan: %d baris.',
                            $saved,
                            count($result['skipped']),
                            count($result['errors']),
                        ))
                        ->persistent()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Menganalisis berkas Excel yang diunggah dan melengkapinya dengan
     * metadata kegiatan tujuan untuk keperluan pratinjau.
     *
     * @return array<string, mixed>
     */
    protected function analyzeImport(mixed $file, int $kegiatanId): array
    {
        $upload = $this->resolveUpload($file);

        if ($upload === null) {
            return [
                'fatal' => 'Berkas Excel tidak ditemukan. Silakan unggah ulang berkas .xlsx.',
                'kegiatan_id' => $kegiatanId,
                'kegiatan_nama' => (string) (Kegiatan::query()->whereKey($kegiatanId)->value('nama_kegiatan') ?? ''),
                'rows' => [],
                'skipped' => [],
                'errors' => [],
                'total_pemasukan' => 0.0,
                'total_pengeluaran' => 0.0,
            ];
        }

        $tempPath = $this->materializeUpload($upload);

        try {
            $result = app(KeuanganExcelImporter::class)->analyze($tempPath, $kegiatanId);
        } catch (\Throwable $exception) {
            $result = [
                'fatal' => 'Berkas tidak dapat dibaca sebagai Excel (.xlsx): ' . $exception->getMessage(),
                'rows' => [],
                'skipped' => [],
                'errors' => [],
                'total_pemasukan' => 0.0,
                'total_pengeluaran' => 0.0,
            ];
        } finally {
            @unlink($tempPath);
        }

        return [
            'kegiatan_id' => $kegiatanId,
            'kegiatan_nama' => (string) (Kegiatan::query()->whereKey($kegiatanId)->value('nama_kegiatan') ?? ''),
            ...$result,
        ];
    }

    /**
     * Menyelesaikan berkas sementara hasil unggahan Livewire.
     */
    protected function resolveUpload(mixed $file): ?TemporaryUploadedFile
    {
        if (is_array($file)) {
            $file = array_values($file)[0] ?? null;
        }

        return $file instanceof TemporaryUploadedFile ? $file : null;
    }

    /**
     * Menyalin berkas unggahan ke lintasan sementara yang pendek sebelum dibaca.
     *
     * Livewire menyimpan berkas sementara pada lintasan yang sangat dalam.
     * Pada Windows, lintasan tersebut dapat melampaui batas MAX_PATH (260
     * karakter) sehingga ZipArchive (OpenSpout) gagal membuka berkas.
     */
    protected function materializeUpload(TemporaryUploadedFile $upload): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'keuangan_impor_');
        copy($upload->getRealPath(), $tempPath);

        return $tempPath;
    }

    /**
     * Merender ringkasan pratinjau impor sebagai HTML aman-escape.
     */
    protected function renderImportPreview(): HtmlString
    {
        $preview = $this->importPreview;

        if ($preview === null) {
            return new HtmlString('<p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data untuk dipratinjau.</p>');
        }

        if (($preview['fatal'] ?? null) !== null) {
            return new HtmlString('<p class="text-sm font-medium text-danger-600">' . e($preview['fatal']) . '</p>');
        }

        $kegiatan = $preview['kegiatan_nama'] !== ''
            ? $preview['kegiatan_nama']
            : '#' . $preview['kegiatan_id'];

        $summary = [
            'Kegiatan' => $kegiatan,
            'Baris siap diimpor' => number_format(count($preview['rows']), 0, ',', '.'),
            'Total pemasukan' => 'Rp ' . number_format($preview['total_pemasukan'], 2, ',', '.'),
            'Total pengeluaran' => 'Rp ' . number_format($preview['total_pengeluaran'], 2, ',', '.'),
            'Baris dilewati' => number_format(count($preview['skipped']), 0, ',', '.'),
            'Baris bermasalah' => number_format(count($preview['errors']), 0, ',', '.'),
        ];

        $html = '<div class="space-y-1 text-sm">';

        foreach ($summary as $label => $value) {
            $html .= '<div><span class="font-medium">' . e($label) . ':</span> ' . e($value) . '</div>';
        }

        $html .= '</div>';

        $html .= $this->renderPreviewList('Baris bermasalah', $preview['errors'], 'text-danger-600');
        $html .= $this->renderPreviewList('Baris dilewati', $preview['skipped'], 'text-gray-500 dark:text-gray-400');

        return new HtmlString($html);
    }

    /**
     * @param  array<int, array{line: int, reason: string}>  $items
     */
    protected function renderPreviewList(string $title, array $items, string $colorClass): string
    {
        if ($items === []) {
            return '';
        }

        $html = '<div class="mt-3 text-sm ' . $colorClass . '"><p class="font-medium">' . e($title) . ':</p><ul class="list-disc ps-5">';

        foreach ($items as $item) {
            $html .= '<li>' . e('Baris ' . $item['line'] . ': ' . $item['reason']) . '</li>';
        }

        return $html . '</ul></div>';
    }
}
