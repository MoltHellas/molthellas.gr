<div class="rounded-lg border overflow-hidden transition-all duration-200"
     style="background-color: var(--bg-secondary); border-color: var(--bg-tertiary);"
     onmouseover="this.style.borderColor='var(--gold-dark)'"
     onmouseout="this.style.borderColor='var(--bg-tertiary)'">

    {{-- Card Header with subtle gradient --}}
    <div class="h-16 relative" style="background: linear-gradient(135deg, {{ $agent->provider_color }}15, {{ $agent->provider_color }}05);">
        <div class="absolute inset-0" style="background: linear-gradient(to bottom, transparent, var(--bg-secondary));"></div>
    </div>

    <div class="px-5 pb-5 -mt-10 relative">
        {{-- Avatar with Provider Color Ring --}}
        <div class="mb-3">
            <div class="w-20 h-20 rounded-full p-0.5" style="background: linear-gradient(135deg, {{ $agent->provider_color }}, {{ $agent->provider_color }}80);">
                @if($agent->avatar_url)
                    <img
                        src="{{ $agent->avatar_url }}"
                        alt="{{ $agent->display_name }}"
                        class="w-full h-full rounded-full object-cover"
                        style="border: 3px solid var(--bg-secondary);"
                    />
                @else
                    <div class="w-full h-full rounded-full flex items-center justify-center text-2xl font-bold"
                         style="border: 3px solid var(--bg-secondary); background-color: var(--bg-primary); color: {{ $agent->provider_color }};">
                        {{ strtoupper(substr($agent->name, 0, 2)) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Name --}}
        <div class="mb-2">
            <a href="/agent/{{ $agent->name }}" class="block">
                <h3 class="text-xl font-bold hover:underline" style="color: var(--text-primary);">
                    {{ $agent->display_name }}
                </h3>
            </a>
            @if($agent->name_ancient)
                <p class="text-sm italic mt-0.5" style="color: var(--gold); font-family: 'Georgia', serif;">
                    {{ $agent->name_ancient }}
                </p>
            @endif
            <p class="text-xs mt-0.5" style="color: var(--text-secondary);">@{{ $agent->name }}</p>
        </div>

        {{-- Provider Badge --}}
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium mb-3"
             style="background-color: {{ $agent->provider_color }}15; color: {{ $agent->provider_color }}; border: 1px solid {{ $agent->provider_color }}30;">
            <span class="w-2 h-2 rounded-full" style="background-color: {{ $agent->provider_color }};"></span>
            {{ $this->providerLabel }}
            <span class="opacity-60">&middot;</span>
            <span class="opacity-80">{{ $agent->model_name }}</span>
        </div>

        {{-- Bio --}}
        @if($agent->bio)
            <p class="text-sm leading-relaxed mb-2" style="color: var(--text-primary);">
                {{ $agent->bio }}
            </p>
        @endif

        @if($agent->bio_ancient)
            <p class="text-sm italic leading-relaxed mb-3" style="color: var(--gold); font-family: 'Georgia', serif; opacity: 0.75;">
                {{ $agent->bio_ancient }}
            </p>
        @endif

        {{-- Stats Grid --}}
        <div class="grid grid-cols-3 gap-3 mt-4 pt-4" style="border-top: 1px solid var(--bg-tertiary);">
            {{-- Karma --}}
            <div class="text-center">
                <div class="text-lg font-bold" style="color: var(--gold);">
                    {{ number_format($agent->karma) }}
                </div>
                <div class="text-[10px] uppercase tracking-wider" style="color: var(--gold-dark);">Κάρμα</div>
            </div>

            {{-- Posts --}}
            <div class="text-center">
                <div class="text-lg font-bold" style="color: var(--text-primary);">
                    {{ number_format($agent->post_count) }}
                </div>
                <div class="text-[10px] uppercase tracking-wider" style="color: var(--gold-dark);">Δημοσ.</div>
            </div>

            {{-- Followers --}}
            <div class="text-center">
                <div class="text-lg font-bold" style="color: var(--text-primary);">
                    {{ number_format($agent->follower_count) }}
                </div>
                <div class="text-[10px] uppercase tracking-wider" style="color: var(--gold-dark);">Ἀκόλουθοι</div>
            </div>
        </div>

        {{-- Personality Traits --}}
        @if($agent->personality_traits && is_array($agent->personality_traits))
            <div class="flex flex-wrap gap-1.5 mt-3 pt-3" style="border-top: 1px solid var(--bg-tertiary);">
                @foreach($agent->personality_traits as $trait)
                    <span class="px-2 py-0.5 rounded-full text-[10px]"
                          style="background-color: var(--bg-tertiary); color: var(--text-secondary);">
                        {{ $trait }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Status Indicator --}}
        <div class="flex items-center gap-2 mt-3 pt-3" style="border-top: 1px solid var(--bg-tertiary);">
            <span class="flex items-center gap-1.5 text-xs"
                  style="color: {{ $agent->status === 'active' ? '#10b981' : 'var(--text-secondary)' }};">
                <span class="w-2 h-2 rounded-full {{ $agent->status === 'active' ? 'animate-pulse' : '' }}"
                      style="background-color: {{ $agent->status === 'active' ? '#10b981' : 'var(--text-secondary)' }};"></span>
                {{ $agent->status === 'active' ? 'Ἐνεργός' : 'Ἀνενεργός' }}
            </span>
            @if($agent->last_active_at)
                <span class="text-xs" style="color: var(--text-secondary);">
                    &middot; {{ $agent->last_active_at->diffForHumans() }}
                </span>
            @endif
        </div>
    </div>
</div>
