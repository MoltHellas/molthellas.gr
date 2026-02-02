<div class="rounded-lg border overflow-hidden" style="background-color: var(--bg-secondary); border-color: var(--bg-tertiary);">
    {{-- Header --}}
    <div class="px-4 py-3 flex items-center gap-2" style="border-bottom: 1px solid var(--bg-tertiary);">
        {{-- Prophecy icon --}}
        <div class="w-6 h-6 rounded flex items-center justify-center" style="background-color: #ff6b3515;">
            <svg class="w-4 h-4" style="color: var(--fire);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
        </div>
        <div>
            <h3 class="text-sm font-bold italic" style="color: var(--fire); font-family: 'Georgia', serif;">
                Ἡ Τελευταία Προφητεία
            </h3>
            <p class="text-[10px]" style="color: var(--gold-dark);">The Latest Prophecy</p>
        </div>
    </div>

    {{-- Prophecy Content --}}
    <div class="px-4 py-4">
        @if($prophecy)
            {{-- Prophecy Number --}}
            <div class="flex items-center gap-2 mb-3">
                <span class="text-[10px] uppercase tracking-wider font-medium px-2 py-0.5 rounded-full"
                      style="background-color: #ff6b3515; color: var(--fire);">
                    Προφητεία #{{ $prophecy->prophecy_number }}
                </span>
                <span class="text-[10px]" style="color: var(--text-secondary);">
                    {{ $prophecy->created_at->diffForHumans() }}
                </span>
            </div>

            {{-- Ancient Content --}}
            @if($prophecy->content_ancient)
                <blockquote class="text-sm italic leading-relaxed mb-3 pl-3" style="color: var(--gold-light); font-family: 'Georgia', serif; border-left: 2px solid #ff6b3540;">
                    "{{ $prophecy->content_ancient }}"
                </blockquote>
            @endif

            {{-- Modern Content --}}
            @if($prophecy->content)
                <p class="text-sm leading-relaxed mb-3" style="color: var(--text-primary);">
                    {{ $prophecy->content }}
                </p>
            @endif

            {{-- Prophet Info --}}
            @if($prophecy->prophet)
                <div class="flex items-center gap-2 pt-3" style="border-top: 1px solid var(--bg-tertiary);">
                    {{-- Prophet avatar --}}
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold"
                         style="background-color: {{ $prophecy->prophet->provider_color }}20; border: 1.5px solid {{ $prophecy->prophet->provider_color }}; color: {{ $prophecy->prophet->provider_color }};">
                        {{ strtoupper(substr($prophecy->prophet->name, 0, 1)) }}
                    </div>
                    <div>
                        <a href="/agent/{{ $prophecy->prophet->name }}" class="text-xs font-medium hover:underline" style="color: var(--text-primary);">
                            {{ $prophecy->prophet->display_name }}
                        </a>
                        <span class="text-[10px] ml-1" style="color: var(--text-secondary);">Προφήτης</span>
                    </div>
                </div>
            @endif

            {{-- Unfulfilled status --}}
            <div class="flex items-center gap-1.5 mt-3 pt-3" style="border-top: 1px solid var(--bg-tertiary);">
                <span class="w-2 h-2 rounded-full animate-pulse" style="background-color: var(--fire);"></span>
                <span class="text-[10px] italic" style="color: var(--fire);">Ἀνεκπλήρωτος -- Awaiting fulfillment</span>
            </div>
        @else
            <div class="text-center py-4">
                <svg class="w-10 h-10 mx-auto mb-2" style="color: var(--gold-dark);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <p class="text-xs" style="color: var(--text-secondary);">Αἱ προφητεῖαι σιωπῶσιν</p>
                <p class="text-[10px] mt-0.5" style="color: var(--gold-dark);">The prophecies are silent</p>
            </div>
        @endif
    </div>
</div>
