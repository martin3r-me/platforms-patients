<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$anamnesisBoard->name" icon="heroicon-o-document-text">
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

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50">
                        @svg('heroicon-o-document-text', 'w-5 h-5 text-blue-600')
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-[var(--ui-secondary)] leading-tight">{{ $anamnesisBoard->name }}</h1>
                        @if($anamnesisBoard->description)
                            <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $anamnesisBoard->description }}</p>
                        @endif
                        <div class="flex items-center gap-2 mt-1.5 text-xs text-[var(--ui-muted)]">
                            <span>{{ $patient->name }}</span>
                            <span>&middot;</span>
                            <span>{{ $anamnesisBoard->sections->count() }} {{ Str::plural('Section', $anamnesisBoard->sections->count()) }}</span>
                        </div>
                    </div>
                </div>
                @can('update', $anamnesisBoard)
                    <x-ui-button variant="primary" size="sm" wire:click="createSection">
                        <span class="inline-flex items-center gap-1.5">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Section</span>
                        </span>
                    </x-ui-button>
                @endcan
            </div>
        </div>

        {{-- Sections --}}
        <div>
            @if($anamnesisBoard->sections->count() > 0)
                <div class="space-y-6">
                    @foreach($anamnesisBoard->sections as $section)
                        {{-- Section (full width) --}}
                        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
                            <div class="p-4 border-b border-[var(--ui-border)]/40 flex items-center justify-between">
                                <div class="flex-1">
                                    @can('update', $anamnesisBoard)
                                        <input 
                                            type="text"
                                            value="{{ $section->name }}"
                                            wire:blur="updateSectionName({{ $section->id }}, $event.target.value)"
                                            class="text-lg font-semibold text-[var(--ui-secondary)] bg-transparent border-none p-0 focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] rounded px-1 -ml-1 w-full"
                                        />
                                    @else
                                        <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">{{ $section->name }}</h3>
                                    @endcan
                                    @if($section->description)
                                        <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $section->description }}</p>
                                    @endif
                                </div>
                                @can('update', $anamnesisBoard)
                                    <x-ui-button 
                                        variant="danger-outline" 
                                        size="xs" 
                                        wire:click="deleteSection({{ $section->id }})"
                                        wire:confirm="Do you really want to delete this section? All rows and blocks will also be deleted."
                                    >
                                        <span class="inline-flex items-center gap-1">
                                            @svg('heroicon-o-trash', 'w-3 h-3')
                                            <span>Delete</span>
                                        </span>
                                    </x-ui-button>
                                @endcan
                            </div>
                            
                            {{-- Rows within the Section --}}
                            <div class="p-4 space-y-4">
                                @foreach($section->rows as $row)
                                    <div class="bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 overflow-hidden">
                                        <div class="p-3 border-b border-[var(--ui-border)]/40 flex items-center justify-between">
                                            <div class="flex items-center gap-3 flex-1 flex-wrap">
                                                @can('update', $anamnesisBoard)
                                                    <input 
                                                        type="text"
                                                        value="{{ $row->name }}"
                                                        wire:blur="updateRowName({{ $row->id }}, $event.target.value)"
                                                        class="text-sm font-semibold text-[var(--ui-secondary)] bg-transparent border-none p-0 focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] rounded px-1 -ml-1"
                                                    />
                                                @else
                                                    <h4 class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $row->name }}</h4>
                                                @endcan
                                                @if($row->description)
                                                    <span class="text-xs text-[var(--ui-muted)]">{{ $row->description }}</span>
                                                @endif
                                                @php
                                                    $totalSpan = $row->blocks->sum('span');
                                                @endphp
                                                <span class="text-[10px] px-1.5 py-0.5 rounded {{ $totalSpan > 12 ? 'bg-red-100 text-red-700' : ($totalSpan == 12 ? 'bg-green-100 text-green-700' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)]') }}">
                                                    {{ $totalSpan }}/12
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @can('update', $anamnesisBoard)
                                                    @php
                                                        $totalSpan = $row->blocks->sum('span');
                                                    @endphp
                                                    <x-ui-button 
                                                        variant="primary" 
                                                        size="xs" 
                                                        wire:click="createBlock({{ $row->id }})"
                                                        :disabled="$totalSpan >= 12"
                                                    >
                                                        <span class="inline-flex items-center gap-1">
                                                            @svg('heroicon-o-plus', 'w-3 h-3')
                                                            <span>Block</span>
                                                        </span>
                                                    </x-ui-button>
                                                    <x-ui-button 
                                                        variant="danger-outline" 
                                                        size="xs" 
                                                        wire:click="deleteRow({{ $row->id }})"
                                                        wire:confirm="Do you really want to delete this row? All blocks will also be deleted."
                                                    >
                                                        <span class="inline-flex items-center gap-1">
                                                            @svg('heroicon-o-trash', 'w-3 h-3')
                                                        </span>
                                                    </x-ui-button>
                                                @endcan
                                            </div>
                                        </div>
                                        
                                        <div class="p-3">
                                            @if($row->blocks->count() > 0)
                                                <div class="grid grid-cols-12 gap-2">
                                                    @foreach($row->blocks as $block)
                                                        @php
                                                            $block->load('content');
                                                            $hasContent = $block->content_type && $block->content;
                                                        @endphp
                                                        <div 
                                                            class="group bg-white rounded-lg border border-[var(--ui-border)]/40 hover:border-[var(--ui-primary)]/60 hover:shadow-sm transition-all relative {{ $hasContent ? 'cursor-pointer' : '' }}"
                                                            style="grid-column: span {{ $block->span }};"
                                                            @if($hasContent)
                                                                @click="window.location.href = '{{ route('patients.anamnesis-board-blocks.show', ['patientsAnamnesisBoardBlock' => $block->id, 'type' => $block->content_type]) }}'"
                                                            @elseif(auth()->user()->can('update', $anamnesisBoard))
                                                                x-data
                                                                @click="$dispatch('open-modal-anamnesis-board-block-settings', { blockId: {{ $block->id }} })"
                                                            @endif
                                                        >
                                                            {{-- Block Content --}}
                                                            <div class="p-4">
                                                                <div class="flex items-start justify-between gap-3">
                                                                    <div class="flex-1 min-w-0">
                                                                        <h5 class="text-sm font-medium text-[var(--ui-secondary)] leading-tight">
                                                                            {{ $block->name }}
                                                                        </h5>
                                                                        @if($block->description)
                                                                            <p class="text-xs text-[var(--ui-muted)] mt-1.5 leading-relaxed">
                                                                                {{ $block->description }}
                                                                            </p>
                                                                        @endif
                                                                        
                                                                        {{-- Content output by type --}}
                                                                        @if($hasContent)
                                                                            @if($block->content_type === 'text' && $block->content)
                                                                                <div class="mt-3 text-sm text-[var(--ui-secondary)] markdown-content-preview">
                                                                                    <div class="line-clamp-3">
                                                                                        {!! \Illuminate\Support\Str::markdown($block->content->content ?? '') !!}
                                                                                    </div>
                                                                                </div>
                                                                            @endif
                                                                        @else
                                                                            <p class="text-xs text-[var(--ui-muted)] italic mt-2">No content yet</p>
                                                                        @endif
                                                                        
                                                                    </div>
                                                                    @can('update', $anamnesisBoard)
                                                                        <button 
                                                                            type="button"
                                                                            @click.stop="$dispatch('open-modal-anamnesis-board-block-settings', { blockId: {{ $block->id }} })"
                                                                            class="opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 p-1.5 rounded-md hover:bg-[var(--ui-muted-5)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]"
                                                                            title="Block Settings"
                                                                        >
                                                                            @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                                                                        </button>
                                                                    @endcan
                                                                </div>
                                                            </div>
                                                            
                                                            {{-- Edit icon bottom right, only when content exists --}}
                                                            @if($hasContent)
                                                                <a 
                                                                    href="{{ route('patients.anamnesis-board-blocks.show', ['patientsAnamnesisBoardBlock' => $block->id, 'type' => $block->content_type]) }}"
                                                                    @click.stop
                                                                    class="absolute bottom-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center w-8 h-8 rounded-md bg-[var(--ui-primary)] text-white hover:bg-[var(--ui-primary)]/90 shadow-sm"
                                                                    title="Edit Block"
                                                                >
                                                                    @svg('heroicon-o-pencil', 'w-4 h-4')
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-center py-4 border-2 border-dashed border-[var(--ui-border)]/40 rounded-lg">
                                                    <p class="text-xs text-[var(--ui-muted)] mb-2">No blocks yet</p>
                                                    @can('update', $anamnesisBoard)
                                                        <x-ui-button 
                                                            variant="primary" 
                                                            size="xs" 
                                                            wire:click="createBlock({{ $row->id }})"
                                                        >
                                                            <span class="inline-flex items-center gap-1">
                                                                @svg('heroicon-o-plus', 'w-3 h-3')
                                                                <span>Add Block</span>
                                                            </span>
                                                        </x-ui-button>
                                                    @endcan
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Add new Row --}}
                                @can('update', $anamnesisBoard)
                                    <div class="border-2 border-dashed border-[var(--ui-border)]/40 rounded-lg p-3 text-center">
                                        <x-ui-button variant="secondary-outline" size="sm" wire:click="createRow({{ $section->id }})">
                                            <span class="inline-flex items-center gap-2">
                                                @svg('heroicon-o-plus', 'w-4 h-4')
                                                <span>Add Row</span>
                                            </span>
                                        </x-ui-button>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-[var(--ui-muted-5)] mb-4">
                        @svg('heroicon-o-squares-2x2', 'w-8 h-8 text-[var(--ui-muted)]')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">No Sections yet</h3>
                    <p class="text-sm text-[var(--ui-muted)] mb-4">Create your first section for this Anamnesis Board.</p>
                    @can('update', $anamnesisBoard)
                        <x-ui-button variant="primary" size="sm" wire:click="createSection">
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                <span>Create Section</span>
                            </span>
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Board Overview" width="w-72" :defaultOpen="true">
            <div class="p-4 space-y-5">
                {{-- Board Navigation --}}
                @include('patients::livewire.partials.board-navigation', ['boardNavigation' => $boardNavigation, 'patient' => $patient])

                {{-- Actions --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-2">Actions</h3>
                    <div class="flex flex-col gap-1.5">
                        @can('update', $anamnesisBoard)
                            <x-ui-button variant="secondary-outline" size="sm" x-data @click="$dispatch('open-modal-anamnesis-board-settings', { anamnesisBoardId: {{ $anamnesisBoard->id }} })" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-cog-6-tooth','w-4 h-4')
                                    <span>Settings</span>
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
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200">
                                Anamnesis
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-1.5 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded-lg text-sm">
                            <span class="text-[var(--ui-muted)]">Created</span>
                            <span class="text-[var(--ui-secondary)] font-medium">{{ $anamnesisBoard->created_at->format('d.m.Y') }}</span>
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

    <livewire:patients.anamnesis-board-settings-modal/>
    <livewire:patients.anamnesis-board-block-settings-modal/>
</x-ui-page>

@push('styles')
<style>
    /* Markdown Content Preview Styling for Anamnesis Board Blocks */
    .markdown-content-preview {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        line-height: 1.6;
        color: var(--ui-secondary);
    }
    
    .markdown-content-preview h1,
    .markdown-content-preview h2,
    .markdown-content-preview h3,
    .markdown-content-preview h4 {
        font-weight: 600;
        margin-top: 0.5em;
        margin-bottom: 0.25em;
        line-height: 1.3;
    }
    
    .markdown-content-preview h1 {
        font-size: 1.5em;
    }
    
    .markdown-content-preview h2 {
        font-size: 1.25em;
    }
    
    .markdown-content-preview h3 {
        font-size: 1.1em;
    }
    
    .markdown-content-preview h4 {
        font-size: 1em;
    }
    
    .markdown-content-preview p {
        margin-bottom: 0.5em;
    }
    
    .markdown-content-preview ul,
    .markdown-content-preview ol {
        margin-bottom: 0.5em;
        padding-left: 1.25em;
    }
    
    .markdown-content-preview li {
        margin-bottom: 0.25em;
    }
    
    .markdown-content-preview code {
        background: var(--ui-muted-5);
        padding: 0.15em 0.3em;
        border-radius: 3px;
        font-size: 0.9em;
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    }
    
    .markdown-content-preview pre {
        background: var(--ui-muted-5);
        padding: 0.75em;
        border-radius: 6px;
        overflow-x: auto;
        margin-bottom: 0.5em;
    }
    
    .markdown-content-preview pre code {
        background: transparent;
        padding: 0;
    }
    
    .markdown-content-preview blockquote {
        border-left: 2px solid var(--ui-primary);
        padding-left: 0.75em;
        margin-left: 0;
        color: var(--ui-muted);
        font-style: italic;
    }
    
    .markdown-content-preview strong {
        font-weight: 600;
    }
    
    .markdown-content-preview em {
        font-style: italic;
    }
    
    .markdown-content-preview a {
        color: var(--ui-primary);
        text-decoration: underline;
    }
    
    .markdown-content-preview a:hover {
        color: var(--ui-primary);
        text-decoration: none;
    }
    
    /* Line clamp for Preview */
    .markdown-content-preview .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
