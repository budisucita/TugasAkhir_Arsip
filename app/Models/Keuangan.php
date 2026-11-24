<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Models\Kegiatan;
use App\Models\User;
use App\Models\Log;



class Keuangan extends Model
{
    use HasFactory;

    protected $table = 'keuangan';
    protected $dispatchesEventsAfterCommit = true;

    protected $fillable = [
        'kegiatan_id',
        'user_id',
        'jenis_transaksi',
        'jumlah',
        'keterangan',
        'bukti_file',
        'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($keuangan) {
            // 1. Hapus bukti file utama (jika ada)
            if ($keuangan->bukti_file && Storage::disk('public')->exists($keuangan->bukti_file)) {
                Storage::disk('public')->delete($keuangan->bukti_file);
            }
        });
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }
}
