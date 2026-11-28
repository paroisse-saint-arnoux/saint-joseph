<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Consultation – affichettes des projets</title>
<style>
    @font-face { font-family: 'Fraunces'; font-weight: 600; font-style: normal; src: url('{{ $fonts }}/fraunces-latin-600-normal.ttf') format('truetype'); }
    @font-face { font-family: 'Hanken Grotesk'; font-weight: 400; font-style: normal; src: url('{{ $fonts }}/hanken-grotesk-latin-400-normal.ttf') format('truetype'); }
    @font-face { font-family: 'Hanken Grotesk'; font-weight: 700; font-style: normal; src: url('{{ $fonts }}/hanken-grotesk-latin-700-normal.ttf') format('truetype'); }

    @page { size: A5 landscape; margin: 0; }
    * { margin: 0; padding: 0; }
    body { font-family: 'Hanken Grotesk', sans-serif; color: #00204E; font-size: 11pt; }

    .page { position: relative; width: 210mm; height: 148mm; background: #F6F1E6; page-break-after: always; overflow: hidden; }
    .page:last-child { page-break-after: auto; }

    .top td { vertical-align: middle; }
    .top img { width: 30mm; height: 30mm; }
    .top .num { padding-left: 6mm; }

    .main { position: absolute; top: 22mm; left: 12mm; width: 116mm; }
    .num { font-family: 'Fraunces', serif; font-weight: 600; font-size: 11pt; color: #9A7A2C; letter-spacing: 1pt; text-transform: uppercase; }
    .num b { display: block; font-size: 64pt; line-height: 1; letter-spacing: 0; margin-top: 1mm; }
    .photo { position: absolute; top: 58mm; left: 12mm; width: {{ \App\Support\ConsultationPoster::PHOTO_WIDTH }}mm; height: {{ \App\Support\ConsultationPoster::PHOTO_HEIGHT }}mm; text-align: center; }
    .photo img { border-radius: 3mm; }

    .qr { position: absolute; top: 38mm; right: 12mm; width: 62mm; background: #fff; border-radius: 5mm; padding: 5mm 5mm 5mm; text-align: center; }
    .qr img { width: 52mm; height: 52mm; }
    .qr .cta { font-family: 'Fraunces', serif; font-weight: 600; font-size: 14pt; line-height: 1.15; margin-top: 2mm; }

</style>
</head>
<body>
@foreach ($projects as $item)
    @php $p = $item['project']; @endphp
    <div class="page">

        <div class="main">
            <table class="top" cellspacing="0" cellpadding="0"><tr>
                <td><img src="{{ $logo }}" alt="Église Saint Joseph"></td>
                <td class="num">Maquette<b>{{ $p->position }}</b></td>
            </tr></table>
        </div>

        @if ($item['photo'])
            <div class="photo">
                <img src="{{ $item['photo']['src'] }}" alt="Maquette {{ $p->position }}"
                     style="width: {{ $item['photo']['width'] }}mm; height: {{ $item['photo']['height'] }}mm;">
            </div>
        @endif

        <div class="qr">
            <img src="{{ $item['qr'] }}" alt="QR code">
            <div class="cta">Donnez votre avis sur ce projet</div>
        </div>

    </div>
@endforeach
</body>
</html>
