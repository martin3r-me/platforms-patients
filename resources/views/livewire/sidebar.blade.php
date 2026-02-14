{{-- Patients Sidebar - Structure based on Planner template --}}
<div 
    x-data="{
        init() {
            // Load state from localStorage on initialization
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
        {{-- More items will be added later --}}
        {{-- <x-ui-sidebar-item :href="route('patients.xxx')">
            @svg('heroicon-o-xxx', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">XXX</span>
        </x-ui-sidebar-item> --}}
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
            {{-- More icons will be added later --}}
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <button type="button" wire:click="createPatient" class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
            @svg('heroicon-o-plus-circle', 'w-5 h-5')
        </button>
    </div>

    {{-- Section: Patients --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            {{-- Only show patients if there are any --}}
            @if($patients->isNotEmpty())
                <x-ui-sidebar-list :label="'Patients' . ($showAllPatients ? ' (' . $allPatientsCount . ')' : '')">
                    @foreach($patients as $patient)
                        <x-ui-sidebar-item :href="route('patients.patients.show', ['patientsPatient' => $patient])">
                            @svg('heroicon-o-tag', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2">
                                <div class="truncate text-sm font-medium">{{ $patient->name }}</div>
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
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                    @if($showAllPatients)
                        No patients
                    @else
                        No patients available
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
