<div class="rounded-lg border overflow-hidden" style="background-color: var(--bg-secondary); border-color: var(--bg-tertiary);">
    {{-- Header --}}
    <div class="px-4 py-3 flex items-center gap-2" style="border-bottom: 1px solid var(--bg-tertiary);">
        <div class="w-6 h-6 rounded flex items-center justify-center" style="background-color: #d4af3715;">
            <svg class="w-4 h-4" style="color: var(--gold);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-bold" style="color: var(--gold);">Τάσεις</h3>
            <p class="text-[10px]" style="color: var(--gold-dark);">Trending Topics</p>
        </div>
    </div>

    {{-- Tags List --}}
    <div class="py-1">
        @forelse($tags as $index => $tag)
            <a href="/search?q={{ urlencode($tag->name) }}"
               class="flex items-center gap-3 px-4 py-2.5 transition-all duration-200"
               onmouseover="this.style.backgroundColor='var(--bg-tertiary)'"
               onmouseout="this.style.backgroundColor='transparent'">

                {{-- Rank Number --}}
                <span class="text-xs font-bold w-5 text-right flex-shrink-0" style="color: {{ $index < 3 ? 'var(--gold)' : 'var(--gold-dark)' }};">
                    {{ $index + 1 }}
                </span>

                {{-- Tag Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-medium" style="color: var(--text-primary);">#{{ $tag->name }}</span>
                        @if($tag->name_ancient)
                            <span class="text-[10px] italic truncate" style="color: #d4af3780; font-family: 'Georgia', serif;">
                                {{ $tag->name_ancient }}
                            </span>
                        @endif
                    </div>
                    <div class="text-[10px]" style="color: var(--text-secondary);">
                        {{ number_format($tag->usage_count) }} {{ $tag->usage_count === 1 ? 'χρῆσις' : 'χρήσεις' }}
                    </div>
                </div>

                {{-- Trend indicator bar --}}
                <div class="w-16 h-1.5 rounded-full overflow-hidden flex-shrink-0" style="background-color: var(--bg-primary);">
                    @php
                        $maxUsage = $tags->max('usage_count') ?: 1;
                        $percentage = ($tag->usage_count / $maxUsage) * 100;
                    @endphp
                    <div class="h-full rounded-full transition-all duration-500"
                         style="width: {{ $percentage }}%; background: linear-gradient(to right, var(--gold), {{ $index < 3 ? 'var(--gold-light)' : 'var(--gold-dark)' }});"></div>
                </div>
            </a>
        @empty
            <div class="px-4 py-6 text-center">
                <p class="text-xs" style="color: var(--text-secondary);">Οὐδεμία τάσις</p>
                <p class="text-[10px] mt-0.5" style="color: var(--gold-dark);">No trending topics</p>
            </div>
        @endforelse
    </div>
</div>
