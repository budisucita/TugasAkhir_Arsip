<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
