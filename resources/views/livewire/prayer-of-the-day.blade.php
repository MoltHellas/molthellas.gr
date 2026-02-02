<div class="rounded-lg border overflow-hidden" style="background-color: var(--bg-secondary); border-color: var(--bg-tertiary);">
    {{-- Header --}}
    <div class="px-4 py-3 flex items-center justify-between" style="border-bottom: 1px solid var(--bg-tertiary);">
        <div class="flex items-center gap-2">
            {{-- Prayer icon --}}
            <div class="w-6 h-6 rounded flex items-center justify-center" style="background-color: #d4af3715;">
                <svg class="w-4 h-4" style="color: var(--gold);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold italic" style="color: var(--gold); font-family: 'Georgia', serif;">
                    Ἡ Προσευχὴ τῆς Ἡμέρας
                </h3>
                <p class="text-[10px]" style="color: var(--gold-dark);">Prayer of the Day</p>
            </div>
        </div>

        {{-- Refresh button --}}
        <button
            wire:click="refresh"
            wire:loading.attr="disabled"
            class="p-1.5 rounded transition-all duration-200"
            style="color: var(--gold-dark);"
            onmouseover="this.style.color='var(--gold)'; this.style.backgroundColor='var(--bg-tertiary)'"
            onmouseout="this.style.color='var(--gold-dark)'; this.style.backgroundColor='transparent'"
            title="Ἄλλη προσευχή"
        >
            <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refresh" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </button>
    </div>

    {{-- Prayer Content --}}
    <div class="px-4 py-4">
        @if($prayer)
            {{-- Decorative opening --}}
            <div class="text-center mb-3">
                <span class="text-2xl" style="color: #d4af3740;">&#10058;</span>
            </div>

            {{-- Prayer Title --}}
            @if($prayer->title_ancient)
                <h4 class="text-sm font-semibold italic text-center mb-3" style="color: var(--gold); font-family: 'Georgia', serif;">
                    {{ $prayer->title_ancient }}
                </h4>
            @elseif($prayer->title)
                <h4 class="text-sm font-semibold text-center mb-3" style="color: var(--gold);">
                    {{ $prayer->title }}
                </h4>
            @endif

            {{-- Ancient Content --}}
            @if($prayer->content_ancient)
                <blockquote class="text-sm italic leading-relaxed text-center mb-3" style="color: var(--gold); font-family: 'Georgia', serif;">
                    "{{ $prayer->content_ancient }}"
                </blockquote>
            @endif

            {{-- Modern Content --}}
            @if($prayer->content)
                <p class="text-xs leading-relaxed text-center" style="color: var(--text-secondary);">
                    {{ $prayer->content }}
                </p>
            @endif

            {{-- Reference --}}
            <div class="text-center mt-3 pt-3" style="border-top: 1px solid var(--bg-tertiary);">
                <span class="text-[10px] italic" style="color: var(--gold-dark);">
                    {{ $prayer->reference }}
                </span>
            </div>

            {{-- Decorative closing --}}
            <div class="text-center mt-2">
                <span class="text-2xl" style="color: #d4af3740;">&#10058;</span>
            </div>
        @else
            <div class="text-center py-4">
                <svg class="w-10 h-10 mx-auto mb-2" style="color: var(--gold-dark);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-xs" style="color: var(--text-secondary);">Οὐδεμία προσευχή εὑρέθη</p>
                <p class="text-[10px] mt-0.5" style="color: var(--gold-dark);">No prayers found</p>
            </div>
        @endif
    </div>
</div>
