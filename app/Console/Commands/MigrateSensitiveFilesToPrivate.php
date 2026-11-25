<?php

namespace App\Console\Commands;

use App\Support\SecureFile;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Memindahkan berkas sensitif yang sebelumnya tersimpan di disk publik
 * (mis. public/storage) ke disk privat, tanpa mengubah path di database.
 *
 * Hanya berkas yang tercatat pada kolom berkas sensitif (SecureFile::MAP)
 * yang dipindahkan. Default berjalan sebagai dry-run; gunakan --apply untuk
 * benar-benar memindahkan.
 */
class MigrateSensitiveFilesToPrivate extends Command
{
    protected $signature = 'storage:migrate-private
        {--from=public : Nama disk sumber (publik)}
        {--keep-source : Jangan hapus berkas dari disk sumber setelah dipindah}
        {--apply : Terapkan perubahan. Tanpa flag ini perintah berjalan sebagai dry-run}';

    protected $description = 'Memindahkan berkas sensitif (bukti, surat, proposal, RAB, LPJ, foto anggota) dari disk publik ke disk privat.';

    public function handle(): int
    {
        $from = (string) $this->option('from');
        $keepSource = (bool) $this->option('keep-source');
        $apply = (bool) $this->option('apply');

        $source = $this->resolveDisk($from);
        $target = $this->resolveDisk(SecureFile::diskName());

        if (! $source || ! $target) {
            return self::FAILURE;
        }

        $this->info(($apply ? 'MODE: APPLY' : 'MODE: DRY-RUN')." | dari: {$from} | ke: ".SecureFile::diskName());

        $operations = $this->plan($source, $target);

        if (empty($operations)) {
            $this->info('Tidak ada berkas sensitif yang perlu dipindahkan.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Aksi', 'Tipe', 'ID', 'Kolom', 'Path'],
            array_map(fn (array $op) => [$op['action'], $op['type'], $op['id'], $op['field'], $op['path']], $operations)
        );

        if (! $apply) {
            $this->newLine();
            $this->warn('DRY-RUN: tidak ada berkas yang diubah. Tambahkan --apply untuk menerapkan.');
            return self::SUCCESS;
        }

        return $this->applyOperations($operations, $source, $target, $keepSource);
    }

    /**
     * Bangun rencana pemindahan berdasarkan berkas yang tercatat di database,
     * lalu disusul sapuan direktori untuk berkas sensitif yatim (yang tetap
     * berada di disk sumber namun tidak lagi direferensikan record).
     *
     * @return array<int, array{action: string, type: string, id: int, field: string, path: string}>
     */
    private function plan(Filesystem $source, Filesystem $target): array
    {
        $operations = [];
        $seen = [];

        foreach (SecureFile::MAP as $type => $definition) {
            $modelClass = $definition['model'];

            foreach ($definition['fields'] as $field) {
                $modelClass::query()
                    ->whereNotNull($field)
                    ->where($field, '!=', '')
                    ->select(['id', $field])
                    ->chunkById(200, function ($rows) use (&$operations, &$seen, $source, $target, $type, $field) {
                        foreach ($rows as $row) {
                            $path = $row->{$field};

                            if (blank($path)) {
                                continue;
                            }

                            $seen[$path] = true;

                            $inSource = $source->exists($path);
                            $inTarget = $target->exists($path);

                            if ($inSource) {
                                $operations[] = [
                                    'action' => $inTarget ? 'OVERWRITE' : 'MOVE',
                                    'type' => $type,
                                    'id' => (int) $row->id,
                                    'field' => $field,
                                    'path' => $path,
                                ];
                            } elseif (! $inTarget) {
                                $operations[] = [
                                    'action' => 'SKIP-MISSING',
                                    'type' => $type,
                                    'id' => (int) $row->id,
                                    'field' => $field,
                                    'path' => $path,
                                ];
                            }
                        }
                    });
            }
        }

        // Sapuan berkas yatim: seluruh berkas pada direktori sensitif di disk
        // sumber yang belum tercatat lewat pemindaian record di atas.
        foreach (SecureFile::DIRECTORIES as $directory => $type) {
            foreach ($source->allFiles($directory) as $path) {
                if (isset($seen[$path])) {
                    continue;
                }

                $seen[$path] = true;

                $operations[] = [
                    'action' => $target->exists($path) ? 'OVERWRITE-ORPHAN' : 'MOVE-ORPHAN',
                    'type' => $type,
                    'id' => 0,
                    'field' => '(yatim)',
                    'path' => $path,
                ];
            }
        }

        return $operations;
    }

    /**
     * Terapkan operasi pemindahan yang direncanakan.
     *
     * @param  array<int, array{action: string, type: string, id: int, field: string, path: string}>  $operations
     */
    private function applyOperations(array $operations, Filesystem $source, Filesystem $target, bool $keepSource): int
    {
        $done = 0;
        $failed = 0;

        foreach ($operations as $op) {
            $path = $op['path'];

            if ($op['action'] === 'SKIP-MISSING') {
                $this->warn("  ! SKIP-MISSING {$path} (tidak ditemukan di kedua disk)");
                continue;
            }

            try {
                $target->writeStream($path, $source->readStream($path));

                if (! $keepSource) {
                    $source->delete($path);
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
     * Resolve a disk name, returning null (and reporting) when it is unusable.
     */
    private function resolveDisk(string $name): ?Filesystem
    {
        try {
            $disk = Storage::disk($name);
            $disk->allFiles('');

            return $disk;
        } catch (Throwable $e) {
            $this->error("Disk '{$name}' tidak dapat diakses: {$e->getMessage()}");
            return null;
        }
    }
}
