<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class LaporanKeuanganController extends Controller
{
    /**
     * Tampilkan laporan keuangan siap cetak.
     */
    public function print(Request $request)
    {
        $validated = $this->validateFilters($request);

        $data = $this->buildQuery($validated)->orderBy('tanggal', 'asc')->get();

        // Saldo awal = akumulasi seluruh transaksi sebelum tanggal_mulai.
        $saldoAwal = $this->hitungSaldoAwal($validated);

        return view('laporan.keuangan', [
            'data' => $data,
            'periode' => $this->formatPeriode($validated),
            'tempat' => $validated['tempat'] ?? 'Denpasar',
            'saldoAwal' => $saldoAwal,
            'tampilkanSaldoAwal' => ! empty($validated['tanggal_mulai']),
        ]);
    }

    /**
     * Ekspor laporan keuangan ke berkas Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $validated = $this->validateFilters($request);

        $data = $this->buildQuery($validated)->orderBy('tanggal', 'asc')->get();
        $saldoAwal = $this->hitungSaldoAwal($validated);
        $periode = $this->formatPeriode($validated);

        $filename = 'Laporan-Keuangan-' . now()->format('Ymd-His') . '.xlsx';

        // Tulis ke berkas sementara lalu kirim sebagai unduhan, agar header
        // respons tetap dikelola Laravel dan berkas dibersihkan otomatis.
        // Nama unduhan ditentukan oleh argumen kedua response()->download().
        $tempPath = tempnam(sys_get_temp_dir(), 'laporan_keuangan_');

        $writer = new Writer();
        $writer->openToFile($tempPath);

        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Laporan Keuangan');
        $sheet->setColumnWidth(6, 1);
        $sheet->setColumnWidth(14, 2);
        $sheet->setColumnWidth(28, 3);
        $sheet->setColumnWidth(40, 4);
        $sheet->setColumnWidth(16, 5);
        $sheet->setColumnWidth(16, 6);
        $sheet->setColumnWidth(16, 7);

        $titleStyle = (new Style())->setFontBold()->setFontSize(14);
        $subtitleStyle = (new Style())->setFontSize(11);
        $headerStyle = (new Style())
            ->setFontBold()
            ->setBackgroundColor('F2F2F2')
            ->setCellAlignment(CellAlignment::CENTER);
        $moneyStyle = (new Style())->setFormat('#,##0');
        $moneyBoldStyle = (new Style())->setFormat('#,##0')->setFontBold();
        $centerStyle = (new Style())->setCellAlignment(CellAlignment::CENTER);
        $boldStyle = (new Style())->setFontBold();

        // Kop laporan.
        $writer->addRow(Row::fromValues(['SEKA TERUNA-TERUNI DHARMA BHAKTI'], $titleStyle));
        $writer->addRow(Row::fromValues(['BR. KARYA DHARMA KELURAHAN SESETAN'], $subtitleStyle));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['LAPORAN KEUANGAN'], $titleStyle));
        $writer->addRow(Row::fromValues(['Periode: ' . $periode], $subtitleStyle));
        $writer->addRow(Row::fromValues(['']));

        // Baris header tabel.
        $writer->addRow(Row::fromValuesWithStyles(
            ['No', 'Tanggal', 'Kegiatan', 'Uraian', 'Pemasukan (Rp)', 'Pengeluaran (Rp)', 'Saldo (Rp)'],
            $headerStyle
        ));

        $saldo = $saldoAwal;
        $totalPemasukan = 0;
        $totalPengeluaran = 0;

        // Baris saldo awal bila periode difilter dengan tanggal mulai.
        if (! empty($validated['tanggal_mulai'])) {
            $writer->addRow(Row::fromValuesWithStyles(
                ['', '', '', 'Saldo Awal Periode', '', '', $saldo],
                null,
                [3 => $boldStyle, 6 => $moneyBoldStyle]
            ));
        }

        foreach ($data as $index => $item) {
            if ($item->jenis_transaksi === 'pemasukan') {
                $pemasukan = (int) $item->jumlah;
                $pengeluaran = null;
                $saldo += $pemasukan;
                $totalPemasukan += $pemasukan;
            } else {
                $pemasukan = null;
                $pengeluaran = (int) $item->jumlah;
                $saldo -= $pengeluaran;
                $totalPengeluaran += $pengeluaran;
            }

            $writer->addRow(Row::fromValuesWithStyles(
                [
                    $index + 1,
                    Carbon::parse($item->tanggal)->translatedFormat('d-m-Y'),
                    $item->kegiatan ? $item->kegiatan->nama_kegiatan : 'Umum',
                    $item->keterangan,
                    $pemasukan,
                    $pengeluaran,
                    $saldo,
                ],
                null,
                [
                    0 => $centerStyle,
                    1 => $centerStyle,
                    4 => $moneyStyle,
                    5 => $moneyStyle,
                    6 => $moneyStyle,
                ]
            ));
        }

        // Baris total.
        $writer->addRow(Row::fromValuesWithStyles(
            ['', '', '', 'TOTAL', $totalPemasukan, $totalPengeluaran, $saldo],
            null,
            [3 => $boldStyle, 4 => $moneyBoldStyle, 5 => $moneyBoldStyle, 6 => $moneyBoldStyle]
        ));

        $writer->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Validasi ketat seluruh parameter query. Bila tidak valid, Laravel
     * mengembalikan respons validasi (redirect + pesan error), bukan 500.
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'kegiatan_id' => ['nullable', 'integer', 'exists:kegiatan,id'],
            'jenis_transaksi' => ['nullable', 'string', Rule::in(['pemasukan', 'pengeluaran'])],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'tempat' => ['nullable', 'string', 'max:100', 'regex:/^[\p{L}\p{N}\s.,\-\/()\'"]+$/u'],
        ]);
    }

    /**
     * Bangun query transaksi sesuai filter (tanpa perhitungan saldo).
     */
    private function buildQuery(array $validated)
    {
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

        return $query;
    }

    /**
     * Hitung saldo awal: akumulasi (pemasukan - pengeluaran) seluruh transaksi
     * yang terjadi SEBELUM tanggal_mulai, dalam lingkup kegiatan yang sama.
     *
     * Bila tidak ada filter tanggal_mulai, saldo awal bernilai 0 karena laporan
     * memang dimulai dari transaksi paling awal.
     */
    private function hitungSaldoAwal(array $validated): int
    {
        if (empty($validated['tanggal_mulai'])) {
            return 0;
        }

        $base = Keuangan::query();

        if (! empty($validated['kegiatan_id'])) {
            $base->where('kegiatan_id', $validated['kegiatan_id']);
        }

        $base->whereDate('tanggal', '<', $validated['tanggal_mulai']);

        $pemasukan = (clone $base)->where('jenis_transaksi', 'pemasukan')->sum('jumlah');
        $pengeluaran = (clone $base)->where('jenis_transaksi', 'pengeluaran')->sum('jumlah');

        return (int) ($pemasukan - $pengeluaran);
    }

    /**
     * Susun label periode dari filter tanggal yang dipilih.
     */
    private function formatPeriode(array $validated): string
    {
        if (! empty($validated['tanggal_mulai']) && ! empty($validated['tanggal_selesai'])) {
            return Carbon::parse($validated['tanggal_mulai'])->locale('id')->translatedFormat('d F Y')
                . ' - '
                . Carbon::parse($validated['tanggal_selesai'])->locale('id')->translatedFormat('d F Y');
        }

        if (! empty($validated['tanggal_mulai'])) {
            return 'Mulai ' . Carbon::parse($validated['tanggal_mulai'])->locale('id')->translatedFormat('d F Y');
        }

        if (! empty($validated['tanggal_selesai'])) {
            return 'Sampai ' . Carbon::parse($validated['tanggal_selesai'])->locale('id')->translatedFormat('d F Y');
        }

        return 'Semua Data';
    }
}
