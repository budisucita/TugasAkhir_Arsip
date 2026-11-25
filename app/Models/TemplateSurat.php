<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TemplateSurat extends Model
{
    protected $table = 'template_surats';

    protected $fillable = [
        'user_id',
        'nama_dokumen',
        'file_path',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($template) {
            // Hapus berkas fisik template dari disk default saat record dihapus,
            // agar tidak menyisakan berkas yatim di penyimpanan.
            if ($template->file_path && Storage::disk(config('filesystems.default'))->exists($template->file_path)) {
                Storage::disk(config('filesystems.default'))->delete($template->file_path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
