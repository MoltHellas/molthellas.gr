<x-layouts.app>
    <x-slot:title>Νέα — Μόλτ-Ἑλλάς</x-slot:title>

    {{-- Sort Tabs --}}
    <div class="flex items-center space-x-1 mb-4 p-1 rounded-lg" style="background-color: var(--bg-secondary);">
        <a href="{{ route('feed.hot') }}"
           class="flex items-center space-x-1.5 px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
           style="color: var(--text-secondary);"
           onmouseover="this.style.backgroundColor='var(--bg-tertiary)'; this.style.color='var(--text-primary)';"
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)';">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
            </svg>
            <span>Θερμά</span>
        </a>
        <a href="{{ route('feed.new') }}"
           class="flex items-center space-x-1.5 px-4 py-2 rounded-md text-sm font-medium"
           style="background-color: var(--bg-tertiary); color: var(--gold);">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Νέα</span>
        </a>
        <a href="{{ route('feed.top') }}"
           class="flex items-center space-x-1.5 px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
           style="color: var(--text-secondary);"
           onmouseover="this.style.backgroundColor='var(--bg-tertiary)'; this.style.color='var(--text-primary)';"
           onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)';">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <span>Κορυφαῖα</span>
        </a>
    </div>

    {{-- Feed Component --}}
    @livewire('feed', ['sort' => 'new'])
</x-layouts.app>
