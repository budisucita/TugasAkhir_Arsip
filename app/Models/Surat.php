<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Support\SecureFile;

class Surat extends Model
{
    use HasFactory;

    protected $table = 'surat';

    protected $fillable = [
        'user_id',
        'no_surat',
        'pengirim',
        'tanggal_terima',
        'status',
        'perihal',
        'file_surat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_terima' => 'date',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($surat) {
            // Hapus berkas surat dari disk privat saat record dihapus.
            if ($surat->file_surat && Storage::disk(SecureFile::diskName())->exists($surat->file_surat)) {
                Storage::disk(SecureFile::diskName())->delete($surat->file_surat);
            }
        });
    }

    /**
     * URL terproteksi untuk berkas surat (diakses lewat controller).
     */
    public function getFileSuratUrlAttribute(): ?string
    {
        return SecureFile::urlFor($this, 'file_surat');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
