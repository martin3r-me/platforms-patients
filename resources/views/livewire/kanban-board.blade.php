<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$kanbanBoard->name" icon="heroicon-o-view-columns">
            <x-slot name="actions">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[var(--ui-muted)]">{{ $patient->name }}</span>
                    <a href="{{ route('patients.patients.show', $patient) }}" wire:navigate class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-md hover:bg-[var(--ui-muted-5)]">
                        @svg('heroicon-o-arrow-left', 'w-4 h-4')
                        <span>Patient</span>
                    </a>
                </div>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board Overview" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-5">
                {{-- Board Navigation --}}
                @include('patients::livewire.partials.board-navigation', ['boardNavigation' => $boardNavigation, 'patient' => $patient])

                {{-- Actions --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Actions</h3>
                    <div class="flex flex-col gap-1.5">
                        @can('update', $kanbanBoard)
                            <x-ui-button variant="secondary" size="sm" wire:click="createSlot" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-square-2-stack','w-4 h-4')
                                    <span>New Slot</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-kanban-board-settings', { kanbanBoardId: {{ $kanbanBoard->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                    <span>Settings</span>
                                </span>
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('extrafields:open')" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-adjustments-horizontal','w-4 h-4')
                                    <span>Extra Fields</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                </div>

                {{-- Board Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Details</h3>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                            <span class="text-[var(--ui-muted)]">Type</span>
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-200">
                                Kanban
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                            <span class="text-[var(--ui-muted)]">Created</span>
                            <span class="text-[var(--ui-secondary)] font-medium">{{ $kanbanBoard->created_at->format('d.m.Y') }}</span>
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
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $activity['title'] ?? 'Activity' }}</div>
                            <div class="text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
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

    {{-- Board container --}}
    @if($slots->count() > 0)
        <div class="kanban-board-kanban-container">
            <x-ui-kanban-container sortable="updateSlotOrder" sortable-group="updateCardOrder">
                @foreach($slots as $slot)
                    <x-ui-kanban-column :title="$slot->name" :sortable-id="$slot->id" :scrollable="true">
                        <x-slot name="headerActions">
                            @can('update', $kanbanBoard)
                                <button
                                    wire:click="createCard({{ $slot->id }})"
                                    class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                                    title="New Card"
                                >
                                    @svg('heroicon-o-plus-circle', 'w-4 h-4')
                                </button>
                                <button
                                    @click="$dispatch('open-modal-kanban-board-slot-settings', { slotId: {{ $slot->id }} })"
                                    class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                                    title="Settings"
                                >
                                    @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                </button>
                            @endcan
                        </x-slot>

                        @foreach($slot->cards as $card)
                            @include('patients::livewire.kanban-card-preview-card', ['card' => $card])
                        @endforeach
                    </x-ui-kanban-column>
                @endforeach
            </x-ui-kanban-container>
        </div>
    @else
        <div class="flex items-center justify-center h-full">
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center max-w-md">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 mb-4">
                    @svg('heroicon-o-view-columns', 'w-8 h-8 text-indigo-600')
                </div>
                <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">No Slots yet</h3>
                <p class="text-sm text-[var(--ui-muted)] mb-4">Create your first slot for this Kanban Board.</p>
                @can('update', $kanbanBoard)
                    <x-ui-button variant="primary" size="sm" wire:click="createSlot">
                        <span class="inline-flex items-center gap-2">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Create Slot</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>
        </div>
    @endif

    <livewire:patients.kanban-board-settings-modal/>
    <livewire:patients.kanban-board-slot-settings-modal/>
</x-ui-page>

@push('styles')
<style>
    .kanban-board-kanban-container .absolute.bottom-6 {
        display: none !important;
    }
</style>
@endpush
