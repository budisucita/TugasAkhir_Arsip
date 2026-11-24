<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SyncStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:sync
        {--from=public : Nama disk sumber}
        {--to=s3 : Nama disk tujuan}
        {--direction=both : Arah sinkronisasi: push|pull|both}
        {--conflict=skip : Penanganan konflik (path sama, isi beda): skip|from|to|newest}
        {--checksum : Bandingkan checksum MD5 untuk file dengan path sama}
        {--prune : Hapus file berlebih di sisi penerima (hati-hati)}
        {--apply : Terapkan perubahan. Tanpa flag ini perintah berjalan sebagai dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyinkronkan dua disk penyimpanan (mis. lokal <-> S3) dengan verifikasi checksum. Default dry-run.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $from = (string) $this->option('from');
        $to = (string) $this->option('to');
        $direction = (string) $this->option('direction');
        $conflict = (string) $this->option('conflict');
        $withChecksum = (bool) $this->option('checksum');
        $prune = (bool) $this->option('prune');
        $apply = (bool) $this->option('apply');

        if (! in_array($direction, ['push', 'pull', 'both'], true)) {
            $this->error("Nilai --direction tidak valid: {$direction}. Gunakan push|pull|both.");
            return self::FAILURE;
        }

        if (! in_array($conflict, ['skip', 'from', 'to', 'newest'], true)) {
            $this->error("Nilai --conflict tidak valid: {$conflict}. Gunakan skip|from|to|newest.");
            return self::FAILURE;
        }

        $source = $this->resolveDisk($from);
        $target = $this->resolveDisk($to);

        if (! $source || ! $target) {
            // Tetap inventarisasi disk yang bisa diakses agar operator tahu kondisinya.
            return self::FAILURE;
        }

        $this->info(($apply ? 'MODE: APPLY' : 'MODE: DRY-RUN')." | arah: {$direction} | checksum: ".($withChecksum ? 'ya' : 'tidak'));

        $sourceFiles = $this->inventory($source, $withChecksum);
        $targetFiles = $this->inventory($target, $withChecksum);

        $this->line("Disk '{$from}': ".count($sourceFiles).' file');
        $this->line("Disk '{$to}': ".count($targetFiles).' file');

        $operations = $this->plan($sourceFiles, $targetFiles, $direction, $conflict, $prune);

        if (empty($operations)) {
            $this->info('Tidak ada perbedaan. Kedua disk sudah selaras.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Aksi', 'File', 'Keterangan'],
            array_map(fn (array $op) => [$op['action'], $op['path'], $op['reason']], $operations)
        );

        if (! $apply) {
            $this->newLine();
            $this->warn('DRY-RUN: tidak ada file yang diubah. Tambahkan --apply untuk menerapkan.');
            return self::SUCCESS;
        }

        return $this->execute($operations, $from, $to, $source, $target);
    }

    /**
     * Bangun rencana operasi tanpa menyentuh disk.
     *
     * @param  array<string, array{size: int, mtime: ?int, checksum: ?string}>  $sourceFiles
     * @param  array<string, array{size: int, mtime: ?int, checksum: ?string}>  $targetFiles
     * @return array<int, array{action: string, path: string, reason: string}>
     */
    private function plan(array $sourceFiles, array $targetFiles, string $direction, string $conflict, bool $prune): array
    {
        $operations = [];
        $paths = array_unique(array_merge(array_keys($sourceFiles), array_keys($targetFiles)));

        foreach ($paths as $path) {
            $inSource = isset($sourceFiles[$path]);
            $inTarget = isset($targetFiles[$path]);

            if ($inSource && ! $inTarget) {
                if (in_array($direction, ['push', 'both'], true)) {
                    $operations[] = ['action' => 'UPLOAD', 'path' => $path, 'reason' => 'Hanya ada di sumber.'];
                } elseif ($prune) {
                    $operations[] = ['action' => 'DELETE-SRC', 'path' => $path, 'reason' => 'Tidak ada di tujuan (arah pull, --prune).'];
                }
                continue;
            }

            if (! $inSource && $inTarget) {
                if (in_array($direction, ['pull', 'both'], true)) {
                    $operations[] = ['action' => 'DOWNLOAD', 'path' => $path, 'reason' => 'Hanya ada di tujuan.'];
                } elseif ($prune) {
                    $operations[] = ['action' => 'DELETE-DST', 'path' => $path, 'reason' => 'Tidak ada di sumber (arah push, --prune).'];
                }
                continue;
            }

            // Path sama: cek konsistensi metadata & isi.
            $src = $sourceFiles[$path];
            $dst = $targetFiles[$path];

            if ($src['checksum'] !== null && $dst['checksum'] !== null) {
                if ($src['checksum'] === $dst['checksum']) {
                    continue; // identik
                }
                $reason = 'Konten berbeda (checksum)';
            } elseif ($src['size'] !== $dst['size']) {
                $reason = "Ukuran berbeda ({$src['size']} vs {$dst['size']} byte)";
            } else {
                continue; // ukuran sama, checksum tidak diminta -> anggap selaras
            }

            $resolved = $this->resolveConflict($src, $dst, $conflict);

            if ($resolved === 'from' && in_array($direction, ['push', 'both'], true)) {
                $operations[] = ['action' => 'OVERWRITE-DST', 'path' => $path, 'reason' => $reason.' -> pakai sumber.'];
            } elseif ($resolved === 'to' && in_array($direction, ['pull', 'both'], true)) {
                $operations[] = ['action' => 'OVERWRITE-SRC', 'path' => $path, 'reason' => $reason.' -> pakai tujuan.'];
            } else {
                $operations[] = ['action' => 'CONFLICT-SKIP', 'path' => $path, 'reason' => $reason.' -> dilewati.'];
            }
        }

        return $operations;
    }

    /**
     * Terapkan operasi yang direncanakan.
     *
     * @param  array<int, array{action: string, path: string, reason: string}>  $operations
     */
    private function applyOperations(array $operations, Filesystem $source, Filesystem $target): int
    {
        $done = 0;
        $failed = 0;

        foreach ($operations as $op) {
            $path = $op['path'];

            try {
                match ($op['action']) {
                    'UPLOAD' => $target->writeStream($path, $source->readStream($path)),
                    'DOWNLOAD' => $source->writeStream($path, $target->readStream($path)),
                    'OVERWRITE-DST' => $target->writeStream($path, $source->readStream($path)),
                    'OVERWRITE-SRC' => $source->writeStream($path, $target->readStream($path)),
                    'DELETE-DST' => $target->delete($path),
                    'DELETE-SRC' => $source->delete($path),
                    'CONFLICT-SKIP' => null,
                    default => null,
                };

                if ($op['action'] === 'CONFLICT-SKIP') {
                    continue;
                }
                $done++;
                $this->line("  ✓ {$op['action']} {$path}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  ✗ {$op['action']} {$path}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Selesai. Berhasil: {$done}, gagal: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Tentukan versi mana yang dipakai saat terjadi konflik.
     *
     * @param  array{size: int, mtime: ?int, checksum: ?string}  $src
     * @param  array{size: int, mtime: ?int, checksum: ?string}  $dst
     */
    private function resolveConflict(array $src, array $dst, string $conflict): string
    {
        return match ($conflict) {
            'from' => 'from',
            'to' => 'to',
            'newest' => ($src['mtime'] ?? 0) >= ($dst['mtime'] ?? 0) ? 'from' : 'to',
            default => 'skip',
        };
    }

    /**
     * Resolve a disk name, returning null (and reporting) when it is unusable.
     */
    private function resolveDisk(string $name): ?Filesystem
    {
        try {
            $disk = Storage::disk($name);

            // Memicu koneksi untuk disk remote agar kegagalan terdeteksi lebih awal.
            $disk->allFiles('');

            return $disk;
        } catch (Throwable $e) {
            $this->error("Disk '{$name}' tidak dapat diakses: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Bangun inventarisasi file (path => ukuran, waktu modifikasi, dan checksum opsional).
     *
     * @return array<string, array{size: int, mtime: ?int, checksum: ?string}>
     */
    private function inventory(Filesystem $disk, bool $withChecksum): array
    {
        $files = [];

        foreach ($disk->allFiles('') as $path) {
            $mtime = null;
            try {
                $mtime = $disk->lastModified($path);
            } catch (Throwable) {
                $mtime = null;
            }

            $files[$path] = [
                'size' => (int) $disk->size($path),
                'mtime' => $mtime,
                'checksum' => $withChecksum ? $this->checksum($disk, $path) : null,
            ];
        }

        return $files;
    }

    /**
     * Hitung checksum MD5 secara streaming agar hemat memori.
     */
    private function checksum(Filesystem $disk, string $path): ?string
    {
        $stream = null;

        try {
            $stream = $disk->readStream($path);
            if (! is_resource($stream)) {
                return null;
            }

            $context = hash_init('md5');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } catch (Throwable) {
            return null;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
