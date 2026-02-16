<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$patient->name" icon="heroicon-o-clipboard-document-list">
            <x-slot name="actions">
                <div class="flex items-center gap-2">
                    {{-- Patient navigation --}}
                    <button wire:click="navigateToPreviousPatient" class="inline-flex items-center gap-1 px-2 py-1.5 text-sm text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors rounded-md hover:bg-[var(--ui-muted-5)]" title="Previous patient (Alt+Up)">
                        @svg('heroicon-o-chevron-left', 'w-4 h-4')
                    </button>
                    <button wire:click="navigateToNextPatient" class="inline-flex items-center gap-1 px-2 py-1.5 text-sm text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors rounded-md hover:bg-[var(--ui-muted-5)]" title="Next patient (Alt+Down)">
                        @svg('heroicon-o-chevron-right', 'w-4 h-4')
                    </button>
                    @if($patient->done)
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-[var(--ui-success-5)] text-[var(--ui-success)] text-xs font-medium border border-[var(--ui-success)]/20">
                            @svg('heroicon-o-check-circle', 'w-3.5 h-3.5')
                            Completed
                        </span>
                    @endif
                </div>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Patient Header --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]/20">
                        @svg('heroicon-o-user', 'w-6 h-6 text-[var(--ui-primary)]')
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-[var(--ui-secondary)] leading-tight">{{ $patient->name }}</h1>
                        @if($patient->description)
                            <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $patient->description }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-2 text-xs text-[var(--ui-muted)]">
                            <span class="flex items-center gap-1">
                                @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                                {{ $patient->created_at->format('d.m.Y') }}
                            </span>
                            <span class="flex items-center gap-1">
                                @svg('heroicon-o-squares-2x2', 'w-3.5 h-3.5')
                                {{ $anamnesisBoards->count() + $kanbanBoards->count() + $progressBoards->count() }} Boards
                            </span>
                        </div>
                    </div>
                </div>
                @can('update', $patient)
                    <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-patient-settings', { patientId: {{ $patient->id }} })">
                        <span class="inline-flex items-center gap-1.5">
                            @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                            <span>Settings</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>
        </div>

        {{-- Boards Grid --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-[var(--ui-secondary)]">Patient Record</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                {{-- Anamnesis Board --}}
                @foreach($anamnesisBoards as $board)
                    <a href="{{ route('patients.anamnesis-boards.show', $board) }}" wire:navigate class="group block" title="Open Anamnesis (Ctrl+1)">
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md hover:border-blue-300 transition-all duration-200 p-5 h-full flex flex-col">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-blue-50">
                                    @svg('heroicon-o-document-text', 'w-5 h-5 text-blue-600')
                                </div>
                                <h4 class="text-base font-semibold text-[var(--ui-secondary)] group-hover:text-blue-600 transition-colors truncate">{{ $board->name }}</h4>
                            </div>
                            @if($board->description)
                                <p class="text-xs text-[var(--ui-muted)] line-clamp-2 mb-3">{{ $board->description }}</p>
                            @endif
                            <div class="mt-auto pt-3 border-t border-[var(--ui-border)]/30">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">
                                    @svg('heroicon-o-document-text', 'w-3 h-3')
                                    Anamnesis
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach

                {{-- Kanban Boards --}}
                @foreach($kanbanBoards as $index => $board)
                    @php
                        $kanbanColors = [
                            'Findings' => ['bg' => 'amber', 'icon' => 'heroicon-o-clipboard-document-check'],
                            'Therapy' => ['bg' => 'indigo', 'icon' => 'heroicon-o-heart'],
                            'Medication' => ['bg' => 'emerald', 'icon' => 'heroicon-o-beaker'],
                        ];
                        $color = $kanbanColors[$board->name]['bg'] ?? 'indigo';
                        $icon = $kanbanColors[$board->name]['icon'] ?? 'heroicon-o-view-columns';
                        $shortcutNum = $index + 2;
                    @endphp
                    <a href="{{ route('patients.kanban-boards.show', $board) }}" wire:navigate class="group block" title="Open {{ $board->name }} (Ctrl+{{ $shortcutNum }})">
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md hover:border-{{ $color }}-300 transition-all duration-200 p-5 h-full flex flex-col">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-{{ $color }}-50">
                                    @svg($icon, 'w-5 h-5 text-' . $color . '-600')
                                </div>
                                <h4 class="text-base font-semibold text-[var(--ui-secondary)] group-hover:text-{{ $color }}-600 transition-colors truncate">{{ $board->name }}</h4>
                            </div>
                            @if($board->description)
                                <p class="text-xs text-[var(--ui-muted)] line-clamp-2 mb-3">{{ $board->description }}</p>
                            @endif
                            <div class="mt-auto pt-3 border-t border-[var(--ui-border)]/30">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-{{ $color }}-50 text-{{ $color }}-700 text-xs font-medium">
                                    @svg($icon, 'w-3 h-3')
                                    {{ $board->name }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach

                {{-- Progress Board --}}
                @foreach($progressBoards as $board)
                    <a href="{{ route('patients.progress-boards.show', $board) }}" wire:navigate class="group block" title="Open Progress (Ctrl+5)">
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-md hover:border-purple-300 transition-all duration-200 p-5 h-full flex flex-col">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-purple-50">
                                    @svg('heroicon-o-clock', 'w-5 h-5 text-purple-600')
                                </div>
                                <h4 class="text-base font-semibold text-[var(--ui-secondary)] group-hover:text-purple-600 transition-colors truncate">{{ $board->name }}</h4>
                            </div>
                            @if($board->description)
                                <p class="text-xs text-[var(--ui-muted)] line-clamp-2 mb-3">{{ $board->description }}</p>
                            @endif
                            <div class="mt-auto pt-3 border-t border-[var(--ui-border)]/30">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 text-xs font-medium">
                                    @svg('heroicon-o-clock', 'w-3 h-3')
                                    Progress
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Patient" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-5">
                {{-- Board Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Boards</h3>
                    <nav class="space-y-1">
                        @foreach($allBoards as $index => $board)
                            <a
                                href="{{ $board['route'] }}"
                                wire:navigate
                                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-[var(--ui-secondary)] hover:bg-{{ $board['color'] }}-50 hover:text-{{ $board['color'] }}-700 transition-all duration-150 group"
                            >
                                <div class="flex items-center justify-center w-7 h-7 rounded-md bg-{{ $board['color'] }}-50 group-hover:bg-{{ $board['color'] }}-100 transition-colors">
                                    @svg($board['icon'], 'w-4 h-4 text-' . $board['color'] . '-600')
                                </div>
                                <span class="flex-1 truncate">{{ $board['name'] }}</span>
                                <kbd class="hidden group-hover:inline-block px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded text-[9px] font-mono text-[var(--ui-muted)]">{{ $index + 1 }}</kbd>
                            </a>
                        @endforeach
                    </nav>
                </div>

                {{-- Patient Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Details</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                            <span class="text-[var(--ui-muted)]">Created</span>
                            <span class="text-[var(--ui-secondary)] font-medium">{{ $patient->created_at->format('d.m.Y') }}</span>
                        </div>
                        @if($patient->done)
                            <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-success-5)] border border-[var(--ui-success)]/20 rounded-lg text-sm">
                                <span class="text-[var(--ui-muted)]">Status</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded bg-[var(--ui-success-5)] text-[var(--ui-success)]">Done</span>
                            </div>
                        @endif
                        @if($patient->getCompany())
                            @php
                                $company = $patient->getCompany();
                                $companyResolver = app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class);
                            @endphp
                            <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                                <span class="text-[var(--ui-muted)]">Company</span>
                                <a href="{{ $companyResolver->url($company->id) }}" class="text-[var(--ui-primary)] font-medium hover:underline truncate max-w-[120px]">
                                    {{ $companyResolver->displayName($company->id) }}
                                </a>
                            </div>
                        @endif
                        @if($patient->getContact())
                            @php
                                $contact = $patient->getContact();
                                $contactResolver = app(\Platform\Core\Contracts\CrmContactResolverInterface::class);
                            @endphp
                            <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                                <span class="text-[var(--ui-muted)]">Contact</span>
                                <a href="{{ $contactResolver->url($contact->id) }}" class="text-[var(--ui-primary)] font-medium hover:underline truncate max-w-[120px]">
                                    {{ $contactResolver->displayName($contact->id) }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Actions</h3>
                    <div class="flex flex-col gap-1.5">
                        @can('update', $patient)
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-patient-settings', { patientId: {{ $patient->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                    <span>Settings</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Keyboard Shortcuts --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Shortcuts</h3>
                    <div class="text-[10px] text-[var(--ui-muted)] space-y-1">
                        <div class="flex justify-between items-center">
                            <span>Board 1-5</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded font-mono">Ctrl+1-5</kbd>
                        </div>
                        <div class="flex justify-between items-center">
                            <span>Next patient</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded font-mono">Alt+Down</kbd>
                        </div>
                        <div class="flex justify-between items-center">
                            <span>Prev patient</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded font-mono">Alt+Up</kbd>
                        </div>
                        <div class="flex justify-between items-center">
                            <span>Back to list</span>
                            <kbd class="px-1 py-0.5 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded font-mono">Esc</kbd>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Activities" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Recent Activities</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)] hover:bg-[var(--ui-muted)] transition-colors">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-[var(--ui-secondary)] leading-snug">
                                        {{ $activity['title'] ?? 'Activity' }}
                                    </div>
                                </div>
                                @if(($activity['type'] ?? null) === 'system')
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-xs text-[var(--ui-muted)]">
                                            @svg('heroicon-o-cog', 'w-3 h-3')
                                            System
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                @svg('heroicon-o-clock', 'w-3 h-3')
                                <span>{{ $activity['time'] ?? '' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">No activities yet</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Changes will be displayed here</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <livewire:patients.patient-settings-modal/>
</x-ui-page>

@push('scripts')
<script>
document.addEventListener('keydown', function(e) {
    // Board navigation: Ctrl+1 through Ctrl+5
    if (e.ctrlKey && !e.shiftKey && !e.altKey && !e.metaKey) {
        const boardLinks = @json($allBoards->pluck('route')->values());
        const num = parseInt(e.key);
        if (num >= 1 && num <= boardLinks.length) {
            e.preventDefault();
            Livewire.navigate(boardLinks[num - 1]);
        }
    }

    // Patient navigation: Alt+ArrowDown / Alt+ArrowUp
    if (e.altKey && !e.ctrlKey && !e.shiftKey && !e.metaKey) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            @this.navigateToNextPatient();
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            @this.navigateToPreviousPatient();
        }
    }

    // Escape: back to dashboard
    if (e.key === 'Escape' && !e.ctrlKey && !e.shiftKey && !e.altKey && !e.metaKey) {
        const active = document.activeElement;
        if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)) {
            return;
        }
        e.preventDefault();
        Livewire.navigate('{{ route('patients.dashboard') }}');
    }
});
</script>
@endpush
