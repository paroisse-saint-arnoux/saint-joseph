@use('App\Support\Consultation')
@php
    $scale = Consultation::SCALES[$name];
    $visible = Consultation::VISIBLE_STEPS[$name];
    $sign = fn (int $v) => $v > 0 ? '+'.$v : ($v < 0 ? '−'.abs($v) : '0');
@endphp
<fieldset class="cs-scale cs-scale-{{ $name }}" data-scale>
    <legend class="cs-scale-title">{{ $scale['label'] }}</legend>
    <div class="cs-scale-options">
        @foreach ($scale['steps'] as $v => $text)
            <label class="cs-opt" data-v="{{ $v }}">
                <input type="radio" name="{{ $name }}" value="{{ $v }}" data-text="{{ $text }}" @checked($value === $v)>
                <span class="cs-opt-num" aria-hidden="true">{{ $sign($v) }}</span>
                <span class="visually-hidden">{{ $sign($v) }} : {{ $text }}</span>
            </label>
        @endforeach
    </div>
    <div class="cs-scale-labels count-{{ count($visible) }}" aria-hidden="true">
        @foreach ($visible as $v)<span>{{ $scale['steps'][$v] }}</span>@endforeach
    </div>
    <p class="cs-scale-choice" aria-hidden="true" data-choice>@if ($value !== null)Votre réponse&nbsp;: <b>{{ $scale['steps'][$value] }}</b>@endif</p>
</fieldset>
