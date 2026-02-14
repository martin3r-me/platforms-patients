<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsAnamnesisBoard;
use Livewire\Attributes\On;

class AnamnesisBoard extends Component
{
    public PatientsAnamnesisBoard $anamnesisBoard;

    public function mount(PatientsAnamnesisBoard $patientsAnamnesisBoard)
    {
        // Reload model to ensure all data is available
        $this->anamnesisBoard = $patientsAnamnesisBoard->fresh()->load('sections.rows.blocks.content');
        
        // Check authorization
        $this->authorize('view', $this->anamnesisBoard);
    }

    #[On('updateAnamnesisBoard')] 
    public function updateAnamnesisBoard()
    {
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function rules(): array
    {
        return [
            'anamnesisBoard.name' => 'required|string|max:255',
            'anamnesisBoard.description' => 'nullable|string',
        ];
    }

    public function createSection()
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'No team selected.');
            return;
        }

        $section = \Platform\Patients\Models\PatientsAnamnesisBoardSection::create([
            'name' => 'New Section',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'anamnesis_board_id' => $this->anamnesisBoard->id,
        ]);

        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function createRow($sectionId)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'No team selected.');
            return;
        }

        $row = \Platform\Patients\Models\PatientsAnamnesisBoardRow::create([
            'name' => 'New Row',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'section_id' => $sectionId,
        ]);

        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function createBlock($rowId)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'No team selected.');
            return;
        }

        $row = \Platform\Patients\Models\PatientsAnamnesisBoardRow::findOrFail($rowId);
        
        // Check if the sum of spans has already reached 12
        $currentSpanSum = $row->blocks()->sum('span');
        if ($currentSpanSum >= 12) {
            session()->flash('error', 'The total of all spans in a row must not exceed 12. Current: ' . $currentSpanSum . '/12.');
            return;
        }

        $block = \Platform\Patients\Models\PatientsAnamnesisBoardBlock::create([
            'name' => 'New Block',
            'description' => null,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'row_id' => $rowId,
            'span' => 1,
        ]);

        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function updateBlockSpan($blockId, $newSpan)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $block = \Platform\Patients\Models\PatientsAnamnesisBoardBlock::findOrFail($blockId);
        $row = $block->row;
        
        // Validation: span must be between 1 and 12
        $newSpan = max(1, min(12, (int)$newSpan));
        
        // Calculate the current sum of all spans in this row
        $currentSum = $row->blocks()->sum('span');
        $currentBlockSpan = $block->span;
        $newSum = $currentSum - $currentBlockSpan + $newSpan;

        // Check if the new sum exceeds 12
        if ($newSum > 12) {
            session()->flash('error', "The total of all spans in a row must not exceed 12. Current: {$currentSum}, with new value: {$newSum}.");
            return;
        }
        
        $block->span = $newSpan;
        $block->save();
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function deleteBlock($blockId)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $block = \Platform\Patients\Models\PatientsAnamnesisBoardBlock::findOrFail($blockId);
        $block->delete();
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function deleteRow($rowId)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $row = \Platform\Patients\Models\PatientsAnamnesisBoardRow::findOrFail($rowId);
        $row->delete();
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function deleteSection($sectionId)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $section = \Platform\Patients\Models\PatientsAnamnesisBoardSection::findOrFail($sectionId);
        $section->delete();
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function updateSectionName($sectionId, $newName)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $section = \Platform\Patients\Models\PatientsAnamnesisBoardSection::findOrFail($sectionId);
        $section->name = trim($newName);
        $section->save();
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function updateRowName($rowId, $newName)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $row = \Platform\Patients\Models\PatientsAnamnesisBoardRow::findOrFail($rowId);
        $row->name = trim($newName);
        $row->save();
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function updateBlockName($blockId, $newName)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        $block = \Platform\Patients\Models\PatientsAnamnesisBoardBlock::findOrFail($blockId);
        $block->name = trim($newName);
        $block->save();
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    /**
     * Updates the order of sections after drag & drop
     */
    public function updateSectionOrder($sections)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        foreach ($sections as $section) {
            $sectionDb = \Platform\Patients\Models\PatientsAnamnesisBoardSection::find($section['value']);
            if ($sectionDb) {
                $sectionDb->order = $section['order'];
                $sectionDb->save();
            }
        }
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    /**
     * Updates the order of rows within a section after drag & drop
     */
    public function updateRowOrder($groups)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        foreach ($groups as $group) {
            $sectionId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $row = \Platform\Patients\Models\PatientsAnamnesisBoardRow::find($item['value']);

                if (!$row) {
                    continue;
                }

                $row->order = $item['order'];
                $row->section_id = $sectionId;
                $row->save();
            }
        }
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    /**
     * Updates the order of blocks within a row after drag & drop
     */
    public function updateBlockOrder($groups)
    {
        $this->authorize('update', $this->anamnesisBoard);
        
        foreach ($groups as $group) {
            $rowId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $block = \Platform\Patients\Models\PatientsAnamnesisBoardBlock::find($item['value']);

                if (!$block) {
                    continue;
                }

                $block->order = $item['order'];
                $block->row_id = $rowId;
                $block->save();
            }
        }
        
        $this->anamnesisBoard->refresh();
        $this->anamnesisBoard->load('sections.rows.blocks.content');
    }

    public function render()
    {
        $user = Auth::user();

        return view('patients::livewire.anamnesis-board', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
