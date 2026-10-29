@php($image = $getRecord())
<div style="background:#f3f4f6; border-radius:10px; display:grid; place-items:center; min-height:320px; padding:12px;">
    <img src="{{ Storage::disk(\App\Models\LibraryImage::SOURCE_DISK)->temporaryUrl($image->source_path, now()->addHour()) }}"
        alt="{{ $image->alt }}" style="max-width:100%; max-height:70vh; object-fit:contain; border-radius:4px;">
</div>
