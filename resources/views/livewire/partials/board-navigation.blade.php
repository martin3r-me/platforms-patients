{{-- Board Navigation Sidebar Partial --}}
{{-- Requires: $boardNavigation, $patient --}}

{{-- Board Navigation --}}
<div>
    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">
        <a href="{{ route('patients.patients.show', $patient) }}" wire:navigate class="hover:text-[var(--ui-primary)] transition-colors">
            {{ $patient->name }}
        </a>
    </h3>
    <nav class="space-y-0.5">
        @foreach($boardNavigation as $index => $nav)
            <a
                href="{{ $nav['route'] }}"
                wire:navigate
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-all duration-150 group
                    @if($nav['active'])
                        bg-{{ $nav['color'] }}-50 text-{{ $nav['color'] }}-700 font-semibold border border-{{ $nav['color'] }}-200
                    @else
                        font-medium text-[var(--ui-secondary)] hover:bg-{{ $nav['color'] }}-50 hover:text-{{ $nav['color'] }}-700
                    @endif
                "
            >
                <div class="flex items-center justify-center w-7 h-7 rounded-md transition-colors
                    @if($nav['active'])
                        bg-{{ $nav['color'] }}-100
                    @else
                        bg-{{ $nav['color'] }}-50 group-hover:bg-{{ $nav['color'] }}-100
                    @endif
                ">
                    @svg($nav['icon'], 'w-4 h-4 text-' . $nav['color'] . '-600')
                </div>
                <span class="flex-1 truncate">{{ $nav['name'] }}</span>
                @if($nav['active'])
                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $nav['color'] }}-500"></span>
                @endif
                <kbd class="hidden group-hover:inline-block px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-[9px] font-mono text-[var(--ui-muted)]">{{ $index + 1 }}</kbd>
            </a>
        @endforeach
    </nav>
</div>

{{-- Back to Patient --}}
<div class="pt-2">
    <a href="{{ route('patients.patients.show', $patient) }}" wire:navigate class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-[var(--ui-muted)] hover:text-[var(--ui-primary)] transition-colors rounded-lg hover:bg-[var(--ui-muted-5)]">
        @svg('heroicon-o-arrow-left', 'w-3.5 h-3.5')
        <span>Back to Patient</span>
    </a>
</div>
