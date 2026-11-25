<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Support\SecureFile;

class Anggota extends Model
{
    use HasFactory;

    protected $table = 'anggota';

    protected $fillable = [
        'user_id',
        'nama',
        'kontak',
        'status',
        'alamat',
        'foto',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($anggota) {
            // Hapus foto anggota dari disk privat saat record dihapus.
            if ($anggota->foto && Storage::disk(SecureFile::diskName())->exists($anggota->foto)) {
                Storage::disk(SecureFile::diskName())->delete($anggota->foto);
            }
        });
    }

    /**
     * URL terproteksi untuk foto anggota (diakses lewat controller).
     */
    public function getFotoUrlAttribute(): ?string
    {
        return SecureFile::urlFor($this, 'foto');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
