<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsAnamnesisBoardBlock;
use Livewire\Attributes\On;

class AnamnesisBoardBlockTextEdit extends Component
{
    public PatientsAnamnesisBoardBlock $block;
    public $content = '';
    public $name = '';

    public function mount(PatientsAnamnesisBoardBlock $patientsAnamnesisBoardBlock, string $type)
    {
        $this->block = $patientsAnamnesisBoardBlock->load('content', 'row.section.anamnesisBoard');
        
        // Check authorization
        $this->authorize('view', $this->block->row->section->anamnesisBoard);

        // Check if content type matches
        if ($this->block->content_type !== $type) {
            abort(404);
        }
        
        // Load content
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->content = $this->block->content->content ?? '';
        }
        
        $this->name = $this->block->name;
    }

    #[On('updateBlock')] 
    public function updateBlock()
    {
        $this->block->refresh();
        $this->block->load('content');
        
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->content = $this->block->content->content ?? '';
        }
        
        $this->name = $this->block->name;

        // Editor sync (wire:ignore)
        $this->dispatch('content-block-sync-editor', [
            'blockId' => $this->block->id,
            'name' => $this->name,
            'content' => $this->content,
        ]);
    }

    public function save()
    {
        $this->authorize('update', $this->block->row->section->anamnesisBoard);
        
        // Update block name
        if (isset($this->name) && trim($this->name)) {
            $this->block->update([
                'name' => trim($this->name),
            ]);
        }
        
        // Update text content
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->block->content->update([
                'content' => $this->content ?? '',
            ]);
        }
        
        $this->block->refresh();
        $this->block->load('content');
        
        // Update local values
        $this->name = $this->block->name;
        if ($this->block->content_type === 'text' && $this->block->content) {
            $this->content = $this->block->content->content ?? '';
        }

        // Editor sync (wire:ignore) + UI can show "saved"
        $this->dispatch('content-block-saved', [
            'blockId' => $this->block->id,
            'savedAt' => now()->toIso8601String(),
        ]);
        
        // Update Anamnesis Board
        $this->dispatch('updateAnamnesisBoard');
    }

    public function generateDummyText($wordCount)
    {
        $lorem = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum';
        
        $words = explode(' ', $lorem);
        $generated = '';
        
        // Generate exactly the specified number of words
        for ($i = 0; $i < $wordCount; $i++) {
            $generated .= $words[$i % count($words)];
            if ($i < $wordCount - 1) {
                $generated .= ' ';
            }
        }
        
        // Append text at the end of current content
        $currentContent = $this->content ?? '';
        $separator = $currentContent && trim($currentContent) ? "\n\n" : '';
        $newContent = $currentContent . $separator . $generated;
        
        // Set content directly (important: without defer, so Livewire has it immediately)
        $this->content = $newContent;
        
        // Update editor via event
        $this->dispatch('content-block-insert-text', [
            'blockId' => $this->block->id,
            'text' => $generated,
            'fullContent' => $newContent,
        ]);
    }

    public function getBreadcrumbs()
    {
        $anamnesisBoard = $this->block->row->section->anamnesisBoard;
        $breadcrumbs = [
            ['name' => 'Patients', 'url' => route('patients.patients.index')],
            ['name' => $anamnesisBoard->patient->name, 'url' => route('patients.patients.show', $anamnesisBoard->patient)],
            ['name' => $anamnesisBoard->name, 'url' => route('patients.anamnesis-boards.show', $anamnesisBoard)],
        ];

        $breadcrumbs[] = [
            'name' => $this->block->name,
            'url' => route('patients.anamnesis-board-blocks.show', ['patientsAnamnesisBoardBlock' => $this->block->id, 'type' => $this->block->content_type]),
        ];

        return $breadcrumbs;
    }

    public function getPreviousBlock()
    {
        $allBlocks = PatientsAnamnesisBoardBlock::whereHas('row.section', function($q) {
            $q->where('anamnesis_board_id', $this->block->row->section->anamnesis_board_id);
        })
        ->where('content_type', $this->block->content_type)
        ->where('id', '<', $this->block->id)
        ->orderBy('id', 'desc')
        ->first();
        
        return $allBlocks;
    }

    public function getNextBlock()
    {
        $allBlocks = PatientsAnamnesisBoardBlock::whereHas('row.section', function($q) {
            $q->where('anamnesis_board_id', $this->block->row->section->anamnesis_board_id);
        })
        ->where('content_type', $this->block->content_type)
        ->where('id', '>', $this->block->id)
        ->orderBy('id', 'asc')
        ->first();
        
        return $allBlocks;
    }

    public function getAllBlocks()
    {
        return PatientsAnamnesisBoardBlock::whereHas('row.section', function($q) {
            $q->where('anamnesis_board_id', $this->block->row->section->anamnesis_board_id);
        })
        ->where('content_type', $this->block->content_type)
        ->with('row.section')
        ->orderBy('id', 'asc')
        ->get();
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->block),
            'modelId' => $this->block->id,
            'subject' => $this->block->name,
            'description' => mb_substr(strip_tags($this->block->content->content ?? ''), 0, 100),
            'url' => route('patients.anamnesis-board-blocks.show', ['patientsAnamnesisBoardBlock' => $this->block->id, 'type' => $this->block->content_type]),
            'source' => 'patients.anamnesis-board-block.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->block->created_at,
            ],
        ]);

        // Set organization context
        $this->dispatch('organization', [
            'context_type' => get_class($this->block),
            'context_id' => $this->block->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        // Set KeyResult context
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->block),
            'context_id' => $this->block->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $anamnesisBoard = $this->block->row->section->anamnesisBoard;
        $previousBlock = $this->getPreviousBlock();
        $nextBlock = $this->getNextBlock();
        $allBlocks = $this->getAllBlocks();

        return view('patients::livewire.anamnesis-board-block-text-edit', [
            'user' => $user,
            'anamnesisBoard' => $anamnesisBoard,
            'previousBlock' => $previousBlock,
            'nextBlock' => $nextBlock,
            'allBlocks' => $allBlocks,
        ])->layout('platform::layouts.app');
    }
}
