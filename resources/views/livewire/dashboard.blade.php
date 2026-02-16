<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Patients" icon="heroicon-o-user-group" />
    </x-slot>

    <x-ui-page-container>

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-ui-dashboard-tile
                title="Active"
                :count="$activePatients"
                icon="tag"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Completed"
                :count="$completedPatients"
                icon="check-circle"
                variant="secondary"
                size="lg"
            />
            <x-ui-dashboard-tile
                title="Total"
                :count="$totalPatients"
                icon="users"
                variant="secondary"
                size="lg"
            />
        </div>

        {{-- Search & Filter Bar --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    @svg('heroicon-o-magnifying-glass', 'w-4 h-4 text-[var(--ui-muted)]')
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search patients..."
                    class="w-full pl-9 pr-8 py-2 text-sm border border-[var(--ui-border)] rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)] placeholder:text-[var(--ui-muted)]"
                />
                @if($search)
                    <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-1 bg-[var(--ui-muted-5)] rounded-lg p-0.5 border border-[var(--ui-border)]/40">
                <button
                    wire:click="$set('filter', 'active')"
                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filter === 'active' ? 'bg-white text-[var(--ui-secondary)] shadow-sm border border-[var(--ui-border)]/60' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    Active
                </button>
                <button
                    wire:click="$set('filter', 'completed')"
                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filter === 'completed' ? 'bg-white text-[var(--ui-secondary)] shadow-sm border border-[var(--ui-border)]/60' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    Completed
                </button>
                <button
                    wire:click="$set('filter', 'all')"
                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $filter === 'all' ? 'bg-white text-[var(--ui-secondary)] shadow-sm border border-[var(--ui-border)]/60' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                >
                    All
                </button>
            </div>
        </div>

        {{-- Patient List --}}
        <div class="grid grid-cols-1 gap-2">
            @forelse($patients as $patient)
                <a
                    href="{{ route('patients.patients.show', $patient) }}"
                    wire:navigate
                    class="flex items-center gap-3 p-3 rounded-lg border border-[var(--ui-border)]/60 bg-white hover:bg-[var(--ui-muted-5)] hover:border-[var(--ui-border)] transition group"
                >
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $patient->done ? 'bg-emerald-50 text-emerald-600' : 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)]' }} shrink-0">
                        @svg($patient->done ? 'heroicon-o-check-circle' : 'heroicon-o-user', 'w-5 h-5')
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm text-[var(--ui-secondary)] truncate group-hover:text-[var(--ui-primary)] transition-colors">
                            {{ $patient->name }}
                        </div>
                        @if($patient->description)
                            <div class="text-xs text-[var(--ui-muted)] truncate mt-0.5">{{ $patient->description }}</div>
                        @endif
                    </div>
                    <div class="text-xs text-[var(--ui-muted)] shrink-0">
                        {{ $patient->created_at->format('d.m.Y') }}
                    </div>
                    <div class="text-[var(--ui-muted)] group-hover:text-[var(--ui-secondary)] transition-colors shrink-0">
                        @svg('heroicon-o-chevron-right', 'w-4 h-4')
                    </div>
                </a>
            @empty
                <div class="py-12 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-[var(--ui-muted-5)] mb-3">
                        @svg('heroicon-o-user-group', 'w-7 h-7 text-[var(--ui-muted)]')
                    </div>
                    @if($search)
                        <p class="text-sm text-[var(--ui-muted)]">No patients matching "{{ $search }}"</p>
                        <button wire:click="$set('search', '')" class="text-xs text-[var(--ui-primary)] hover:underline mt-1">Clear search</button>
                    @else
                        <p class="text-sm text-[var(--ui-muted)]">No patients found</p>
                        <p class="text-xs text-[var(--ui-muted)] mt-1">Create your first patient to get started</p>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($patients->hasPages())
            <div class="mt-4">
                {{ $patients->links() }}
            </div>
        @endif

    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Overview" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-5">
                {{-- Actions --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Actions</h3>
                    <div class="flex flex-col gap-1.5">
                        <x-ui-button variant="secondary" size="sm" wire:click="createPatient" class="w-full">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>New Patient</span>
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- Stats --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Statistics</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                            <span class="text-[var(--ui-muted)]">Active</span>
                            <span class="text-[var(--ui-secondary)] font-medium">{{ $activePatients }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                            <span class="text-[var(--ui-muted)]">Completed</span>
                            <span class="text-[var(--ui-secondary)] font-medium">{{ $completedPatients }}</span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                            <span class="text-[var(--ui-muted)]">Total</span>
                            <span class="text-[var(--ui-secondary)] font-medium">{{ $totalPatients }}</span>
                        </div>
                    </div>
                </div>

                {{-- Shortcuts --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Shortcuts</h3>
                    <div class="space-y-1 text-xs text-[var(--ui-muted)]">
                        <div class="flex justify-between py-1">
                            <span>Search</span>
                            <kbd class="px-1.5 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-[10px] font-mono">Ctrl+K</kbd>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Activities" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Recent Activities</div>
                <div class="py-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                        @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                    </div>
                    <p class="text-sm text-[var(--ui-muted)]">No activities yet</p>
                    <p class="text-xs text-[var(--ui-muted)] mt-1">Changes will be displayed here</p>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
