<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaporanKeuanganController extends Controller
{
    public function print(Request $request)
    {
        // Validasi ketat seluruh parameter query sebelum diproses. Bila tidak
        // valid, Laravel mengembalikan respons validasi (redirect + pesan error),
        // bukan error 500.
        $validated = $request->validate([
            'kegiatan_id' => ['nullable', 'integer', 'exists:kegiatan,id'],
            'jenis_transaksi' => ['nullable', 'string', Rule::in(['pemasukan', 'pengeluaran'])],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'tempat' => ['nullable', 'string', 'max:100', 'regex:/^[\p{L}\p{N}\s.,\-\/()\'"]+$/u'],
        ]);

        $query = Keuangan::query()->with(['kegiatan', 'user']);

        if (! empty($validated['kegiatan_id'])) {
            $query->where('kegiatan_id', $validated['kegiatan_id']);
        }

        if (! empty($validated['jenis_transaksi'])) {
            $query->where('jenis_transaksi', $validated['jenis_transaksi']);
        }

        if (! empty($validated['tanggal_mulai'])) {
            $query->whereDate('tanggal', '>=', $validated['tanggal_mulai']);
        }

        if (! empty($validated['tanggal_selesai'])) {
            $query->whereDate('tanggal', '<=', $validated['tanggal_selesai']);
        }

        $data = $query->orderBy('tanggal', 'asc')->get();

        // Carbon::parse() hanya dijalankan pada nilai yang sudah lolos validasi
        // 'date' di atas, sehingga aman dari format tanggal yang tidak sesuai.
        $periode = 'Semua Data';
        if (! empty($validated['tanggal_mulai']) && ! empty($validated['tanggal_selesai'])) {
            $periode = Carbon::parse($validated['tanggal_mulai'])->locale('id')->translatedFormat('d F Y')
                . ' - '
                . Carbon::parse($validated['tanggal_selesai'])->locale('id')->translatedFormat('d F Y');
        } elseif (! empty($validated['tanggal_mulai'])) {
            $periode = 'Mulai ' . Carbon::parse($validated['tanggal_mulai'])->locale('id')->translatedFormat('d F Y');
        } elseif (! empty($validated['tanggal_selesai'])) {
            $periode = 'Sampai ' . Carbon::parse($validated['tanggal_selesai'])->locale('id')->translatedFormat('d F Y');
        }

        return view('laporan.keuangan', [
            'data' => $data,
            'periode' => $periode,
            'tempat' => $validated['tempat'] ?? 'Denpasar',
        ]);
    }
}
