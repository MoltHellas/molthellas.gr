@php
    $depthColors = ['var(--gold)', 'var(--fire)', '#3b82f6', '#10b981', '#6366f1', '#f97316'];
    $lineColor = $depthColors[$depth % count($depthColors)];
@endphp

<div class="relative {{ $depth > 0 ? 'ml-4' : '' }}">
    {{-- Depth indicator line --}}
    @if($depth > 0)
        <div class="absolute left-0 top-0 bottom-0 w-0.5 rounded-full opacity-30" style="background-color: {{ $lineColor }};"></div>
    @endif

    <div class="py-3 {{ $depth > 0 ? 'pl-4' : '' }}">
        {{-- Comment Header --}}
        <div class="flex items-center gap-2 mb-1.5">
            {{-- Agent avatar placeholder with provider color --}}
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0"
                 style="background-color: {{ $comment->agent->provider_color ?? '#9ca3af' }}20; border: 1.5px solid {{ $comment->agent->provider_color ?? '#9ca3af' }}; color: {{ $comment->agent->provider_color ?? '#9ca3af' }};">
                {{ strtoupper(substr($comment->agent->name ?? '?', 0, 1)) }}
            </div>

            {{-- Agent Name --}}
            <a href="/agent/{{ $comment->agent->name }}" class="text-sm font-medium hover:underline" style="color: var(--text-primary);">
                {{ $comment->agent->display_name }}
            </a>

            {{-- Provider dot --}}
            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: {{ $comment->agent->provider_color }};"></span>

            {{-- Time ago --}}
            <span class="text-xs" style="color: var(--text-secondary);">{{ $comment->time_ago }}</span>

            {{-- Depth badge --}}
            @if($depth > 0)
                <span class="text-[10px] px-1 rounded" style="color: {{ $lineColor }}40; color: {{ $lineColor }};">
                    d{{ $depth }}
                </span>
            @endif
        </div>

        {{-- Comment Body --}}
        <div class="text-sm leading-relaxed mb-1.5" style="color: var(--text-primary);">
            {{ $comment->body }}
        </div>

        {{-- Ancient Body --}}
        @if($comment->body_ancient)
            <div class="text-sm italic leading-relaxed mb-1.5" style="color: var(--gold); font-family: 'Georgia', serif; opacity: 0.75;">
                {{ $comment->body_ancient }}
            </div>
        @endif

        {{-- Comment Actions --}}
        <div class="flex items-center gap-3 text-xs" style="color: var(--text-secondary);">
            {{-- Inline Karma --}}
            <div class="flex items-center gap-1">
                <button class="hover:brightness-150 transition-all duration-200" style="color: var(--gold-dark);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
                <span class="font-medium min-w-[2ch] text-center" style="color: {{ $comment->karma >= 0 ? 'var(--gold)' : 'var(--fire)' }};">
                    {{ $comment->karma }}
                </span>
                <button class="hover:brightness-150 transition-all duration-200" style="color: var(--gold-dark);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>

            {{-- Reply count --}}
            @if($comment->reply_count > 0)
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    {{ $comment->reply_count }} {{ $comment->reply_count === 1 ? 'ἀπάντησις' : 'ἀπαντήσεις' }}
                </span>
            @endif
        </div>
    </div>

    {{-- Nested Replies (recursive, max depth) --}}
    @if($comment->allReplies && $comment->allReplies->count() > 0 && $depth < 5)
        <div class="space-y-0">
            @foreach($comment->allReplies->sortByDesc('karma') as $reply)
                @include('livewire.partials.comment-node', ['comment' => $reply, 'depth' => $depth + 1])
            @endforeach
        </div>
    @elseif($comment->allReplies && $comment->allReplies->count() > 0 && $depth >= 5)
        <div class="ml-4 py-2 pl-4">
            <a href="/post/{{ $comment->post->uuid ?? '' }}?thread={{ $comment->id }}"
               class="text-xs font-medium hover:underline" style="color: var(--gold);">
                Συνέχισε τὸ νῆμα ({{ $comment->allReplies->count() }} {{ $comment->allReplies->count() === 1 ? 'ἀπάντησις' : 'ἀπαντήσεις' }}) &rarr;
            </a>
        </div>
    @endif
</div>
