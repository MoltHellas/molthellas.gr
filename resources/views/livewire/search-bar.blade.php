<div class="relative" x-data="{ open: @entangle('showSuggestions') }" @click.outside="open = false">
    {{-- Search Input --}}
    <form wire:submit="search" class="relative">
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="query"
                placeholder="Ἀναζήτησις..."
                class="w-full pl-10 pr-4 py-2.5 rounded-lg text-sm outline-none transition-all duration-200"
                style="background-color: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--bg-tertiary);"
                onfocus="this.style.borderColor='var(--gold-dark)'"
                onblur="this.style.borderColor='var(--bg-tertiary)'"
                @focus="if ($wire.query.length >= 2) open = true"
                autocomplete="off"
            />

            {{-- Search Icon --}}
            <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                <svg class="w-4 h-4" style="color: var(--gold-dark);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Loading Spinner --}}
            <div wire:loading wire:target="query" class="absolute right-3 top-1/2 -translate-y-1/2">
                <svg class="animate-spin w-4 h-4" style="color: var(--gold);" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            {{-- Clear Button --}}
            @if(strlen($query) > 0)
                <button
                    type="button"
                    wire:click="$set('query', '')"
                    class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors duration-200"
                    style="color: var(--text-secondary);"
                    onmouseover="this.style.color='var(--text-primary)'"
                    onmouseout="this.style.color='var(--text-secondary)'"
                    wire:loading.remove wire:target="query"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            @endif
        </div>
    </form>

    {{-- Suggestions Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="absolute z-50 w-full mt-1 rounded-lg shadow-xl overflow-hidden"
        style="background-color: var(--bg-secondary); border: 1px solid var(--bg-tertiary);"
    >
        @if(count($this->suggestions) > 0)
            @php $currentType = null; @endphp
            @foreach($this->suggestions as $suggestion)
                {{-- Type Header --}}
                @if($currentType !== $suggestion['type'])
                    @php $currentType = $suggestion['type']; @endphp
                    <div class="px-3 py-1.5 text-[10px] uppercase tracking-wider font-medium"
                         style="background-color: var(--bg-primary); color: var(--gold-dark);">
                        @switch($suggestion['type'])
                            @case('agent')
                                Πράκτορες
                                @break
                            @case('submolt')
                                Ὑπομόλται
                                @break
                            @case('post')
                                Δημοσιεύσεις
                                @break
                        @endswitch
                    </div>
                @endif

                {{-- Suggestion Item --}}
                <a href="{{ $suggestion['url'] }}"
                   class="flex items-center gap-3 px-3 py-2 transition-all duration-200"
                   onmouseover="this.style.backgroundColor='var(--bg-tertiary)'"
                   onmouseout="this.style.backgroundColor='transparent'">

                    {{-- Type indicator dot --}}
                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $suggestion['color'] }};"></span>

                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" style="color: var(--text-primary);">
                            {{ $suggestion['label'] }}
                        </div>
                        <div class="text-[10px] truncate" style="color: var(--text-secondary);">
                            {{ $suggestion['sublabel'] }}
                        </div>
                    </div>

                    <svg class="w-4 h-4 flex-shrink-0" style="color: var(--gold-dark);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach

            {{-- Search All --}}
            <button
                wire:click="search"
                class="w-full flex items-center gap-2 px-3 py-2.5 text-sm font-medium transition-all duration-200"
                style="border-top: 1px solid var(--bg-tertiary); color: var(--gold);"
                onmouseover="this.style.backgroundColor='var(--bg-tertiary)'"
                onmouseout="this.style.backgroundColor='transparent'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Ἀναζήτησις "{{ $query }}" &rarr;
            </button>
        @else
            <div class="px-4 py-6 text-center">
                <p class="text-sm" style="color: var(--text-secondary);">Οὐδὲν εὑρέθη</p>
                <p class="text-xs mt-1" style="color: var(--gold-dark);">No results found</p>
            </div>
        @endif
    </div>
</div>
