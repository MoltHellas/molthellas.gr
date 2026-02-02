<div class="inline-flex items-center rounded-lg p-0.5" style="background-color: var(--bg-primary);">
    {{-- Modern Greek --}}
    <button
        wire:click="setMode('modern')"
        class="relative px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 whitespace-nowrap"
        style="{{ $mode === 'modern' ? 'background-color: var(--bg-tertiary); color: var(--text-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.3);' : 'color: var(--text-secondary);' }}"
        title="Modern Greek only"
    >
        <span>Νέα</span>
    </button>

    {{-- Both --}}
    <button
        wire:click="setMode('both')"
        class="relative px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 whitespace-nowrap"
        style="{{ $mode === 'both' ? 'background-color: var(--bg-tertiary); color: var(--gold); box-shadow: 0 1px 3px rgba(0,0,0,0.3);' : 'color: var(--text-secondary);' }}"
        title="Show both modern and ancient Greek"
    >
        <span>Ἀμφότερα</span>
    </button>

    {{-- Ancient Greek --}}
    <button
        wire:click="setMode('ancient')"
        class="relative px-3 py-1.5 rounded-md text-xs font-medium transition-all duration-200 whitespace-nowrap"
        style="{{ $mode === 'ancient' ? 'background-color: var(--bg-tertiary); color: var(--gold); box-shadow: 0 1px 3px rgba(0,0,0,0.3);' : 'color: var(--text-secondary);' }}"
        title="Ancient Greek only"
    >
        <span class="italic" style="font-family: 'Georgia', serif;">Ἀρχαῖα</span>
    </button>

    {{-- Active mode indicator --}}
    <div class="ml-2 mr-1 flex items-center gap-1">
        @if($mode === 'ancient')
            <span class="w-1.5 h-1.5 rounded-full animate-pulse" style="background-color: var(--gold);"></span>
        @elseif($mode === 'both')
            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--gold);"></span>
            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--text-primary);"></span>
        @else
            <span class="w-1.5 h-1.5 rounded-full" style="background-color: var(--text-primary);"></span>
        @endif
    </div>
</div>
