<?php

namespace App\Http\Controllers;

use App\Support\SecureFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan berkas sensitif (bukti transaksi, surat resmi, proposal, RAB,
 * LPJ, dan foto anggota) dari disk privat.
 *
 * Rute controller ini dilindungi middleware `auth` dan menerapkan Policy
 * `view` pada model pemilik berkas, sehingga berkas tidak dapat diakses
 * langsung lewat /storage oleh pengguna yang tidak berwenang.
 */
class SecureFileController extends Controller
{
    /**
     * Alirkan berkas sensitif ke pengguna yang terautentikasi dan berwenang.
     */
    public function show(Request $request, string $type, int $id, string $field): StreamedResponse
    {
        $definition = SecureFile::resolve($type);

        // Tipe berkas tidak dikenali -> tidak ada yang bisa disajikan.
        abort_if($definition === null, 404);

        $field = str($field)->afterLast('.')->toString();

        // Kolom tidak termasuk berkas sensitif untuk tipe ini.
        abort_unless(in_array($field, $definition['fields'], true), 404);

        $modelClass = $definition['model'];

        /** @var \Illuminate\Database\Eloquent\Model $record */
        $record = $modelClass::query()->findOrFail($id);

        // Lapisan otorisasi: pengguna harus berhak melihat record pemilik berkas.
        Gate::authorize('view', $record);

        $path = $record->{$field};

        // Tidak ada berkas tercatat untuk kolom ini.
        abort_if(blank($path), 404);

        $disk = SecureFile::disk();

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
