<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'model',       // Pastikan ini ada!
        'record_id',   // Pastikan ini ada!
        'keuangan_id', // Biarkan ada tapi nanti kita isi null
        'action',
        'description',
        'before',
        'after',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keuangan(): BelongsTo
    {
        return $this->belongsTo(Keuangan::class);
    }

    public function subject()
    {
        // Ini mengasumsikan kamu menyimpan nama full class di kolom 'model'
        // Contoh: App\Models\Keuangan
        return $this->morphTo(__FUNCTION__, 'model', 'record_id');
    }
}
