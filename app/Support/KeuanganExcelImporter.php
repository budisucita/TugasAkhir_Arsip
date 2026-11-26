<?php

namespace App\Support;

use App\Models\Keuangan;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Reader\XLSX\Sheet;

/**
 * Membaca dan memvalidasi berkas Excel (.xlsx) berisi data keuangan
 * (format "Buku Kas"), lalu menyimpannya ke tabel `keuangan` tanpa
 * mengubah struktur tabel yang sudah ada.
 *
 * Pemetaan kolom dilakukan berdasarkan NAMA HEADER pada baris header
 * (bukan posisi kolom), sehingga pengguna tidak perlu menyesuaikan
 * susunan kolom pada berkasnya:
 *  - Tanggal                 -> tanggal
 *  - Jenis                   -> jenis_transaksi
 *  - Nominal (Rp)            -> jumlah
 *  - Kategori                \
 *  - Nama / Sumber / Penerima > keterangan (digabung secara terstruktur)
 *  - Catatan                 /
 *  - Link Bukti Foto         -> bukti_file (opsional)
 *
 * Kolom di luar rentang A–L (termasuk kolom rumus bantuan pada kolom M)
 * diabaikan sepenuhnya.
 */
class KeuanganExcelImporter
{
    /** Nama sheet yang diprioritaskan bila tersedia. */
    private const PREFERRED_SHEET = 'Buku Kas';

    /** Jumlah kolom yang dibaca (A–L). Kolom M dan seterusnya diabaikan. */
    private const MAX_COLUMNS = 12;

    /** Batas panjang kolom `keterangan` (mengikuti validasi form resource). */
    private const KETERANGAN_MAX_LENGTH = 255;

    /** Batas panjang kolom `bukti_file` (VARCHAR(255)). */
    private const BUKTI_MAX_LENGTH = 255;

    /** Jumlah baris awal yang dipindai untuk menemukan baris header. */
    private const HEADER_SCAN_LIMIT = 10;

    /** Basis serial tanggal Excel (sistem 1900). */
    private const EXCEL_DATE_BASE = '1899-12-30';

    /**
     * Menganalisis berkas Excel dan mengembalikan ringkasan pratinjau
     * (belum menyimpan apa pun ke database).
     *
     * @return array{
     *     fatal: null|string,
     *     rows: array<int, array<string, mixed>>,
     *     skipped: array<int, array{line: int, reason: string}>,
     *     errors: array<int, array{line: int, reason: string}>,
     *     total_pemasukan: float,
     *     total_pengeluaran: float
     * }
     */
    public function analyze(string $filePath, int $kegiatanId): array
    {
        $reader = new Reader();
        $reader->open($filePath);

        try {
            $sheet = $this->resolveSheet($reader);
            $raw = $this->readSheet($sheet);
        } finally {
            $reader->close();
        }

        return $this->evaluate($raw, $kegiatanId);
    }

    /**
     * Menyimpan baris data yang valid ke database di dalam satu transaksi.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return int  Jumlah baris yang berhasil disimpan.
     */
    public function persist(array $rows, int $kegiatanId, int $userId): int
    {
        return DB::transaction(function () use ($rows, $kegiatanId, $userId): int {
            $saved = 0;

            foreach ($rows as $row) {
                Keuangan::create([
                    'kegiatan_id' => $kegiatanId,
                    'user_id' => $userId,
                    'jenis_transaksi' => $row['jenis_transaksi'],
                    'jumlah' => $row['jumlah'],
                    'keterangan' => $row['keterangan'],
                    'bukti_file' => $row['bukti_file'],
                    'tanggal' => $row['tanggal'],
                ]);

                $saved++;
            }

            return $saved;
        });
    }

    /**
     * Menentukan sheet yang akan dibaca: sheet bernama "Buku Kas" bila ada,
     * jika tidak maka sheet pertama yang tersedia.
     */
    private function resolveSheet(Reader $reader): Sheet
    {
        $firstSheet = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            $firstSheet ??= $sheet;

            if (strcasecmp(trim($sheet->getName()), self::PREFERRED_SHEET) === 0) {
                return $sheet;
            }
        }

        return $firstSheet;
    }

    /**
     * Membaca header dan seluruh baris data dari sebuah sheet.
     *
     * @return array{header: array<int, mixed>, rows: array<int, array{line: int, values: array<int, mixed>}>}
     */
    private function readSheet(Sheet $sheet): array
    {
        $header = null;
        $rows = [];
        $line = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $line++;
            $values = $this->cellValues($row);

            if ($header === null) {
                if ($line <= self::HEADER_SCAN_LIMIT && $this->looksLikeHeader($values)) {
                    $header = $values;
                }

                // Baris judul sebelum header diabaikan.
                continue;
            }

            $rows[] = ['line' => $line, 'values' => $values];
        }

        return ['header' => $header ?? [], 'rows' => $rows];
    }

    /**
     * Mengambil nilai kolom A–L (maksimal 12 kolom) dari satu baris.
     *
     * @return array<int, mixed>
     */
    private function cellValues(Row $row): array
    {
        $values = [];

        for ($index = 0; $index < self::MAX_COLUMNS; $index++) {
            $values[$index] = $row->getCellAtIndex($index)?->getValue();
        }

        return $values;
    }

    /**
     * Apakah deretan sel tampak seperti baris header (memuat "Tanggal"
     * serta "Jenis" dan/atau "Nominal").
     *
     * @param  array<int, mixed>  $values
     */
    private function looksLikeHeader(array $values): bool
    {
        $map = $this->mapHeaders($values);

        return isset($map['tanggal']) && (isset($map['jenis']) || isset($map['jumlah']));
    }

    /**
     * Memetakan indeks kolom berdasarkan nama header.
     *
     * @param  array<int, mixed>  $values
     * @return array<string, int>
     */
    private function mapHeaders(array $values): array
    {
        $map = [];

        foreach ($values as $index => $value) {
            $normalized = strtolower(trim((string) $value));

            if ($normalized === '') {
                continue;
            }

            if (! isset($map['tanggal']) && str_contains($normalized, 'tanggal')) {
                $map['tanggal'] = $index;
            } elseif (! isset($map['jenis']) && str_contains($normalized, 'jenis')) {
                $map['jenis'] = $index;
            } elseif (! isset($map['jumlah']) && str_contains($normalized, 'nominal')) {
                $map['jumlah'] = $index;
            } elseif (! isset($map['kategori']) && str_contains($normalized, 'kategori')) {
                $map['kategori'] = $index;
            } elseif (! isset($map['nama'])
                && (str_contains($normalized, 'nama') || str_contains($normalized, 'sumber') || str_contains($normalized, 'penerima'))) {
                $map['nama'] = $index;
            } elseif (! isset($map['catatan']) && str_contains($normalized, 'catatan')) {
                $map['catatan'] = $index;
            } elseif (! isset($map['bukti']) && (str_contains($normalized, 'link') || str_contains($normalized, 'bukti'))) {
                $map['bukti'] = $index;
            }
        }

        return $map;
    }

    /**
     * Menjalankan validasi, deteksi duplikat, dan agregasi untuk semua baris.
     *
     * @param  array{header: array<int, mixed>, rows: array<int, array{line: int, values: array<int, mixed>}>}  $raw
     * @return array{
     *     fatal: null|string,
     *     rows: array<int, array<string, mixed>>,
     *     skipped: array<int, array{line: int, reason: string}>,
     *     errors: array<int, array{line: int, reason: string}>,
     *     total_pemasukan: float,
     *     total_pengeluaran: float
     * }
     */
    private function evaluate(array $raw, int $kegiatanId): array
    {
        $headerMap = $this->mapHeaders($raw['header']);

        if (! isset($headerMap['tanggal'])) {
            return [
                'fatal' => 'Header "Tanggal" tidak ditemukan pada berkas. Pastikan berkas mengikuti format "Buku Kas".',
                'rows' => [],
                'skipped' => [],
                'errors' => [],
                'total_pemasukan' => 0.0,
                'total_pengeluaran' => 0.0,
            ];
        }

        $rawRows = $raw['rows'];

        // Buang baris kosong di bagian ekor agar tidak menggelembungkan
        // jumlah "dilewati" akibat baris kosong sisa format Excel.
        while ($rawRows !== [] && $this->isRowEmpty(end($rawRows)['values'])) {
            array_pop($rawRows);
        }

        $existing = $this->existingKeys($kegiatanId);
        $seen = [];
        $valid = [];
        $skipped = [];
        $errors = [];
        $totalPemasukan = 0.0;
        $totalPengeluaran = 0.0;

        foreach ($rawRows as $entry) {
            $line = $entry['line'];
            $values = $entry['values'];

            // Lewati secara diam-diam baris yang seluruh kolom A–L kosong.
            if ($this->isRowEmpty($values)) {
                $skipped[] = ['line' => $line, 'reason' => 'Baris kosong'];
                continue;
            }

            $tanggal = $this->parseDate($this->valueAt($values, $headerMap, 'tanggal'));
            $jenis = $this->normalizeJenis($this->valueAt($values, $headerMap, 'jenis'));
            $jumlah = $this->cleanAmount($this->valueAt($values, $headerMap, 'jumlah'));
            $keterangan = $this->composeKeterangan(
                $this->valueAt($values, $headerMap, 'kategori'),
                $this->valueAt($values, $headerMap, 'nama'),
                $this->valueAt($values, $headerMap, 'catatan'),
            );
            $bukti = $this->normalizeBukti($this->valueAt($values, $headerMap, 'bukti'));

            $reasons = [];

            if (! $tanggal) {
                $reasons[] = 'Tanggal tidak valid';
            }

            if (! $jenis) {
                $reasons[] = 'Jenis transaksi harus "pemasukan" atau "pengeluaran"';
            }

            if ($jumlah === null || $jumlah <= 0) {
                $reasons[] = 'Nominal harus berupa angka lebih dari 0';
            }

            if ($keterangan === '') {
                $reasons[] = 'Keterangan kosong';
            }

            if ($reasons !== []) {
                $errors[] = ['line' => $line, 'reason' => implode('; ', $reasons)];
                continue;
            }

            $tanggalString = $tanggal->format('Y-m-d');
            $key = $this->duplicateKey($kegiatanId, $tanggalString, $jenis, $jumlah, $keterangan);

            if (isset($existing[$key]) || isset($seen[$key])) {
                $skipped[] = ['line' => $line, 'reason' => 'Duplikat (sudah ada di database)'];
                continue;
            }

            $seen[$key] = true;
            $valid[] = [
                'line' => $line,
                'tanggal' => $tanggalString,
                'jenis_transaksi' => $jenis,
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'bukti_file' => $bukti,
            ];

            if ($jenis === 'pemasukan') {
                $totalPemasukan += $jumlah;
            } else {
                $totalPengeluaran += $jumlah;
            }
        }

        return [
            'fatal' => null,
            'rows' => $valid,
            'skipped' => $skipped,
            'errors' => $errors,
            'total_pemasukan' => $totalPemasukan,
            'total_pengeluaran' => $totalPengeluaran,
        ];
    }

    /**
     * Membangun himpunan kunci duplikat dari data keuangan yang sudah ada
     * untuk kegiatan terkait.
     *
     * @return array<string, bool>
     */
    private function existingKeys(int $kegiatanId): array
    {
        $keys = [];

        Keuangan::query()
            ->where('kegiatan_id', $kegiatanId)
            ->get(['tanggal', 'jenis_transaksi', 'jumlah', 'keterangan'])
            ->each(function (Keuangan $keuangan) use (&$keys, $kegiatanId): void {
                $tanggal = $keuangan->tanggal instanceof DateTimeInterface
                    ? $keuangan->tanggal->format('Y-m-d')
                    : (string) $keuangan->tanggal;

                $keys[$this->duplicateKey(
                    $kegiatanId,
                    $tanggal,
                    (string) $keuangan->jenis_transaksi,
                    (float) $keuangan->jumlah,
                    (string) $keuangan->keterangan,
                )] = true;
            });

        return $keys;
    }

    /**
     * Kunci unik kombinasi kegiatan, tanggal, jenis, jumlah, dan keterangan.
     */
    private function duplicateKey(int $kegiatanId, string $tanggal, string $jenis, float|int|string $jumlah, string $keterangan): string
    {
        return implode('|', [
            $kegiatanId,
            $tanggal,
            $jenis,
            number_format((float) $jumlah, 2, '.', ''),
            trim($keterangan),
        ]);
    }

    /**
     * @param  array<int, mixed>  $values
     * @param  array<string, int>  $map
     */
    private function valueAt(array $values, array $map, string $key): mixed
    {
        return isset($map[$key]) ? ($values[$map[$key]] ?? null) : null;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function isRowEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if (! $this->isBlank($value)) {
                return false;
            }
        }

        return true;
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }

    /**
     * Mengonversi nilai menjadi Carbon. Mendukung serial tanggal Excel
     * (angka) maupun string tanggal dalam beberapa format umum.
     */
    private function parseDate(mixed $value): ?Carbon
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial <= 0) {
                return null;
            }

            return Carbon::parse(self::EXCEL_DATE_BASE)
                ->addDays((int) floor($serial))
                ->startOfDay();
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd M Y', 'd F Y', 'm/d/Y'] as $format) {
            if (! Carbon::hasFormat($raw, $format)) {
                continue;
            }

            try {
                $parsed = Carbon::createFromFormat($format, $raw);
            } catch (\Throwable) {
                continue;
            }

            // Tolak tanggal yang di-roll-over PHP (mis. 31/02/2026).
            if ($parsed !== null && $parsed->format($format) === $raw) {
                return $parsed->startOfDay();
            }
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Normalisasi jenis transaksi menjadi 'pemasukan' atau 'pengeluaran'.
     */
    private function normalizeJenis(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['pemasukan', 'pengeluaran'], true) ? $normalized : null;
    }

    /**
     * Membersihkan nilai nominal menjadi float. Pemisah ribuan (baik titik
     * maupun koma) dan simbol mata uang dibuang.
     */
    private function cleanAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        $isNegative = str_starts_with($raw, '-');
        $digits = preg_replace('/[^0-9]/', '', $raw);

        if ($digits === null || $digits === '') {
            return null;
        }

        $amount = (float) $digits;

        return $isNegative ? -$amount : $amount;
    }

    /**
     * Menggabungkan Kategori, Nama / Sumber / Penerima, dan Catatan menjadi
     * satu kolom keterangan dengan format `[Kategori] Nama / Sumber - Catatan`.
     */
    private function composeKeterangan(mixed $kategori, mixed $nama, mixed $catatan): string
    {
        $kategori = $this->cleanText($kategori);
        $nama = $this->cleanText($nama);
        $catatan = $this->cleanText($catatan);

        $keterangan = $kategori !== '' ? "[{$kategori}]" : '';

        if ($nama !== '') {
            $keterangan = $keterangan !== '' ? "{$keterangan} {$nama}" : $nama;
        }

        if ($catatan !== '') {
            $keterangan = $keterangan !== '' ? "{$keterangan} - {$catatan}" : $catatan;
        }

        return $this->truncate($keterangan, self::KETERANGAN_MAX_LENGTH);
    }

    /**
     * Bukti bersifat opsional (URL Google Drive) dan boleh kosong (null).
     */
    private function normalizeBukti(mixed $value): ?string
    {
        $text = $this->cleanText($value);

        return $text === '' ? null : $this->truncate($text, self::BUKTI_MAX_LENGTH);
    }

    private function cleanText(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function truncate(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length) : $value;
    }
}
