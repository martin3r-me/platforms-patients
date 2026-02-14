<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsPatient;
use Livewire\Attributes\On;

class Patient extends Component
{
    public PatientsPatient $patient;

    public function mount(PatientsPatient $patientsPatient)
    {
        $this->patient = $patientsPatient;

        // Check authorization
        $this->authorize('view', $this->patient);
    }

    #[On('updatePatient')]
    public function updatePatient()
    {
        $this->patient->refresh();
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->patient),
            'modelId' => $this->patient->id,
            'subject' => $this->patient->name,
            'description' => $this->patient->description ?? '',
            'url' => route('patients.patients.show', $this->patient),
            'source' => 'patients.patient.view',
            'recipients' => [],
            'capabilities' => [
                'manage_channels' => true,
                'threads' => false,
            ],
            'meta' => [
                'created_at' => $this->patient->created_at,
            ],
        ]);

        // Set organization context
        $this->dispatch('organization', [
            'context_type' => get_class($this->patient),
            'context_id' => $this->patient->id,
            'allow_time_entry' => true,
            'allow_entities' => true,
            'allow_dimensions' => true,
        ]);

        // Set KeyResult context
        $this->dispatch('keyresult', [
            'context_type' => get_class($this->patient),
            'context_id' => $this->patient->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        // Load boards for this patient
        $anamnesisBoards = $this->patient->anamnesisBoards;
        $progressBoards = $this->patient->progressBoards;
        $kanbanBoards = $this->patient->kanbanBoards;

        return view('patients::livewire.patient', [
            'user' => $user,
            'anamnesisBoards' => $anamnesisBoards,
            'progressBoards' => $progressBoards,
            'kanbanBoards' => $kanbanBoards,
        ])->layout('platform::layouts.app');
    }
}
