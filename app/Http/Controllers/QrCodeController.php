<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Support\ConsultationStats;
use App\Support\ShortLinks;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Page /qrcodes : liens avec balises UTM, raccourcis en sj.saint-arnoux.fr/{code}, et leur QR code. Publique mais non référencée. */
class QrCodeController extends Controller
{
    public function show()
    {
        return response()->view('qrcodes', [
            'targets' => ShortLinks::targets(),
            'sources' => ShortLinks::SOURCES,
            'campaigns' => ShortLinks::campaigns(),
            'recent' => ShortLink::latest('id')->limit(20)->get(),
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'target' => ['required', 'string', 'max:255', fn ($attribute, $value, $fail) => ShortLinks::isTarget($value) ? null : $fail('Cette page n\'existe pas.')],
            'source' => ['required', Rule::in(array_keys(ShortLinks::SOURCES))],
            'campaign' => ['nullable', 'string', 'max:60'],
            'content' => ['nullable', 'string', 'max:60'],
        ]);

        $link = ShortLinks::create($data['target'], $data['source'], $data['campaign'] ?? null, $data['content'] ?? null);
        $shortUrl = $link->shortUrl();

        return response()->json([
            'short_url' => $shortUrl,
            'url' => $link->url,
            'svg' => ConsultationStats::qrCode($shortUrl, transparent: true, color: '#00204E'),
            'png' => 'data:image/png;base64,'.base64_encode(ConsultationStats::qrCode($shortUrl, 'png', transparent: true, color: '#00204E')),
            'filename' => 'qrcode-'.$link->code,
        ]);
    }
}
