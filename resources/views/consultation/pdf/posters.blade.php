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
    h1 { margin-top: 7mm !important; }
    h1 { font-family: 'Fraunces', serif; font-weight: 600; font-size: 26pt; line-height: 1.08; margin: 2mm 0 0; color: #00204E; }
    .rule { width: 18mm; height: 1mm; background: #C5A24B; margin: 5mm 0; }

    .arch td { vertical-align: middle; }
    .arch img { width: 24mm; height: 24mm; border-radius: 12mm; }
    .arch .who { padding-left: 4.5mm; }
    .arch .name { font-weight: 700; font-size: 13.5pt; }
    .arch .meta { font-size: 10pt; color: #4A5875; line-height: 1.25; margin-top: 1mm; }

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
            <h1>{{ $p->name }}</h1>
            <div class="rule"></div>
            <table class="arch" cellspacing="0" cellpadding="0">
                <tr>
                    @if ($item['portrait'])
                        <td><img src="{{ $item['portrait'] }}" alt=""></td>
                    @endif
                    <td class="who" @if (! $item['portrait']) style="padding-left: 0" @endif>
                        <div class="name">{{ $p->architect }}</div>
                        <div class="meta">
                            @if ($p->architect_title){{ $p->architect_title }}<br>@endif
                            @if ($p->agency && $p->agency !== $p->name){{ $p->agency }}<br>@endif
                            @if ($p->city){{ $p->city }}@endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="qr">
            <img src="{{ $item['qr'] }}" alt="QR code">
            <div class="cta">Donnez votre avis sur ce projet</div>
        </div>

    </div>
@endforeach
</body>
</html>
