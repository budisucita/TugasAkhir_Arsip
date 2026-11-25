<?php

namespace App\Observers;

use App\Filament\Widgets\StatsOverview;
use App\Models\Keuangan;
use App\Models\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log as SysLog;
use Illuminate\Support\Facades\Auth;


class KeuanganObserver
{
    /**
     * Handle the Keuangan "created" event.
     */
    public function created(Keuangan $keuangan): void
    {
        $this->clearCache();

        try {
            Log::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'model' => Keuangan::class,
                'record_id' => $keuangan->id,
                'keuangan_id' => $keuangan->id,
                'description' => "Mencatat {$keuangan->jenis_transaksi} baru sejumlah Rp " . number_format($keuangan->jumlah, 0, ',', '.'),
                'after' => $keuangan->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log keuangan (created)', [
                'error' => $e->getMessage(),
                'keuangan_id' => $keuangan->id ?? null,
            ]);
        }
    }

    /**
     * Handle the Keuangan "updated" event.
     */
    public function updated(Keuangan $keuangan): void
    {
        $this->clearCache();

        $perubahan = [];

        // 1. Cek Perubahan KEGIATAN
        if ($keuangan->isDirty('kegiatan_id')) {
            $oldId = $keuangan->getOriginal('kegiatan_id');
            // Cari nama kegiatan lama (perlu query sedikit agar log jelas)
            $oldKegiatan = \App\Models\Kegiatan::find($oldId); 
            $oldName = $oldKegiatan ? $oldKegiatan->nama_kegiatan : 'N/A';
            
            $newName = $keuangan->kegiatan ? $keuangan->kegiatan->nama_kegiatan : 'N/A';
            
            $perubahan[] = "Kegiatan ({$oldName} -> {$newName})";
        }

        // 2. Cek Perubahan JUMLAH (Nominal)
        if ($keuangan->isDirty('jumlah')) {
            $oldJumlah = 'Rp ' . number_format($keuangan->getOriginal('jumlah'), 0, ',', '.');
            $newJumlah = 'Rp ' . number_format($keuangan->jumlah, 0, ',', '.');
            
            $perubahan[] = "Nominal ({$oldJumlah} -> {$newJumlah})";
        }

        // 3. Cek Perubahan TANGGAL
        if ($keuangan->isDirty('tanggal')) {
            // Menggunakan Carbon untuk format tanggal agar enak dibaca
            $oldDate = \Carbon\Carbon::parse($keuangan->getOriginal('tanggal'))->format('d M Y');
            $newDate = \Carbon\Carbon::parse($keuangan->tanggal)->format('d M Y');
            
            $perubahan[] = "Dari Tanggal ({$oldDate} -> {$newDate})";
        }

        // 4. Cek Perubahan JENIS TRANSAKSI
        if ($keuangan->isDirty('jenis_transaksi')) {
            $oldType = ucfirst($keuangan->getOriginal('jenis_transaksi'));
            $newType = ucfirst($keuangan->jenis_transaksi);
            
            $perubahan[] = "Tipe ({$oldType} -> {$newType})";
        }

        // 5. Cek Perubahan KETERANGAN
        if ($keuangan->isDirty('keterangan')) {
            $perubahan[] = "Keterangan diperbarui";
        }

        // 6. Cek Perubahan FILE BUKTI
        if ($keuangan->isDirty('bukti_file')) {
            $perubahan[] = "File Bukti diganti";
        }

        // GABUNGKAN SEMUA PERUBAHAN JADI SATU KALIMAT
        if (empty($perubahan)) {
            $deskripsi = "Memperbarui data keuangan (Tanpa perubahan signifikan)";
        } else {
            // Contoh hasil: "Mengubah: Nominal (Rp 10.000 -> Rp 20.000), Tanggal (01 Aug -> 02 Aug)"
            $deskripsi = "Mengubah: " . implode(', ', $perubahan);
        }

        try {
            Log::create([
                'user_id' => Auth::id(),
                'keuangan_id' => $keuangan->id,
                'model' => Keuangan::class,
                'record_id' => $keuangan->id,
                'action' => 'updated',
                'description' => $deskripsi,
                'before' => $keuangan->getOriginal(),
                'after' => $keuangan->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log keuangan (updated)', [
                'error' => $e->getMessage(),
                'keuangan_id' => $keuangan->id ?? null,
            ]);
        }
    }

    /**
     * Handle the Keuangan "deleted" event.
     */
    public function deleted(Keuangan $keuangan): void
    {
        $this->clearCache();

        try {
            Log::create([
                'user_id'     => Auth::id(),
                'model'       => Keuangan::class,
                'record_id'   => $keuangan->id,
                'action'      => 'deleted',
                'description' => "Menghapus data keuangan (ID: {$keuangan->id}). Keterangan: {$keuangan->keterangan}",
                'before'      => $keuangan->toArray(),
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log keuangan (deleted)', [
                'error' => $e->getMessage(),
                'keuangan_id' => $keuangan->id ?? null,
            ]);
        }
    }

    /**
     * Bersihkan cache statistik Dashboard agar nilai pemasukan/pengeluaran
     * langsung diperbarui setelah ada perubahan data keuangan.
     */
    private function clearCache(): void
    {
        Cache::forget(StatsOverview::CACHE_KEY);
    }
}

