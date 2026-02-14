<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$patient->name" icon="heroicon-o-clipboard-document-list" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Boards Section --}}
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-[var(--ui-secondary)]">Patient Record</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                {{-- Anamnesis Board --}}
                @foreach($anamnesisBoards as $board)
                    <a href="{{ route('patients.anamnesis-boards.show', $board) }}" class="group block">
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-lg hover:border-blue-200 transition-all duration-200 p-6 h-full flex flex-col">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50 mb-3">
                                    @svg('heroicon-o-document-text', 'w-5 h-5 text-blue-600')
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2 group-hover:text-blue-600 transition-colors truncate">{{ $board->name }}</h4>
                                @if($board->description)
                                    <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $board->description }}</p>
                                @endif
                            </div>
                            <div class="mt-auto pt-4 border-t border-[var(--ui-border)]/40">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">
                                    @svg('heroicon-o-document-text', 'w-3.5 h-3.5')
                                    Anamnesis
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach

                {{-- Kanban Boards (Findings, Therapy, Medication) --}}
                @foreach($kanbanBoards as $board)
                    @php
                        $kanbanColors = [
                            'Findings' => ['bg' => 'amber', 'icon' => 'heroicon-o-clipboard-document-check'],
                            'Therapy' => ['bg' => 'indigo', 'icon' => 'heroicon-o-heart'],
                            'Medication' => ['bg' => 'green', 'icon' => 'heroicon-o-beaker'],
                        ];
                        $color = $kanbanColors[$board->name]['bg'] ?? 'indigo';
                        $icon = $kanbanColors[$board->name]['icon'] ?? 'heroicon-o-view-columns';
                    @endphp
                    <a href="{{ route('patients.kanban-boards.show', $board) }}" class="group block">
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-lg hover:border-{{ $color }}-200 transition-all duration-200 p-6 h-full flex flex-col">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-{{ $color }}-50 mb-3">
                                    @svg($icon, 'w-5 h-5 text-' . $color . '-600')
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2 group-hover:text-{{ $color }}-600 transition-colors truncate">{{ $board->name }}</h4>
                                @if($board->description)
                                    <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $board->description }}</p>
                                @endif
                            </div>
                            <div class="mt-auto pt-4 border-t border-[var(--ui-border)]/40">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-{{ $color }}-50 text-{{ $color }}-700 text-xs font-medium">
                                    @svg($icon, 'w-3.5 h-3.5')
                                    {{ $board->name }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach

                {{-- Progress Board --}}
                @foreach($progressBoards as $board)
                    <a href="{{ route('patients.progress-boards.show', $board) }}" class="group block">
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm hover:shadow-lg hover:border-purple-200 transition-all duration-200 p-6 h-full flex flex-col">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-50 mb-3">
                                    @svg('heroicon-o-clock', 'w-5 h-5 text-purple-600')
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2 group-hover:text-purple-600 transition-colors truncate">{{ $board->name }}</h4>
                                @if($board->description)
                                    <p class="text-sm text-[var(--ui-muted)] line-clamp-2">{{ $board->description }}</p>
                                @endif
                            </div>
                            <div class="mt-auto pt-4 border-t border-[var(--ui-border)]/40">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 text-xs font-medium">
                                    @svg('heroicon-o-clock', 'w-3.5 h-3.5')
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
        <x-ui-page-sidebar title="Patient Overview" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Mini Dashboard --}}
                <div class="bg-gradient-to-br from-[var(--ui-primary-5)] to-[var(--ui-primary-10)] rounded-xl p-4 border border-[var(--ui-primary)]/20">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-primary)] mb-4">Dashboard</h3>

                    <div class="space-y-3">
                        {{-- Boards Statistics --}}
                        <div class="bg-white/80 backdrop-blur-sm rounded-lg p-3 border border-white/50">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-squares-2x2', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm font-semibold text-[var(--ui-secondary)]">Boards</span>
                                </div>
                                <span class="text-lg font-bold text-[var(--ui-primary)]">{{ $anamnesisBoards->count() + $kanbanBoards->count() + $progressBoards->count() }}</span>
                            </div>
                            <div class="grid grid-cols-5 gap-2 mt-2">
                                <div class="text-center">
                                    <div class="text-xs font-medium text-blue-600">{{ $anamnesisBoards->count() }}</div>
                                    <div class="text-[10px] text-[var(--ui-muted)]">Anamnesis</div>
                                </div>
                                @foreach($kanbanBoards as $kb)
                                    @php
                                        $kbColor = match($kb->name) {
                                            'Findings' => 'amber',
                                            'Therapy' => 'indigo',
                                            'Medication' => 'green',
                                            default => 'indigo',
                                        };
                                    @endphp
                                    <div class="text-center">
                                        <div class="text-xs font-medium text-{{ $kbColor }}-600">1</div>
                                        <div class="text-[10px] text-[var(--ui-muted)]">{{ $kb->name }}</div>
                                    </div>
                                @endforeach
                                <div class="text-center">
                                    <div class="text-xs font-medium text-purple-600">{{ $progressBoards->count() }}</div>
                                    <div class="text-[10px] text-[var(--ui-muted)]">Progress</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Actions</h3>
                    <div class="flex flex-col gap-2">
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

                {{-- Patient Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Created</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $patient->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        @if($patient->done)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Status</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded bg-[var(--ui-success-5)] text-[var(--ui-success)]">
                                    Done
                                </span>
                            </div>
                        @endif
                        @if($patient->getCompany())
                            @php
                                $company = $patient->getCompany();
                                $companyResolver = app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class);
                            @endphp
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Company</span>
                                <a href="{{ $companyResolver->url($company->id) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $companyResolver->displayName($company->id) }}
                                </a>
                            </div>
                        @endif
                        @if($patient->getContact())
                            @php
                                $contact = $patient->getContact();
                                $contactResolver = app(\Platform\Core\Contracts\CrmContactResolverInterface::class);
                            @endphp
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Contact Person</span>
                                <a href="{{ $contactResolver->url($contact->id) }}" class="text-sm text-[var(--ui-primary)] font-medium hover:underline">
                                    {{ $contactResolver->displayName($contact->id) }}
                                </a>
                            </div>
                        @endif
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
