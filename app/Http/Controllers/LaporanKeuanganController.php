<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use Illuminate\Http\Request;

class LaporanKeuanganController extends Controller
{
    public function print(Request $request)
    {
        $query = Keuangan::query()->with(['kegiatan', 'user']);

        if ($request->has('kegiatan_id') && $request->kegiatan_id) {
            $query->where('kegiatan_id', $request->kegiatan_id);
        }

        if ($request->has('jenis_transaksi') && $request->jenis_transaksi) {
            $query->where('jenis_transaksi', $request->jenis_transaksi);
        }

        if ($request->has('tanggal_mulai') && $request->tanggal_mulai) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }

        if ($request->has('tanggal_selesai') && $request->tanggal_selesai) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        }

        $data = $query->orderBy('tanggal', 'asc')->get();

        $periode = 'Semua Data';
        if ($request->tanggal_mulai && $request->tanggal_selesai) {
            $periode = \Carbon\Carbon::parse($request->tanggal_mulai)->locale('id')->translatedFormat('d F Y') . ' - ' . \Carbon\Carbon::parse($request->tanggal_selesai)->locale('id')->translatedFormat('d F Y');
        } elseif ($request->tanggal_mulai) {
            $periode = 'Mulai ' . \Carbon\Carbon::parse($request->tanggal_mulai)->locale('id')->translatedFormat('d F Y');
        } elseif ($request->tanggal_selesai) {
            $periode = 'Sampai ' . \Carbon\Carbon::parse($request->tanggal_selesai)->locale('id')->translatedFormat('d F Y');
        }

        return view('laporan.keuangan', [
            'data' => $data,
            'periode' => $periode,
            'tempat' => $request->get('tempat', 'Denpasar'),
        ]);
    }
}
