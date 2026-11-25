@php
    use Illuminate\Support\Facades\Storage;

    $path = $getState();

    $disk = Storage::disk('public');
    $exists = filled($path) && $disk->exists($path);

    $url = $exists ? $disk->url($path) : null;
    $filename = $exists ? basename($path) : null;
    $extension = $exists ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : null;
    $bytes = $exists ? (int) $disk->size($path) : 0;

    $isPdf = $extension === 'pdf';
    $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif'], true);
    $isExcel = in_array($extension, ['xlsx', 'xls', 'csv'], true);
    $isWord = in_array($extension, ['docx', 'doc'], true);

    // Format ukuran file ke satuan yang mudah dibaca.
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $unitIndex = min($bytes > 0 ? (int) floor(log($bytes, 1024)) : 0, count($units) - 1);
    $formattedSize = $bytes > 0
        ? number_format($bytes / (1024 ** $unitIndex), 2, ',', '.') . ' ' . $units[$unitIndex]
        : '0 B';

    $typeLabel = $isExcel ? 'Excel' : ($isWord ? 'Word' : strtoupper((string) $extension));
@endphp

<div class="p-2">
    @if (! $exists)
        {{-- Tidak ada file yang diunggah --}}
        <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center dark:border-white/20 dark:bg-gray-900">
            <x-filament::icon
                icon="heroicon-o-document-arrow-up"
                class="h-10 w-10 text-gray-400 dark:text-gray-500"
            />
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Tidak ada file yang diupload.
            </p>
        </div>
    @elseif ($isPdf)
        {{-- Pratinjau langsung PDF di dalam iframe --}}
        <div
            x-data="{ loading: true, failed: false }"
            x-init="
                $nextTick(() => {
                    if ($refs.frame && $refs.frame.dataset.loaded === '1') {
                        loading = false;
                    }
                });
                setTimeout(() => {
                    if (loading) {
                        loading = false;
                        failed = true;
                    }
                }, 30000);
            "
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
        >
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <div class="flex min-w-0 items-center gap-3">
                    <x-filament::icon
                        icon="heroicon-o-document-text"
                        class="h-5 w-5 shrink-0 text-danger-600 dark:text-danger-400"
                    />
                    <span class="truncate text-sm font-medium text-gray-950 dark:text-white">
                        {{ $filename }}
                    </span>
                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                        ({{ $formattedSize }})
                    </span>
                </div>

                <x-filament::button
                    tag="a"
                    href="{{ $url }}"
                    download="{{ $filename }}"
                    size="sm"
                    color="gray"
                    icon="heroicon-o-arrow-down-tray"
                >
                    Unduh
                </x-filament::button>
            </div>

            <div class="relative">
                {{-- State memuat --}}
                <div
                    x-show="loading"
                    class="absolute inset-0 z-10 flex h-[60vh] max-h-[600px] min-h-[320px] flex-col items-center justify-center gap-3 bg-white text-center dark:bg-gray-900"
                >
                    <x-filament::loading-indicator class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Memuat pratinjau PDF&hellip;
                    </p>
                </div>

                {{-- State error bila PDF gagal dimuat --}}
                <div
                    x-show="failed"
                    x-cloak
                    class="absolute inset-0 z-10 flex h-[60vh] max-h-[600px] min-h-[320px] flex-col items-center justify-center gap-3 bg-white px-6 text-center dark:bg-gray-900"
                >
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="h-10 w-10 text-warning-500"
                    />
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        Pratinjau PDF gagal dimuat
                    </p>
                    <p class="max-w-md text-xs text-gray-500 dark:text-gray-400">
                        Peramban Anda mungkin tidak mendukung pratinjau PDF secara langsung.
                        Silakan unduh file untuk membukanya.
                    </p>
                    <x-filament::button
                        tag="a"
                        href="{{ $url }}"
                        download="{{ $filename }}"
                        color="primary"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        Unduh File
                    </x-filament::button>
                </div>

                <iframe
                    x-ref="frame"
                    x-show="! failed"
                    src="{{ $url }}#view=FitH"
                    title="Pratinjau {{ $filename }}"
                    class="h-[60vh] max-h-[600px] min-h-[320px] w-full border-0"
                    onload="this.dataset.loaded = '1'"
                    x-on:load="loading = false"
                    x-on:error="loading = false; failed = true"
                ></iframe>
            </div>
        </div>
    @elseif ($isImage)
        {{-- Pratinjau langsung gambar (mis. bukti transaksi) --}}
        <div
            x-data="{ loading: true }"
            class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
        >
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <div class="flex min-w-0 items-center gap-3">
                    <x-filament::icon
                        icon="heroicon-o-photo"
                        class="h-5 w-5 shrink-0 text-primary-600 dark:text-primary-400"
                    />
                    <span class="truncate text-sm font-medium text-gray-950 dark:text-white">
                        {{ $filename }}
                    </span>
                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                        ({{ $formattedSize }})
                    </span>
                </div>

                <x-filament::button
                    tag="a"
                    href="{{ $url }}"
                    download="{{ $filename }}"
                    size="sm"
                    color="gray"
                    icon="heroicon-o-arrow-down-tray"
                >
                    Unduh
                </x-filament::button>
            </div>

            <div class="relative">
                <div
                    x-show="loading"
                    class="absolute inset-0 z-10 flex min-h-[240px] items-center justify-center bg-white dark:bg-gray-900"
                >
                    <x-filament::loading-indicator class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                </div>

                <img
                    src="{{ $url }}"
                    alt="{{ $filename }}"
                    loading="lazy"
                    x-on:load="loading = false"
                    x-on:error="loading = false"
                    class="mx-auto max-h-[600px] w-full object-contain"
                />
            </div>
        </div>
    @else
        {{-- Kartu pratinjau untuk file Excel/Word, atau fallback unduh untuk ekstensi lain --}}
        @php
            $isUnsupported = ! $isExcel && ! $isWord;

            $iconName = $isExcel
                ? 'heroicon-o-table-cells'
                : ($isWord ? 'heroicon-o-document-text' : 'heroicon-o-document');

            $iconColor = $isExcel
                ? 'text-success-600 dark:text-success-400'
                : ($isWord ? 'text-info-600 dark:text-info-400' : 'text-gray-400 dark:text-gray-500');
        @endphp

        <div class="flex flex-col gap-4 rounded-xl border {{ $isUnsupported ? 'border-dashed border-gray-300 dark:border-white/20' : 'border-gray-200 dark:border-white/10' }} bg-white p-5 shadow-sm dark:bg-gray-900 sm:flex-row sm:items-center">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-800">
                <x-filament::icon
                    :icon="$iconName"
                    class="h-6 w-6 {{ $iconColor }}"
                />
            </div>

            <div class="min-w-0 flex-1 text-center sm:text-start">
                <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                    {{ $filename }}
                </p>

                <div class="mt-1 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                    @if ($isExcel)
                        <x-filament::badge color="success" size="sm">{{ $typeLabel }}</x-filament::badge>
                    @elseif ($isWord)
                        <x-filament::badge color="info" size="sm">{{ $typeLabel }}</x-filament::badge>
                    @else
                        <x-filament::badge color="gray" size="sm">{{ $typeLabel ?: 'FILE' }}</x-filament::badge>
                    @endif

                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $formattedSize }}
                    </span>
                </div>

                @if ($isUnsupported)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Jenis file ini tidak dapat ditampilkan sebagai pratinjau. Silakan unduh file untuk membukanya.
                    </p>
                @endif
            </div>

            <div class="flex flex-col justify-center gap-2 sm:shrink-0 sm:flex-row">
                <x-filament::button
                    tag="a"
                    href="{{ $url }}"
                    download="{{ $filename }}"
                    color="primary"
                    icon="heroicon-o-arrow-down-tray"
                >
                    Unduh
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="{{ $url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    color="gray"
                    icon="heroicon-o-arrow-top-right-on-square"
                >
                    Buka di Tab Baru
                </x-filament::button>
            </div>
        </div>
    @endif
</div>
