<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Platform\Patients\Models\PatientsAnamnesisBoardBlock;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AnamnesisBoardBlockSettingsModal extends Component
{
    public $modalShow = false;
    public $block;
    public $span;
    public $name;
    public $description;
    public $contentType;

    #[On('open-modal-anamnesis-board-block-settings')] 
    public function openModalAnamnesisBoardBlockSettings($blockId)
    {
        $this->block = PatientsAnamnesisBoardBlock::with('row.section.anamnesisBoard')->findOrFail($blockId);
        
        // Check policy authorization
        $this->authorize('update', $this->block->row->section->anamnesisBoard);

        $this->span = $this->block->span;
        $this->name = $this->block->name;
        $this->description = $this->block->description;
        $this->contentType = $this->block->content_type;
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'span' => 'required|integer|min:1|max:12',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contentType' => 'nullable|string|in:text,image,carousel,video',
        ];
    }

    public function setContentType($type)
    {
        if (!$this->block) {
            return;
        }
        
        $this->authorize('update', $this->block->row->section->anamnesisBoard);
        
        // Delete existing content if present
        if ($this->block->content) {
            $this->block->content->delete();
        }
        
        // Create new content when type is set
        if ($type === 'text') {
            $user = Auth::user();
            $team = $user->currentTeam;
            
            $textContent = \Platform\Patients\Models\PatientsAnamnesisBoardBlockText::create([
                'content' => '',
                'user_id' => $user->id,
                'team_id' => $team->id,
            ]);
            
            $this->block->content_type = 'text';
            $this->block->content_id = $textContent->id;
            $this->block->save();
            
            $this->contentType = 'text';
        } else {
            $this->block->content_type = $type;
            $this->block->content_id = null;
            $this->block->save();
            
            $this->contentType = $type;
        }
        
        $this->dispatch('updateAnamnesisBoard');
        $this->dispatch('updateSection');
    }

    public function save()
    {
        $this->validate();
        
        if (!$this->block) {
            return;
        }
        
        // Check policy authorization
        $this->authorize('update', $this->block->row->section->anamnesisBoard);

        $row = $this->block->row;
        $row->load('blocks');
        
        // Calculate the current sum of all spans in this row
        $currentSum = $row->blocks->sum('span');
        $currentBlockSpan = $this->block->span;
        $newSum = $currentSum - $currentBlockSpan + $this->span;
        
        // Check if the new sum exceeds 12
        if ($newSum > 12) {
            $this->addError('span', "The total of all spans in a row must not exceed 12. Current: {$currentSum}, with new value: {$newSum}.");
            return;
        }
        
        $this->block->span = $this->span;
        $this->block->name = trim($this->name);
        $this->block->description = $this->description ? trim($this->description) : null;
        $this->block->save();
        
        // Store block info for notification before reset()
        // Use ID and class directly to ensure they are set
        $blockId = $this->block->id;
        $blockClass = \Platform\Patients\Models\PatientsAnamnesisBoardBlock::class;
        
        $this->dispatch('updateAnamnesisBoard');
        $this->dispatch('updateSection');

        $this->dispatch('notifications:store', [
            'title' => 'Block saved',
            'message' => 'The block has been successfully updated.',
            'notice_type' => 'success',
            'noticable_type' => $blockClass,
            'noticable_id'   => $blockId,
        ]);

        $this->reset(['span', 'name', 'description']);
        $this->closeModal();
    }

    public function deleteBlock()
    {
        if (!$this->block) {
            return;
        }
        
        // Check policy authorization
        $this->authorize('update', $this->block->row->section->anamnesisBoard);

        // Store block info for notification before delete()
        $blockId = $this->block->id;
        $blockClass = \Platform\Patients\Models\PatientsAnamnesisBoardBlock::class;
        
        $this->block->delete();
        
        $this->dispatch('updateAnamnesisBoard');
        $this->dispatch('updateSection');

        $this->dispatch('notifications:store', [
            'title' => 'Block deleted',
            'message' => 'The block has been successfully deleted.',
            'notice_type' => 'success',
            'noticable_type' => $blockClass,
            'noticable_id'   => $blockId,
        ]);

        $this->reset(['span', 'name', 'description', 'contentType']);
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('patients::livewire.anamnesis-board-block-settings-modal')->layout('platform::layouts.app');
    }
}
