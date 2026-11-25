<?php

namespace App\Support;

use App\Models\Anggota;
use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\Surat;
use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Pusat kebijakan berkas sensitif.
 *
 * Berkas sensitif (bukti transaksi, surat resmi, proposal, RAB, LPJ, dan foto
 * anggota) disimpan pada disk privat dan tidak boleh diakses langsung lewat
 * /storage. Semua akses harus melalui rute terproteksi (SecureFileController)
 * yang memverifikasi autentikasi dan Policy.
 */
class SecureFile
{
    /**
     * Pemetaan slug tipe -> model + kolom-kolom berkas sensitif yang dilindungi.
     *
     * @var array<string, array{model: class-string<Model>, fields: array<int, string>}>
     */
    public const MAP = [
        'keuangan' => ['model' => Keuangan::class, 'fields' => ['bukti_file']],
        'surat' => ['model' => Surat::class, 'fields' => ['file_surat']],
        'kegiatan' => ['model' => Kegiatan::class, 'fields' => ['proposal_file', 'rab_file', 'lpj_file']],
        'anggota' => ['model' => Anggota::class, 'fields' => ['foto']],
    ];

    /**
     * Direktori di disk penyimpanan yang menampung berkas sensitif, beserta
     * slug tipenya. Dipakai untuk memindai/memigrasikan berkas sensitif
     * (termasuk berkas yatim yang tidak lagi direferensikan record).
     *
     * @var array<string, string>
     */
    public const DIRECTORIES = [
        'keuangan/bukti' => 'keuangan',
        'surat/file' => 'surat',
        'kegiatan/proposal' => 'kegiatan',
        'kegiatan/rab' => 'kegiatan',
        'kegiatan/lpj' => 'kegiatan',
        'anggota/foto' => 'anggota',
    ];

    /**
     * Nama disk privat yang dipakai untuk menyimpan berkas sensitif.
     */
    public static function diskName(): string
    {
        return config('filesystems.private_disk', 'private');
    }

    /**
     * Instance disk privat.
     */
    public static function disk(): Filesystem
    {
        return Storage::disk(static::diskName());
    }

    /**
     * Ambil definisi tipe berdasarkan slug, atau null bila tidak dikenali.
     *
     * @return array{model: class-string<Model>, fields: array<int, string>}|null
     */
    public static function resolve(string $type): ?array
    {
        return static::MAP[$type] ?? null;
    }

    /**
     * Tentukan slug tipe untuk kombinasi model + kolom berkas.
     */
    public static function typeFor(Model $record, string $field): ?string
    {
        $field = str($field)->afterLast('.')->toString();

        foreach (static::MAP as $type => $definition) {
            if ($record instanceof $definition['model'] && in_array($field, $definition['fields'], true)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * URL rute terproteksi untuk berkas sensitif milik sebuah record.
     *
     * Mengembalikan null bila record/kolom tidak termasuk berkas terproteksi
     * atau nilainya kosong.
     */
    public static function urlFor(?Model $record, ?string $field): ?string
    {
        if (! $record instanceof Model || blank($field)) {
            return null;
        }

        $field = str($field)->afterLast('.')->toString();
        $type = static::typeFor($record, $field);

        if ($type === null || blank($record->{$field} ?? null)) {
            return null;
        }

        return route('secure-file.show', [
            'type' => $type,
            'id' => $record->getKey(),
            'field' => $field,
        ]);
    }

    /**
     * Closure siap pakai untuk FileUpload::getUploadedFileUsing(), agar pratinjau
     * berkas yang tersimpan di disk privat disajikan lewat rute terproteksi
     * (bukan URL /storage yang bocor).
     */
    public static function uploadedFileUsing(): Closure
    {
        return function (FileUpload $component, string $file, string|array|null $storedFileNames = null): ?array {
            $disk = $component->getDisk();

            if (! $disk->exists($file)) {
                return null;
            }

            $field = str($component->getName())->afterLast('.')->toString();
            $url = static::urlFor($component->getRecord(), $field);

            // Tanpa record/kolom valid, tidak ada URL aman yang bisa dibuat.
            if ($url === null) {
                return null;
            }

            return [
                'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                'size' => $disk->size($file),
                'type' => $disk->mimeType($file),
                'url' => $url,
            ];
        };
    }
}
