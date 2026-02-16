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

    public function navigateToNextPatient()
    {
        $teamId = $this->patient->team_id;
        $next = PatientsPatient::where('team_id', $teamId)
            ->where('name', '>', $this->patient->name)
            ->orderBy('name')
            ->first();

        if (!$next) {
            $next = PatientsPatient::where('team_id', $teamId)
                ->orderBy('name')
                ->first();
        }

        if ($next && $next->id !== $this->patient->id) {
            return $this->redirect(route('patients.patients.show', $next), navigate: true);
        }
    }

    public function navigateToPreviousPatient()
    {
        $teamId = $this->patient->team_id;
        $prev = PatientsPatient::where('team_id', $teamId)
            ->where('name', '<', $this->patient->name)
            ->orderByDesc('name')
            ->first();

        if (!$prev) {
            $prev = PatientsPatient::where('team_id', $teamId)
                ->orderByDesc('name')
                ->first();
        }

        if ($prev && $prev->id !== $this->patient->id) {
            return $this->redirect(route('patients.patients.show', $prev), navigate: true);
        }
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

        // Build boards list for sidebar navigation
        $allBoards = collect();

        foreach ($anamnesisBoards as $board) {
            $allBoards->push([
                'id' => $board->id,
                'name' => $board->name,
                'type' => 'anamnesis',
                'icon' => 'heroicon-o-document-text',
                'color' => 'blue',
                'route' => route('patients.anamnesis-boards.show', $board),
                'sections_count' => $board->sections()->count(),
            ]);
        }

        foreach ($kanbanBoards as $board) {
            $colorMap = [
                'Findings' => 'amber',
                'Therapy' => 'indigo',
                'Medication' => 'emerald',
            ];
            $iconMap = [
                'Findings' => 'heroicon-o-clipboard-document-check',
                'Therapy' => 'heroicon-o-heart',
                'Medication' => 'heroicon-o-beaker',
            ];
            $allBoards->push([
                'id' => $board->id,
                'name' => $board->name,
                'type' => 'kanban',
                'icon' => $iconMap[$board->name] ?? 'heroicon-o-view-columns',
                'color' => $colorMap[$board->name] ?? 'indigo',
                'route' => route('patients.kanban-boards.show', $board),
                'cards_count' => $board->cards()->count(),
            ]);
        }

        foreach ($progressBoards as $board) {
            $allBoards->push([
                'id' => $board->id,
                'name' => $board->name,
                'type' => 'progress',
                'icon' => 'heroicon-o-clock',
                'color' => 'purple',
                'route' => route('patients.progress-boards.show', $board),
                'cards_count' => $board->cards()->count(),
            ]);
        }

        return view('patients::livewire.patient', [
            'user' => $user,
            'anamnesisBoards' => $anamnesisBoards,
            'progressBoards' => $progressBoards,
            'kanbanBoards' => $kanbanBoards,
            'allBoards' => $allBoards,
        ])->layout('platform::layouts.app');
    }
}
