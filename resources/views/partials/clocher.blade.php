{{-- « Le clocher des bâtisseurs » : 1 pierre = 10 €, 1 mur = 100 pierres. Animé par resources/js/modules/clocher.js. --}}
@php
    $raised = (float) ($collecte['raised'] ?? 0);
    $goal = max((float) ($collecte['goal'] ?? 6000000), 1);
    $goalStones = $goal / 10;
    $donUrl = $site['don_url'] ?? 'https://don-nice.catholique.fr/centre-paroissial-st-arnoux-rouret/';
    $fmt = fn ($n) => number_format($n, 0, ',', ' ');
@endphp
<section class="collecte section" id="collecte" data-raised="{{ $raised }}" data-goal="{{ $goal }}">
    <div class="collecte-wrap">
        <div class="collecte-head reveal">
            <span class="eyebrow">{{ $collecte['eyebrow'] ?? 'Le Clocher des Bâtisseurs' }}</span>
            <h2>{{ $collecte['title'] ?? 'Faites monter le clocher.' }}</h2>
            <p>{{ $collecte['text'] ?? '' }}</p>
        </div>

        <div class="board">
            <div class="church-panel">
                <div class="glow"></div>
                <span class="ptag">Le clocher monte</span>
                <svg class="clocher-tower" viewBox="0 0 300 640" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Clocher : {{ $fmt($raised / 10) }} pierres posées sur {{ $fmt($goalStones) }}">
                    <defs>
                        <linearGradient id="stone2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f4ecdc"/><stop offset="1" stop-color="#d3c39c"/></linearGradient>
                        <linearGradient id="wgl" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="rgba(226,201,127,0)"/><stop offset=".5" stop-color="rgba(226,201,127,.95)"/><stop offset="1" stop-color="rgba(226,201,127,0)"/></linearGradient>
                        <clipPath id="riseClip"><rect id="riseRect" x="48" y="590" width="144" height="8"/></clipPath>
                    </defs>
                    <g font-family="Hanken Grotesk Variable, sans-serif">
                        <g stroke="rgba(226,201,127,.28)" stroke-width="1">
                            <line x1="184" y1="60" x2="206" y2="60"/><line x1="184" y1="193" x2="206" y2="193"/>
                            <line x1="184" y1="325" x2="206" y2="325"/><line x1="184" y1="458" x2="206" y2="458"/>
                        </g>
                        <g fill="#E2C97F" font-size="11" font-weight="600">
                            <text x="210" y="58">{{ $fmt($goalStones) }}</text>
                            <text x="210" y="191">{{ $fmt($goalStones * .75) }}</text>
                            <text x="210" y="323">{{ $fmt($goalStones * .5) }}</text>
                            <text x="210" y="456">{{ $fmt($goalStones * .25) }}</text>
                        </g>
                        <g fill="#cdd3df" font-size="9.5"><text x="210" y="70">pierres</text></g>
                    </g>
                    <g fill="none" stroke="#E2C97F" stroke-width="1.5" stroke-dasharray="4 5" opacity=".38" stroke-linejoin="round">
                        <rect x="62" y="182" width="116" height="408"/>
                        <path d="M54 182 L120 60 188 182 Z"/>
                        <rect x="58" y="168" width="124" height="14"/>
                    </g>
                    <g clip-path="url(#riseClip)">
                        <rect x="62" y="182" width="116" height="408" fill="url(#stone2)" stroke="#b89a5a" stroke-width="1"/>
                        <path d="M54 182 L120 60 188 182 Z" fill="url(#stone2)" stroke="#b89a5a" stroke-width="1"/>
                        <rect x="58" y="168" width="124" height="14" fill="url(#stone2)" stroke="#b89a5a" stroke-width="1"/>
                        <path d="M82 300 v-46 a13 13 0 0 1 26 0 v46 Z" fill="#00204E" opacity=".42"/>
                        <path d="M132 300 v-46 a13 13 0 0 1 26 0 v46 Z" fill="#00204E" opacity=".42"/>
                        <circle cx="120" cy="372" r="19" fill="#00204E" opacity=".38"/><circle cx="120" cy="372" r="19" fill="none" stroke="#b89a5a" stroke-width="1.2"/>
                        <path d="M104 590 v-44 a16 16 0 0 1 32 0 v44 Z" fill="#00204E" opacity=".5"/>
                    </g>
                    <rect x="40" y="590" width="160" height="8" rx="2" fill="#d8c9a6"/>
                    <line x1="30" y1="590" x2="210" y2="590" stroke="#E2C97F" stroke-width="1" opacity=".5"/>
                    <g stroke="#E2C97F" stroke-width="3.5" stroke-linecap="round"><line x1="120" y1="60" x2="120" y2="28"/><line x1="107" y1="42" x2="133" y2="42"/></g>
                    <line id="curLine" x1="48" y1="589" x2="192" y2="589" stroke="#E2C97F" stroke-width="1.4" stroke-dasharray="3 3" opacity=".9"/>
                    <rect id="riseLine" x="48" y="588" width="144" height="3" fill="url(#wgl)"/>
                    <text id="curCm" x="120" y="584" fill="#E2C97F" font-family="Fraunces Variable, serif" font-size="15" font-weight="600" text-anchor="middle">0 pierres</text>
                </svg>

                <div class="barwrap">
                    <div class="barline"><div class="big" id="raisedTxt">0 <small>pierres</small></div><div class="pct" id="pctTxt">0 %</div></div>
                    <div class="bar"><i id="barFill"></i></div>
                    <div class="barsub"><span><b>{{ $fmt($raised) }} €</b> collectés</span><span>Objectif : <b>{{ $fmt($goal) }} €</b></span></div>
                </div>
            </div>

            <div class="info">
                <div class="scale-card">
                    <span class="sl">L'Échelle des Bâtisseurs</span>
                    <div class="sv"><span id="pierres">0</span> <small>pierres posées</small></div>
                    <span class="sn">Chaque pierre fait monter le clocher · 1 mur = 100 pierres · objectif : {{ $collecte['goal_label'] ?? 'les cloches en 2030' }}</span>
                </div>
                <div class="wall-card">
                    <div class="wall-head"><span class="sl">Le Mur des Bâtisseurs</span><span class="wall-cnt"><b id="wallPierres">0</b> pierres posées</span></div>
                    <div class="wall" id="wall" aria-hidden="true"></div>
                    <div class="wall-sub">Chaque don de 10 € pose une pierre. <span id="wallPct">0 %</span> du mur est bâti.</div>
                </div>
                <div class="kpis">
                    <div class="kpi"><div class="v"><span id="mursDone">0</span></div><div class="l">Murs bâtis</div></div>
                    <div class="kpi"><div class="v">{{ number_format(max($goal - $raised, 0) / 1000000, 1, ',', ' ') }}&nbsp;M€</div><div class="l">Reste à financer</div></div>
                </div>
                <div class="cta-clocher">
                    <a class="primary" href="{{ $donUrl }}" target="_blank" rel="noopener">Faire un don &rarr;</a>
                </div>
                @if (filled($collecte['quote'] ?? null))
                    <p class="clocher-msg">{{ $collecte['quote'] }}</p>
                @endif
            </div>
        </div>

        <div class="levels">
            <h3>Quel bâtisseur voulez-vous être&nbsp;?</h3>
            <div class="levels-grid">
                <div class="lvl"><b>1 pierre</b><span>10 € : vous posez une pierre du clocher.</span></div>
                <div class="lvl"><b>10 pierres</b><span>100 € : vous montez une rangée du mur.</span></div>
                <div class="lvl"><b>1 mur</b><span>1 000 € : 100 pierres, un mur entier s'élève.</span></div>
                <div class="lvl"><b>5 murs</b><span>5 000 € : le clocher prend de la hauteur.</span></div>
                <div class="lvl"><b>10 murs</b><span>10 000 € : les cloches approchent du sommet.</span></div>
                <div class="lvl"><b>Le clocher</b><span>Ensemble : les cloches sonnent en 2030.</span></div>
            </div>
        </div>
    </div>
</section>
