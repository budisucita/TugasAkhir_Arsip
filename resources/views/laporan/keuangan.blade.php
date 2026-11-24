<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
        }
        .kop {
            display: grid;
            grid-template-columns: 90px 1fr;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .kop-logo {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .kop img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .kop-text {
            text-align: center;
        }
        .kop-text h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .kop-text p {
            margin: 2px 0;
            font-size: 12px;
        }
        .kop-line {
            border-top: 2px solid #000;
            margin-top: 6px;
            margin-bottom: 14px;
        }
        .title {
            text-align: center;
            margin-bottom: 14px;
        }
        .title h2 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        .title p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .signature-block {
            margin-top: 30px;
        }
        .signature-grid {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .signature-item {
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }
        .signature-item p {
            margin: 2px 0;
        }
        .role { margin-top: 0; }
        .dots {
            margin-top: 0;
            display: block;
            text-align: center;
            font-family: "Courier New", monospace;
            letter-spacing: 0px;
            margin: 0 auto;
        }
        .signature-center {
            text-align: center;
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .signature-center p { margin: 0; }
        .signature-center .dots { margin-top: 60px; }
        .right-date {
            text-align: right;
            margin-bottom: 8px;
        }
        .signature-block {
            page-break-inside: avoid;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            thead {
                display: table-header-group;
            }
            tr {
                page-break-inside: avoid;
            }
            .signature-block {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <div class="kop">
        <div class="kop-logo">
            <img src="{{ asset('images/logo-dharma-bhakti.png') }}" alt="Logo Dharma Bhakti">
        </div>
        <div class="kop-text">
            <h1>SEKA TERUNA-TERUNI DHARMA BHAKTI</h1>
            <p>BR. KARYA DHARMA KELURAHAN SELATAN</p>
            <p>Jl. Gurita I No. 9, Sesetan</p>
            <p>Email: st.dharmabhaktisesetan@gmail.com</p>
        </div>
    </div>
    <div class="kop-line"></div>

    <div class="title">
        <h2>LAPORAN KEUANGAN</h2>
        <p>Periode: {{ $periode }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="18%">Kegiatan</th>
                <th>Uraian</th>
                <th width="12%">Pemasukan (Rp)</th>
                <th width="12%">Pengeluaran (Rp)</th>
                <th width="12%">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $saldo = 0;
                $totalPemasukan = 0;
                $totalPengeluaran = 0;
            @endphp
            @forelse($data as $index => $item)
                @php
                    if ($item->jenis_transaksi == 'pemasukan') {
                        $pemasukan = $item->jumlah;
                        $pengeluaran = 0;
                        $saldo += $pemasukan;
                        $totalPemasukan += $pemasukan;
                    } else {
                        $pemasukan = 0;
                        $pengeluaran = $item->jumlah;
                        $saldo -= $pengeluaran;
                        $totalPengeluaran += $pengeluaran;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d-m-Y') }}</td>
                    <td>{{ $item->kegiatan ? $item->kegiatan->nama_kegiatan : 'Umum' }}</td>
                    <td>{{ $item->keterangan }}</td>
                    <td class="text-right">{{ $pemasukan > 0 ? number_format($pemasukan, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $pengeluaran > 0 ? number_format($pengeluaran, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ number_format($saldo, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data keuangan untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="4" class="text-center">TOTAL</td>
                <td class="text-right">{{ number_format($totalPemasukan, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($saldo, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-block">
        <div class="right-date">{{ $tempat ?? 'Denpasar' }}, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</div>
        <div class="signature-grid">
            <div class="signature-item">
                <div class="role">
                    <p>Hormat kami,</p>
                    <p>Ketua Panitia,</p>
                </div>
                <div class="dots">..................................</div>
            </div>
            <div class="signature-item">
                <div class="role">
                    <p>&nbsp;</p>
                    <p>Bendahara,</p>
                </div>
                <div class="dots">..................................</div>
            </div>
            <div class="signature-item">
                <div class="role">
                    <p>&nbsp;</p>
                    <p>Ketua STT Dharma Bhakti,</p>
                </div>
                <div class="dots">..................................</div>
            </div>
        </div>
        <div class="signature-center">
            <p>Mengetahui,</p>
            <p>Kepala Lingkungan Banjar Karya Dharma</p>
            <div class="dots">..................................</div>
        </div>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>
