<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Support\SecureFile;

class Kegiatan extends Model
{
    use HasFactory;

    protected $table = 'kegiatan';

    protected $fillable = [
        'user_id',
        'nama_kegiatan',
        'lokasi',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'deskripsi',
        'proposal_file',
        'rab_file',
        'lpj_file',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($kegiatan) {
            // Hapus seluruh berkas kegiatan dari disk privat saat record dihapus.
            foreach (['proposal_file', 'rab_file', 'lpj_file'] as $field) {
                if ($kegiatan->{$field} && Storage::disk(SecureFile::diskName())->exists($kegiatan->{$field})) {
                    Storage::disk(SecureFile::diskName())->delete($kegiatan->{$field});
                }
            }
        });
    }

    /**
     * URL terproteksi untuk berkas kegiatan (diakses lewat controller).
     */
    public function getProposalUrlAttribute(): ?string
    {
        return SecureFile::urlFor($this, 'proposal_file');
    }

    public function getRabUrlAttribute(): ?string
    {
        return SecureFile::urlFor($this, 'rab_file');
    }

    public function getLpjUrlAttribute(): ?string
    {
        return SecureFile::urlFor($this, 'lpj_file');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keuangan(): HasMany
    {
        return $this->hasMany(Keuangan::class);
    }

    public function dokumentasi(): HasMany
    {
        return $this->hasMany(Dokumentasi::class);
    }
}
