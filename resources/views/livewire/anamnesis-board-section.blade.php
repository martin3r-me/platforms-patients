<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$section->name" icon="heroicon-o-squares-2x2">
            <x-slot name="actions">
                <a href="{{ route('patients.anamnesis-boards.show', $section->anamnesisBoard) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Back to Anamnesis Board</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $section->name }}</h1>
                @if($section->description)
                    <p class="text-[var(--ui-secondary)]">{{ $section->description }}</p>
                @endif
            </div>
        </div>

        {{-- Rows Section --}}
        <div class="space-y-6">
            @foreach($section->rows as $row)
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-[var(--ui-border)]/40 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $row->name }}</h3>
                            @if($row->description)
                                <span class="text-sm text-[var(--ui-muted)]">{{ $row->description }}</span>
                            @endif
                        </div>
                        @can('update', $section->anamnesisBoard)
                            @php
                                $totalSpan = $row->blocks->sum('span');
                            @endphp
                            <x-ui-button 
                                variant="primary" 
                                size="sm" 
                                wire:click="createBlock({{ $row->id }})"
                                :disabled="$totalSpan >= 12"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    <span>Add Block</span>
                                </span>
                            </x-ui-button>
                        @endcan
                    </div>
                    
                    <div class="p-4">
                        @if($row->blocks->count() > 0)
                            <div class="grid grid-cols-12 gap-4">
                                @foreach($row->blocks as $block)
                                    <div 
                                        class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 p-4 hover:border-[var(--ui-primary)]/40 transition-colors"
                                        style="grid-column: span {{ $block->span }};"
                                    >
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex-1">
                                                <h4 class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $block->name }}</h4>
                                                @if($block->description)
                                                    <p class="text-xs text-[var(--ui-muted)] mt-1">{{ $block->description }}</p>
                                                @endif
                                            </div>
                                            <span class="text-xs text-[var(--ui-muted)] bg-white px-2 py-0.5 rounded">
                                                {{ $block->span }}/12
                                            </span>
                                        </div>
                                        <div class="text-xs text-[var(--ui-muted)]">
                                            Block
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 border-2 border-dashed border-[var(--ui-border)]/40 rounded-lg bg-[var(--ui-muted-5)]">
                                <p class="text-sm text-[var(--ui-muted)] mb-3">No blocks in this row yet</p>
                                @can('update', $section->anamnesisBoard)
                                    <x-ui-button 
                                        variant="primary" 
                                        size="sm" 
                                        wire:click="createBlock({{ $row->id }})"
                                    >
                                        <span class="inline-flex items-center gap-2">
                                            @svg('heroicon-o-plus', 'w-4 h-4')
                                            <span>Add First Block</span>
                                        </span>
                                    </x-ui-button>
                                @endcan
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Add new Row --}}
            @can('update', $section->anamnesisBoard)
                <div class="bg-white rounded-xl border-2 border-dashed border-[var(--ui-border)]/40 shadow-sm overflow-hidden">
                    <div class="p-6 text-center">
                        <x-ui-button variant="primary" size="sm" wire:click="createRow">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Add New Row</span>
                            </span>
                        </x-ui-button>
                    </div>
                </div>
            @endcan
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Section Overview" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('patients.anamnesis-boards.show', $section->anamnesisBoard) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Back to Anamnesis Board</span>
                        </a>
                    </div>
                </div>

                {{-- Section Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Rows</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $section->rows->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg">
                            <span class="text-sm text-[var(--ui-muted)]">Created</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $section->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
