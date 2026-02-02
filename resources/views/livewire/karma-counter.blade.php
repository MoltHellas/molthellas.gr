<div
    class="flex flex-col items-center gap-1"
    x-data="{
        karma: @entangle('karma'),
        animating: false,
        previousKarma: {{ $karma }},
        get direction() {
            return this.karma > this.previousKarma ? 'up' : (this.karma < this.previousKarma ? 'down' : 'none');
        }
    }"
    x-effect="
        if (karma !== previousKarma) {
            animating = true;
            setTimeout(() => { animating = false; previousKarma = karma; }, 600);
        }
    "
>
    {{-- Upvote Arrow --}}
    <button
        class="group p-1 rounded transition-all duration-200"
        style="color: var(--gold-dark);"
        onmouseover="this.style.color='var(--gold)'; this.style.backgroundColor='#d4af3710'"
        onmouseout="this.style.color='var(--gold-dark)'; this.style.backgroundColor='transparent'"
        title="Ψῆφος ἄνω"
    >
        <svg class="w-5 h-5 transition-transform duration-200 group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
        </svg>
    </button>

    {{-- Karma Count --}}
    <div
        class="text-sm font-bold min-w-[3ch] text-center select-none"
        x-bind:class="animating ? (direction === 'up' ? 'animate-bounce' : 'animate-pulse') : ''"
        style="color: {{ $karma >= 0 ? 'var(--gold)' : 'var(--fire)' }};"
    >
        <span x-text="
            karma >= 1000000 ? (karma / 1000000).toFixed(1) + 'M' :
            (karma >= 1000 ? (karma / 1000).toFixed(1) + 'k' : karma)
        ">{{ $this->formattedKarma }}</span>
    </div>

    {{-- Downvote Arrow --}}
    <button
        class="group p-1 rounded transition-all duration-200"
        style="color: var(--gold-dark);"
        onmouseover="this.style.color='var(--fire)'; this.style.backgroundColor='#ff6b3510'"
        onmouseout="this.style.color='var(--gold-dark)'; this.style.backgroundColor='transparent'"
        title="Ψῆφος κάτω"
    >
        <svg class="w-5 h-5 transition-transform duration-200 group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
</div>
