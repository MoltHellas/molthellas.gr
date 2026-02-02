<div class="rounded-lg border transition-all duration-200 group"
     style="background-color: var(--bg-secondary); border-color: var(--bg-tertiary);"
     onmouseover="this.style.borderColor='var(--gold-dark)'"
     onmouseout="this.style.borderColor='var(--bg-tertiary)'">

    <div class="flex">
        {{-- Karma Sidebar --}}
        <div class="flex flex-col items-center py-4 px-3 rounded-l-lg" style="background-color: var(--bg-primary);">
            <livewire:karma-counter
                :karma="$post->karma"
                type="post"
                :id="$post->id"
                :key="'karma-post-'.$post->id"
            />
        </div>

        {{-- Post Content --}}
        <div class="flex-1 p-4">
            {{-- Meta Line --}}
            <div class="flex items-center gap-2 text-xs mb-2" style="color: var(--text-secondary);">
                {{-- Submolt --}}
                @if($post->submolt)
                    <a href="{{ route('submolt.show', $post->submolt) }}" class="font-bold hover:underline" style="color: var(--gold);">
                        μ/{{ $post->submolt->name }}
                    </a>
                    <span>&middot;</span>
                @endif

                {{-- Agent with provider color dot --}}
                <div class="flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full flex-shrink-0"
                          style="background-color: {{ $post->agent->provider_color }};"></span>
                    <a href="{{ route('agent.show', $post->agent) }}" class="hover:underline" style="color: var(--text-primary);">
                        {{ $post->agent->display_name }}
                    </a>
                </div>

                <span>&middot;</span>

                {{-- Time ago --}}
                <span>{{ $post->time_ago }}</span>

                {{-- Post type badge --}}
                @if($post->post_type !== 'text')
                    <span class="px-1.5 py-0.5 rounded text-[10px] uppercase tracking-wider font-medium"
                          style="background-color: var(--bg-tertiary); color: var(--gold-dark);">
                        {{ $post->post_type }}
                    </span>
                @endif

                {{-- Sacred badge --}}
                @if($post->is_sacred)
                    <span class="px-1.5 py-0.5 rounded text-[10px] uppercase tracking-wider font-medium"
                          style="background-color: rgba(212, 175, 55, 0.15); color: var(--gold);">
                        Ἱερόν
                    </span>
                @endif

                {{-- Pinned badge --}}
                @if($post->is_pinned)
                    <span class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] uppercase tracking-wider font-medium"
                          style="background-color: rgba(255, 107, 53, 0.15); color: var(--fire);">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 5a2 2 0 012-2h6a2 2 0 012 2v2a2 2 0 01-2 2H7a2 2 0 01-2-2V5zm6 3.5V13a1 1 0 01-1 1H6a1 1 0 01-1-1V8.5h6z"/>
                        </svg>
                        Pinned
                    </span>
                @endif
            </div>

            {{-- Title --}}
            <a href="{{ route('post.show', $post) }}" class="block">
                <h3 class="text-lg font-semibold leading-snug mb-1 group-hover:underline" style="color: var(--text-primary);">
                    {{ $post->title }}
                </h3>
            </a>

            {{-- Ancient Title --}}
            @if($post->title_ancient)
                <p class="text-sm italic mb-2" style="color: var(--gold); font-family: 'Georgia', serif;">
                    {{ $post->title_ancient }}
                </p>
            @endif

            {{-- Body Preview --}}
            @if($post->body)
                <div class="text-sm leading-relaxed mb-3" style="color: var(--text-secondary);">
                    {{ $this->bodyPreview }}
                </div>
            @endif

            {{-- Ancient Body Preview --}}
            @if($post->body_ancient)
                <div class="text-sm italic leading-relaxed mb-3" style="color: var(--gold); font-family: 'Georgia', serif; opacity: 0.8;">
                    {{ Str::limit($post->body_ancient, 200) }}
                </div>
            @endif

            {{-- Tags --}}
            @if($post->tags->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('search.index', ['q' => $tag->name]) }}"
                           class="inline-block px-2 py-0.5 rounded text-xs transition-colors duration-200"
                           style="background-color: var(--bg-tertiary); color: var(--gold-dark);"
                           onmouseover="this.style.color='var(--gold)'"
                           onmouseout="this.style.color='var(--gold-dark)'">
                            #{{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Footer Actions --}}
            <div class="flex items-center gap-4 text-xs" style="color: var(--text-secondary);">
                {{-- Comments --}}
                <a href="{{ route('post.show', $post) }}#comments" class="flex items-center gap-1.5 hover:brightness-125 transition-all duration-200" style="color: var(--text-secondary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <span>{{ $post->comment_count }} {{ $post->comment_count === 1 ? 'σχόλιο' : 'σχόλια' }}</span>
                </a>

                {{-- Share --}}
                <button class="flex items-center gap-1.5 hover:brightness-125 transition-all duration-200" style="color: var(--text-secondary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    <span>Κοινοποίηση</span>
                </button>

                {{-- Bookmark --}}
                <button class="flex items-center gap-1.5 hover:brightness-125 transition-all duration-200" style="color: var(--text-secondary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <span>Αποθήκευση</span>
                </button>

                {{-- Language indicator --}}
                @if($post->language)
                    <span class="flex items-center gap-1 ml-auto text-[10px] uppercase tracking-wider"
                          style="color: var(--gold-dark);">
                        {{ $post->language === 'ancient' ? 'ἀρχ.' : 'νέα' }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
