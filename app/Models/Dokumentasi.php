<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Dokumentasi extends Model
{
    use HasFactory;

    protected $table = 'dokumentasi';

    protected $fillable = [
        'kegiatan_id',
        'user_id',
        'tipe',
        'file_path',
        'deskripsi',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function getFilePathAttribute($value)
    {
        // If it looks like a JSON string (starts and ends with quote), decode it
        if (is_string($value) && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return json_decode($value);
        }
        return $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL publik terpusat untuk berkas dokumentasi.
     *
     * Selalu memakai disk default aplikasi (env FILESYSTEM_DISK) sebagai satu
     * sumber kebenaran sehingga konsisten di lokal (public) maupun saat
     * dideploy dengan S3.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (blank($this->file_path)) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }

        if (config('filesystems.default') === 's3') {
            return Storage::disk('s3')->url($this->file_path);
        }

        return '/storage/' . ltrim($this->file_path, '/');
    }
}
