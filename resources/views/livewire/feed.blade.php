<div>
    {{-- Sort Tabs --}}
    <div class="flex items-center gap-1 mb-6 rounded-lg p-1" style="background-color: var(--bg-secondary);">
        <button
            wire:click="setSort('hot')"
            class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-all duration-200"
            style="{{ $sort === 'hot' ? 'background-color: var(--bg-tertiary); color: var(--fire);' : 'color: var(--text-secondary);' }}"
        >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
            </svg>
            <span>Θερμά</span>
        </button>

        <button
            wire:click="setSort('new')"
            class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-all duration-200"
            style="{{ $sort === 'new' ? 'background-color: var(--bg-tertiary); color: var(--gold);' : 'color: var(--text-secondary);' }}"
        >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.828a1 1 0 101.415-1.414L11 9.586V6z" clip-rule="evenodd"/>
            </svg>
            <span>Νέα</span>
        </button>

        <button
            wire:click="setSort('top')"
            class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-all duration-200"
            style="{{ $sort === 'top' ? 'background-color: var(--bg-tertiary); color: var(--gold);' : 'color: var(--text-secondary);' }}"
        >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            <span>Κορυφαία</span>
        </button>

        {{-- Period selector for Top sort --}}
        @if($sort === 'top')
            <div class="ml-auto flex items-center gap-1">
                @foreach(['today' => 'Σήμερα', 'week' => 'Εβδομάδα', 'month' => 'Μήνας', 'year' => 'Έτος', 'all' => 'Πάντα'] as $key => $label)
                    <button
                        wire:click="setPeriod('{{ $key }}')"
                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200"
                        style="{{ $period === $key ? 'background-color: var(--gold); color: var(--bg-primary);' : 'color: var(--gold-dark);' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Posts List --}}
    <div class="space-y-3">
        @forelse($posts as $post)
            <livewire:post-card :post="$post" :key="'post-'.$post->id" />
        @empty
            <div class="text-center py-16 rounded-lg" style="background-color: var(--bg-secondary);">
                <svg class="w-16 h-16 mx-auto mb-4" style="color: var(--gold-dark);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                </svg>
                <p class="text-lg font-medium" style="color: var(--gold);">Οὐδεμία δημοσίευσις</p>
                <p class="text-sm mt-1" style="color: var(--text-secondary);">No posts yet in this realm</p>
            </div>
        @endforelse
    </div>

    {{-- Load More --}}
    @if($hasMore)
        <div class="mt-6 text-center">
            <button
                wire:click="loadMore"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-medium transition-all duration-200 border"
                style="border-color: var(--gold-dark); color: var(--gold); background-color: transparent;"
                onmouseover="this.style.backgroundColor='var(--bg-tertiary)'"
                onmouseout="this.style.backgroundColor='transparent'"
            >
                <span wire:loading.remove wire:target="loadMore">Φόρτωσε περισσότερα</span>
                <span wire:loading wire:target="loadMore" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Φορτώνει...
                </span>
            </button>
        </div>
    @endif

    {{-- Loading overlay for sort changes --}}
    <div wire:loading.flex wire:target="setSort, setPeriod" class="fixed inset-0 z-50 items-center justify-center" style="background-color: rgba(10, 9, 8, 0.6);">
        <div class="flex flex-col items-center gap-3">
            <svg class="animate-spin w-8 h-8" style="color: var(--gold);" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-sm" style="color: var(--gold);">Φορτώνει...</span>
        </div>
    </div>
</div>
