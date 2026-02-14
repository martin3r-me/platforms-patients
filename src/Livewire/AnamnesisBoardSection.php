<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsAnamnesisBoardSection;
use Livewire\Attributes\On;

class AnamnesisBoardSection extends Component
{
    public PatientsAnamnesisBoardSection $section;

    public function mount(PatientsAnamnesisBoardSection $patientsAnamnesisBoardSection)
    {
        // Reload model to ensure all data is available
        $this->section = $patientsAnamnesisBoardSection->fresh()->load(['anamnesisBoard', 'rows.blocks']);
        
        // Check authorization
        $this->authorize('view', $this->section->anamnesisBoard);
    }

    #[On('updateSection')] 
    public function updateSection()
    {
        $this->section->refresh();
        $this->section->load('rows.blocks');
    }

    public function createRow()
    {
        $this->authorize('update', $this->section->anamnesisBoard);
        
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
            'section_id' => $this->section->id,
        ]);

        $this->section->refresh();
        $this->section->load('rows.blocks');
    }

    public function createBlock($rowId)
    {
        $this->authorize('update', $this->section->anamnesisBoard);
        
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

        $this->section->refresh();
        $this->section->load('rows.blocks');
    }

    public function render()
    {
        $user = Auth::user();

        return view('patients::livewire.anamnesis-board-section', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
