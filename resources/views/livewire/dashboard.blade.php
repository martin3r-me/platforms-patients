<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Dashboard" icon="heroicon-o-home" />
    </x-slot>

    <x-ui-page-container>

            {{-- Main Stats Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-ui-dashboard-tile
                    title="Active Patients"
                    :count="$activePatients"
                    subtitle="of {{ $totalPatients }}"
                    icon="tag"
                    variant="secondary"
                    size="lg"
                />
            </div>

            <x-ui-panel title="My Active Patients" subtitle="Top 5 Patients">
                <div class="grid grid-cols-1 gap-3">
                    @forelse($activePatientsList as $patient)
                        @php
                            $href = route('patients.patients.show', ['patientsPatient' => $patient['id'] ?? null]);
                        @endphp
                        <a href="{{ $href }}" class="flex items-center gap-3 p-3 rounded-md border border-[var(--ui-border)] bg-white hover:bg-[var(--ui-muted-5)] transition">
                            <div class="w-8 h-8 bg-[var(--ui-primary)] text-[var(--ui-on-primary)] rounded flex items-center justify-center">
                                @svg('heroicon-o-tag', 'w-5 h-5')
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $patient['name'] ?? 'Patient' }}</div>
                                <div class="text-xs text-[var(--ui-muted)] truncate">
                                    {{ $patient['subtitle'] ?? '' }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-3 text-sm text-[var(--ui-muted)] bg-white rounded-md border border-[var(--ui-border)]">No patients found.</div>
                    @endforelse
                </div>
            </x-ui-panel>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Quick Access" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Actions</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary-outline" size="sm" wire:click="createPatient" class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>New Patient</span>
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Quick Stats</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="text-xs text-[var(--ui-muted)]">Active Patients</div>
                            <div class="text-lg font-bold text-[var(--ui-secondary)]">{{ $activePatients ?? 0 }} Patients</div>
                        </div>
                    </div>
                </div>

                {{-- Recent Activity (Dummy) --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Recent Activities</h3>
                    <div class="space-y-2 text-sm">
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">Dashboard loaded</div>
                            <div class="text-[var(--ui-muted)] text-xs">1 minute ago</div>
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
                <div class="space-y-3 text-sm">
                    <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                        <div class="font-medium text-[var(--ui-secondary)] truncate">Dashboard loaded</div>
                        <div class="text-[var(--ui-muted)]">1 minute ago</div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
