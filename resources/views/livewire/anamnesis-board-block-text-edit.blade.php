<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$block->name" icon="heroicon-o-document-text">
            <x-slot name="actions">
                <a href="{{ route('patients.anamnesis-boards.show', $anamnesisBoard) }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors">
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    <span>Back to Anamnesis Board</span>
                </a>
            </x-slot>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Block-Details" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-6">
                {{-- Navigation --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="flex flex-col gap-2">
                        <a href="{{ route('patients.anamnesis-boards.show', $anamnesisBoard) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            <span>Back to Anamnesis Board</span>
                        </a>
                    </div>
                </div>

                {{-- Current Position --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Current Position</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <span class="text-sm text-[var(--ui-muted)]">Section</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $block->row->section->name }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <span class="text-sm text-[var(--ui-muted)]">Row</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                Row #{{ $block->row->order }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <span class="text-sm text-[var(--ui-muted)]">Span</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $block->span }}/12
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <span class="text-sm text-[var(--ui-muted)]">Type</span>
                            <span class="text-xs font-medium px-2 py-1 rounded-full bg-[var(--ui-primary-10)] text-[var(--ui-primary)] border border-[var(--ui-primary)]/20">
                                Text
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Navigation to other blocks --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Other Text Blocks</h3>
                    <div class="flex flex-col gap-2">
                        @if($previousBlock)
                            <a href="{{ route('patients.anamnesis-board-blocks.show', ['patientsAnamnesisBoardBlock' => $previousBlock->id, 'type' => 'text']) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                                @svg('heroicon-o-arrow-up', 'w-4 h-4')
                                <span class="truncate">{{ $previousBlock->name }}</span>
                            </a>
                        @endif
                        @if($nextBlock)
                            <a href="{{ route('patients.anamnesis-board-blocks.show', ['patientsAnamnesisBoardBlock' => $nextBlock->id, 'type' => 'text']) }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-[var(--ui-secondary)] hover:text-[var(--ui-primary)] transition-colors rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]">
                                @svg('heroicon-o-arrow-down', 'w-4 h-4')
                                <span class="truncate">{{ $nextBlock->name }}</span>
                            </a>
                        @endif
                    </div>
                    @if($allBlocks->count() > 0)
                        <div class="mt-3 space-y-1 max-h-64 overflow-y-auto">
                            @foreach($allBlocks as $otherBlock)
                                <a href="{{ route('patients.anamnesis-board-blocks.show', ['patientsAnamnesisBoardBlock' => $otherBlock->id, 'type' => 'text']) }}" class="block px-3 py-2 text-sm text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] rounded transition-colors {{ $otherBlock->id === $block->id ? 'bg-[var(--ui-primary-10)] font-medium' : '' }}">
                                    <div class="truncate">{{ $otherBlock->name }}</div>
                                    <div class="text-xs text-[var(--ui-muted)] mt-0.5">{{ $otherBlock->row->section->name }} · Row #{{ $otherBlock->row->order }}</div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Dummy Text Generator --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Dummy Text Generator</h3>
                    <div class="flex flex-col gap-2">
                        <button
                            type="button"
                            wire:click="generateDummyText(50)"
                            class="w-full px-3 py-2 text-sm font-medium rounded-md border border-[var(--ui-border)]/40 bg-white hover:bg-[var(--ui-muted-5)] text-[var(--ui-secondary)] transition-colors text-left"
                        >
                            50 Words
                        </button>
                        <button
                            type="button"
                            wire:click="generateDummyText(100)"
                            class="w-full px-3 py-2 text-sm font-medium rounded-md border border-[var(--ui-border)]/40 bg-white hover:bg-[var(--ui-muted-5)] text-[var(--ui-secondary)] transition-colors text-left"
                        >
                            100 Words
                        </button>
                        <button
                            type="button"
                            wire:click="generateDummyText(300)"
                            class="w-full px-3 py-2 text-sm font-medium rounded-md border border-[var(--ui-border)]/40 bg-white hover:bg-[var(--ui-muted-5)] text-[var(--ui-secondary)] transition-colors text-left"
                        >
                            300 Words
                        </button>
                    </div>
                </div>

                {{-- Block Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded">
                            <span class="text-sm text-[var(--ui-muted)]">Created</span>
                            <span class="text-sm text-[var(--ui-secondary)] font-medium">
                                {{ $block->created_at->format('d.m.Y') }}
                            </span>
                        </div>
                        <div 
                            x-data="{ copied: false }"
                            class="flex justify-between items-center py-2 px-3 bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 rounded"
                        >
                            <span class="text-sm text-[var(--ui-muted)]">UUID</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono text-[var(--ui-secondary)]">{{ $block->uuid }}</span>
                                <button
                                    type="button"
                                    @click="
                                        navigator.clipboard.writeText('{{ $block->uuid }}');
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    class="p-1 rounded hover:bg-white transition-colors"
                                    title="Copy UUID"
                                >
                                    <span x-show="!copied">
                                        @svg('heroicon-o-clipboard', 'w-3.5 h-3.5 text-[var(--ui-muted)] hover:text-[var(--ui-primary)]')
                                    </span>
                                    <span x-show="copied" x-cloak>
                                        @svg('heroicon-o-check', 'w-3.5 h-3.5 text-green-600')
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container class="max-w-4xl mx-auto">
        @can('update', $anamnesisBoard)
            {{-- Bear/Obsidian-like Editor --}}
            <div
                x-data="{
                    editor: null,
                    isSaving: false,
                    savedLabel: '—',
                    debounceTimer: null,
                    boot() {
                        const Editor = window.ToastUIEditor;
                        if (!Editor) return false;

                        if (this.editor && typeof this.editor.destroy === 'function') {
                            this.editor.destroy();
                        }

                        this.editor = new Editor({
                            el: this.$refs.editorEl,
                            height: '70vh',
                            initialEditType: 'wysiwyg',
                            previewStyle: 'tab',
                            hideModeSwitch: true,
                            usageStatistics: false,
                            placeholder: 'Start writing... / Headings, Lists, Checklists, Links, Code',
                            toolbarItems: [
                                ['heading', 'bold', 'italic', 'strike'],
                                ['ul', 'ol', 'task', 'quote'],
                                ['link', 'code', 'codeblock', 'hr'],
                            ],
                            initialValue: @js($content ?? ''),
                        });

                        // Sync Editor -> Livewire state (debounced, without DB-write)
                        this.editor.on('change', () => {
                            clearTimeout(this.debounceTimer);
                            this.debounceTimer = setTimeout(() => {
                                const md = this.editor.getMarkdown();
                                $wire.set('content', md, false);
                                this.savedLabel = 'Unsaved';
                            }, 900);
                        });

                        // Ctrl/Cmd + S (only once globally; replace on navigation)
                        if (window.__contentBlockKeydownHandler) {
                            window.removeEventListener('keydown', window.__contentBlockKeydownHandler);
                        }
                        window.__contentBlockKeydownHandler = (e) => {
                            const isSave = (e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 's';
                            if (!isSave) return;
                            e.preventDefault();
                            this.saveNow();
                        };
                        window.addEventListener('keydown', window.__contentBlockKeydownHandler);

                        // Livewire events (wire:ignore)
                        const bindLivewire = () => {
                            if (!window.Livewire) return;
                            Livewire.on('content-block-sync-editor', (payload) => {
                                if (!payload || payload.blockId !== {{ (int) $block->id }}) return;
                                if (typeof payload.name === 'string') {
                                    $wire.set('name', payload.name, false);
                                }
                                if (typeof payload.content === 'string' && this.editor) {
                                    this.editor.setMarkdown(payload.content);
                                }
                                this.savedLabel = '—';
                            });

                            Livewire.on('content-block-saved', (payload) => {
                                if (!payload || payload.blockId !== {{ (int) $block->id }}) return;
                                this.savedLabel = 'Saved';
                                this.isSaving = false;
                            });

                            Livewire.on('content-block-insert-text', (payload) => {
                                if (!payload || payload.blockId !== {{ (int) $block->id }}) return;
                                if (this.editor) {
                                    // Use fullContent if available, otherwise add text
                                    let newContent;
                                    if (payload.fullContent) {
                                        newContent = payload.fullContent;
                                    } else if (typeof payload.text === 'string') {
                                        const currentContent = this.editor.getMarkdown();
                                        const separator = currentContent && currentContent.trim() ? '\n\n' : '';
                                        newContent = currentContent + separator + payload.text;
                                    } else {
                                        return;
                                    }
                                    
                                    this.editor.setMarkdown(newContent);
                                    // Ensure Livewire has the content immediately (without false)
                                    $wire.set('content', newContent);
                                    this.savedLabel = 'Unsaved';
                                }
                            });
                        };

                        if (window.Livewire) {
                            bindLivewire();
                        } else {
                            document.addEventListener('livewire:init', bindLivewire, { once: true });
                        }

                        return true;
                    },
                    init() {
                        if (!this.boot()) {
                            window.addEventListener('toastui:ready', () => this.boot(), { once: true });
                        }
                    },
                    saveNow() {
                        if (!this.editor) return;
                        this.isSaving = true;
                        const md = this.editor.getMarkdown();
                        // Explicitly set content and then save
                        $wire.set('content', md);
                        // Wait briefly so Livewire processes the content
                        setTimeout(() => {
                            $wire.save();
                        }, 50);
                    },
                }"
                class="min-h-[calc(100vh-220px)]"
            >
                {{-- Title + tiny status --}}
                <div class="mb-6">
                    <div class="flex items-start justify-between gap-4 mb-2">
                        <input
                            type="text"
                            wire:model="name"
                            placeholder="Block Name…"
                            class="w-full text-4xl font-bold bg-transparent border-0 focus:ring-0 focus:outline-none text-[var(--ui-secondary)] placeholder:text-[var(--ui-muted)]"
                            style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;"
                        />

                        <div class="flex items-center gap-3 flex-shrink-0 pt-2">
                            <div class="text-xs text-[var(--ui-muted)]">
                                <span x-text="savedLabel"></span>
                                <span class="mx-1">·</span>
                                <span>⌘S</span>
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
                    <p class="text-xs text-[var(--ui-muted)] italic">
                        This title is for orientation only and will not be included.
                    </p>
                </div>

                <div class="content-block-editor-shell">
                    <div wire:ignore x-ref="editorEl"></div>
                </div>
            </div>
        @else
            {{-- Read-only View --}}
            <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm p-8">
                <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4">{{ $block->name }}</h1>
                @if($block->description)
                    <p class="text-[var(--ui-secondary)] mb-6">{{ $block->description }}</p>
                @endif
                <div class="markdown-content">
                    {!! \Illuminate\Support\Str::markdown($content ?? '') !!}
                </div>
            </div>
        @endcan
    </x-ui-page-container>
</x-ui-page>

@push('styles')
<style>
    /* Toast UI Editor: make it feel like Bear/Obsidian (clean, minimal) */
    .content-block-editor-shell .toastui-editor-defaultUI {
        border: 1px solid var(--ui-border);
        border-radius: 12px;
        overflow: hidden;
    }
    .content-block-editor-shell .toastui-editor-toolbar {
        background: color-mix(in srgb, var(--ui-muted-5) 70%, transparent);
        border-bottom: 1px solid var(--ui-border);
    }
    .content-block-editor-shell .toastui-editor-contents {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        font-size: 17px;
        line-height: 1.7;
    }
    .content-block-editor-shell .toastui-editor-defaultUI-toolbar button {
        border-radius: 8px;
    }
    .content-block-editor-shell .toastui-editor-mode-switch {
        display: none !important;
    }

    /* Obsidian/Bear Style Markdown Rendering */
    .markdown-content {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        font-size: 17px;
        line-height: 1.7;
        color: var(--ui-secondary);
    }
    
    .markdown-content h1 {
        font-size: 2.5em;
        font-weight: 700;
        margin-top: 1.5em;
        margin-bottom: 0.5em;
        line-height: 1.2;
    }
    
    .markdown-content h2 {
        font-size: 2em;
        font-weight: 600;
        margin-top: 1.3em;
        margin-bottom: 0.5em;
        line-height: 1.3;
    }
    
    .markdown-content h3 {
        font-size: 1.5em;
        font-weight: 600;
        margin-top: 1.2em;
        margin-bottom: 0.5em;
    }
    
    .markdown-content h4 {
        font-size: 1.25em;
        font-weight: 600;
        margin-top: 1em;
        margin-bottom: 0.5em;
    }
    
    .markdown-content p {
        margin-bottom: 1em;
    }
    
    .markdown-content ul, .markdown-content ol {
        margin-bottom: 1em;
        padding-left: 1.5em;
    }
    
    .markdown-content li {
        margin-bottom: 0.5em;
    }
    
    .markdown-content code {
        background: var(--ui-muted-5);
        padding: 0.2em 0.4em;
        border-radius: 4px;
        font-size: 0.9em;
    }
    
    .markdown-content pre {
        background: var(--ui-muted-5);
        padding: 1em;
        border-radius: 8px;
        overflow-x: auto;
        margin-bottom: 1em;
    }
    
    .markdown-content blockquote {
        border-left: 3px solid var(--ui-primary);
        padding-left: 1em;
        margin-left: 0;
        color: var(--ui-muted);
        font-style: italic;
    }
</style>
@endpush
