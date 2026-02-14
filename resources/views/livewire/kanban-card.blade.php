<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$title ?: $card->title" icon="heroicon-o-document-text">
            <div class="mt-1 text-sm text-[var(--ui-muted)] flex items-center gap-2">
                <a href="{{ route('patients.patients.show', $card->kanbanBoard->patient) }}" class="text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] flex items-center gap-1">
                    @svg('heroicon-o-tag', 'w-4 h-4')
                    {{ $card->kanbanBoard->patient->name }}
                </a>
                <span>›</span>
                <a href="{{ route('patients.kanban-boards.show', $card->kanbanBoard) }}" class="text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] flex items-center gap-1">
                    @svg('heroicon-o-view-columns', 'w-4 h-4')
                    {{ $card->kanbanBoard->name }}
                </a>
                @if($card->slot)
                    <span>›</span>
                    <span class="flex items-center gap-1">
                        @svg('heroicon-o-view-columns', 'w-4 h-4')
                        {{ $card->slot->name }}
                    </span>
                @endif
            </div>
            <x-slot name="actions">
                <a href="{{ route('patients.kanban-boards.show', $card->kanbanBoard) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Back to Board</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container class="max-w-4xl mx-auto">
        @can('update', $card)
            <div
                x-data="{
                    savedLabel: '—',
                    isSaving: false,
                    init() {
                        if (window.Livewire) {
                            Livewire.on('patients-kanban-saved', (payload) => {
                                if (!payload || payload.cardId !== {{ (int) $card->id }}) return;
                                this.savedLabel = 'Saved';
                                this.isSaving = false;
                            });
                        }
                    },
                    saveNow() {
                        this.isSaving = true;
                        $wire.save();
                    },
                }"
                class="min-h-[calc(100vh-220px)]"
            >
                {{-- Title + tiny status --}}
                <div class="flex items-start justify-between gap-4 mb-6">
                    <input
                        type="text"
                        wire:model.live="title"
                        placeholder="Title..."
                        class="w-full text-4xl font-bold bg-transparent border-0 focus:ring-0 focus:outline-none text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)]"
                        style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;"
                    />

                    <div class="flex items-center gap-3 flex-shrink-0 pt-2">
                        <div class="text-xs text-[var(--ui-muted)]">
                            <span x-text="savedLabel"></span>
                        </div>
                        <button
                            type="button"
                            @click="saveNow()"
                            class="px-3 py-1.5 text-sm rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors"
                        >
                            Save
                        </button>
                    </div>
                </div>

                {{-- Description --}}
                <div class="mb-6">
                    <x-ui-input-textarea
                        name="description"
                        label="Description"
                        wire:model.defer="description"
                        placeholder="Description for this card..."
                        :errorKey="'description'"
                    />
                </div>

                <x-core-extra-fields-section
                    :definitions="$this->extraFieldDefinitions"
                    class="mt-8"
                />
            </div>
        @else
            {{-- Read-only View --}}
            <div class="space-y-6">
                <div>
                    <h1 class="text-4xl font-bold text-[var(--ui-secondary)] mb-4">{{ $card->title }}</h1>

                    @if($card->description)
                        <div class="mb-6 p-4 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <p class="text-sm text-[var(--ui-muted)]">{{ $card->description }}</p>
                        </div>
                    @else
                        <div class="text-center py-12 text-[var(--ui-muted)]">
                            <p>No description yet</p>
                        </div>
                    @endif
                </div>
            </div>
        @endcan
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Card Overview" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('patients.kanban-boards.show', $card->kanbanBoard) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Back to Board</span>
                        </a>
                    </div>
                </div>

                {{-- Card Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        @if($card->slot)
                            <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <span class="text-sm text-[var(--ui-muted)]">Slot</span>
                                <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                    {{ $card->slot->name }}
                                </span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <span class="text-sm text-[var(--ui-muted)]">Created</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $card->created_at->format('d.m.Y') }}
                            </span>
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
</x-ui-page>
