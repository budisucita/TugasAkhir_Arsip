<div class="p-2">
    @php
        $path = $getState();
    @endphp

    @if ($path)
        <iframe
            src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($path) }}"
            style="width: 100%; height: 600px;"
            frameborder="0"
        ></iframe>
    @else
        <div class="text-center text-gray-400">
            Tidak ada file yang diupload.
        </div>
    @endif
</div>