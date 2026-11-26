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

    /**
     * Proxy unduhan berkas privat lewat rute /admin/dokumen/{tipe}/{id}.
     *
     * Wajib login (middleware `auth`) dan berwenang (Policy `view`) sebelum
     * berkas dialirkan. Berkas dilayani dari disk privat yang dikonfigurasi
     * (SecureFile::disk()), sehingga bukti transaksi, surat resmi, proposal,
     * RAB, dan LPJ tidak pernah bocor lewat URL /storage publik.
     *
     * Tipe dengan lebih dari satu berkas (mis. kegiatan: proposal/RAB/LPJ)
     * dapat memilih kolom lewat query `?field=`; tanpa itu dipakai kolom
     * pertama tipe tersebut.
     */
    public function download(Request $request, string $tipe, int $id): StreamedResponse
    {
        $definition = SecureFile::resolve($tipe);

        // Tipe berkas tidak dikenali -> tidak ada yang bisa disajikan.
        abort_if($definition === null, 404);

        $requested = $request->query('field');

        // Normalisasi ke nama kolom tanpa prefiks direktori bila diberikan.
        $field = filled($requested)
            ? str($requested)->afterLast('.')->toString()
            : null;

        if ($field === null || ! in_array($field, $definition['fields'], true)) {
            $field = $definition['fields'][0];
        }

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
