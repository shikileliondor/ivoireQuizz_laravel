@php($playUrl = config('site.play_store_url'))

@if ($playUrl)
    <a href="{{ $playUrl }}" target="_blank" rel="noopener" class="group inline-flex items-center gap-3 rounded-2xl bg-ink px-5 py-3 text-white shadow-lg shadow-stone-900/20 transition hover:-translate-y-0.5 hover:bg-stone-800 {{ $class ?? '' }}">
        <svg class="size-7 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 3.5v17a1 1 0 0 0 1.5.87l14.7-8.5a1 1 0 0 0 0-1.74L6.5 2.63A1 1 0 0 0 5 3.5Z" fill="#f77f00"/>
            <path d="M5 12h16.7a1 1 0 0 1-.5.87L6.5 21.37A1 1 0 0 1 5 20.5Z" fill="#009e60"/>
        </svg>
        <span class="text-left leading-tight">
            <span class="block text-[11px] font-medium tracking-wide text-stone-300 uppercase">Télécharger sur</span>
            <span class="block text-lg font-semibold">Google Play</span>
        </span>
    </a>
@else
    <span class="inline-flex items-center gap-3 rounded-2xl bg-white px-5 py-3 text-ink ring-1 ring-stone-300 {{ $class ?? '' }}">
        <svg class="size-7 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M5 3.5v17a1 1 0 0 0 1.5.87l14.7-8.5a1 1 0 0 0 0-1.74L6.5 2.63A1 1 0 0 0 5 3.5Z" fill="#f77f00"/>
            <path d="M5 12h16.7a1 1 0 0 1-.5.87L6.5 21.37A1 1 0 0 1 5 20.5Z" fill="#009e60"/>
        </svg>
        <span class="text-left leading-tight">
            <span class="block text-[11px] font-medium tracking-wide text-stone-500 uppercase">Bientôt disponible sur</span>
            <span class="block text-lg font-semibold">Google Play</span>
        </span>
    </span>
@endif
