<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Support\SecureFile;

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
            // Hapus berkas fisik template dari disk privat saat record dihapus,
            // agar tidak menyisakan berkas yatim di penyimpanan.
            if ($template->file_path && Storage::disk(SecureFile::diskName())->exists($template->file_path)) {
                Storage::disk(SecureFile::diskName())->delete($template->file_path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
