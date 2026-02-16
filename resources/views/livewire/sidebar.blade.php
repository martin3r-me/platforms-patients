{{-- Patients Sidebar - Main navigation with patient search --}}
<div
    x-data="{
        init() {
            const savedState = localStorage.getItem('patients.showAllPatients');
            if (savedState !== null) {
                @this.set('showAllPatients', savedState === 'true');
            }
        }
    }"
>
    {{-- Module Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Patients
    </div>

    {{-- Section: General (via UI components) --}}
    <x-ui-sidebar-list label="General">
        <x-ui-sidebar-item :href="route('patients.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- New Patient --}}
    <x-ui-sidebar-list>
        <x-ui-sidebar-item wire:click="createPatient">
            @svg('heroicon-o-plus-circle', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">New Patient</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only for General --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('patients.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createPatient" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Section: Patient Search & List --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- Quick Search --}}
            <div class="px-3 pb-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        @svg('heroicon-o-magnifying-glass', 'w-3.5 h-3.5 text-[var(--ui-muted)]')
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.200ms="search"
                        placeholder="Search patients..."
                        class="w-full pl-8 pr-8 py-1.5 text-xs bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60 rounded-md text-[var(--ui-secondary)] placeholder-[var(--ui-muted)] focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]/40 focus:border-[var(--ui-primary)]/40 transition-colors"
                    />
                    @if($search)
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]"
                        >
                            @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                        </button>
                    @endif
                </div>
            </div>

            {{-- Patient Count --}}
            <div class="px-3 pb-1">
                <span class="text-[10px] uppercase tracking-wider text-[var(--ui-muted)] font-medium">
                    @if($search)
                        {{ $patients->count() }} of {{ $allPatientsCount }} patients
                    @else
                        {{ $allPatientsCount }} {{ Str::plural('patient', $allPatientsCount) }}
                    @endif
                </span>
            </div>

            {{-- Patient List --}}
            @if($patients->isNotEmpty())
                <x-ui-sidebar-list>
                    @foreach($patients as $patient)
                        <x-ui-sidebar-item :href="route('patients.patients.show', ['patientsPatient' => $patient])">
                            <div class="flex items-center justify-center w-6 h-6 rounded-md {{ $patient->done ? 'bg-[var(--ui-success-5)]' : 'bg-[var(--ui-primary-5)]' }} flex-shrink-0">
                                @if($patient->done)
                                    @svg('heroicon-o-check-circle', 'w-3.5 h-3.5 text-[var(--ui-success)]')
                                @else
                                    @svg('heroicon-o-user', 'w-3.5 h-3.5 text-[var(--ui-primary)]')
                                @endif
                            </div>
                            <div class="flex-1 min-w-0 ml-2">
                                <div class="truncate text-sm font-medium">{{ $patient->name }}</div>
                                @if($patient->description)
                                    <div class="truncate text-[10px] text-[var(--ui-muted)]">{{ Str::limit($patient->description, 30) }}</div>
                                @endif
                            </div>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @endif

            {{-- Button to show/hide all patients --}}
            @if($hasMorePatients)
                <div class="px-3 py-2">
                    <button
                        type="button"
                        wire:click="toggleShowAllPatients"
                        x-on:click="localStorage.setItem('patients.showAllPatients', (!$wire.showAllPatients).toString())"
                        class="flex items-center gap-2 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                    >
                        @if($showAllPatients)
                            @svg('heroicon-o-eye-slash', 'w-4 h-4')
                            <span>Only my patients</span>
                        @else
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            <span>Show all patients</span>
                        @endif
                    </button>
                </div>
            @endif

            {{-- No Patients --}}
            @if($patients->isEmpty())
                <div class="px-3 py-3 text-center">
                    @if($search)
                        <div class="text-xs text-[var(--ui-muted)]">
                            @svg('heroicon-o-magnifying-glass', 'w-4 h-4 mx-auto mb-1 opacity-50')
                            No patients matching "{{ $search }}"
                        </div>
                    @else
                        <div class="text-xs text-[var(--ui-muted)]">No patients yet</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Keyboard shortcut hint --}}
    <div x-show="!collapsed" class="px-3 py-2 mt-2 border-t border-[var(--ui-border)]">
        <div class="text-[10px] text-[var(--ui-muted)] space-y-0.5">
            <div class="flex justify-between"><span>Search</span><kbd class="px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-[9px] font-mono">Ctrl+K</kbd></div>
            <div class="flex justify-between"><span>Next patient</span><kbd class="px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-[9px] font-mono">Alt+&darr;</kbd></div>
            <div class="flex justify-between"><span>Prev patient</span><kbd class="px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-[9px] font-mono">Alt+&uarr;</kbd></div>
        </div>
    </div>
</div>
