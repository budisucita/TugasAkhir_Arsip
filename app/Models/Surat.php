<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
