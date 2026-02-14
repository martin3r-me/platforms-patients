<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsPatient;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllPatients = false;

    public function mount()
    {
        // Load state from localStorage (set by frontend)
        $this->showAllPatients = false; // Default value, overridden by frontend
    }

    #[On('updateSidebar')] 
    public function updateSidebar()
    {
        // Will be implemented later
    }

    public function toggleShowAllPatients()
    {
        $this->showAllPatients = !$this->showAllPatients;
    }

    public function createPatient()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            return;
        }

        // Check policy authorization
        $this->authorize('create', PatientsPatient::class);

        // Create new patient
        $patient = PatientsPatient::create([
            'name' => 'New Patient',
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $this->dispatch('updateSidebar');
        
        // Redirect to patient view
        return $this->redirect(route('patients.patients.show', $patient), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam->id ?? null;

        if (!$user || !$teamId) {
            return view('patients::livewire.sidebar', [
                'patients' => collect(),
                'hasMorePatients' => false,
                'allPatientsCount' => 0,
            ]);
        }

        // All patients of the team
        $allPatients = PatientsPatient::query()
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get();

        // Filter patients: all or specific ones (extensible later)
        $patientsToShow = $this->showAllPatients
            ? $allPatients
            : $allPatients; // Later: only patients matching specific criteria

        $hasMorePatients = false; // Later: when filter logic is implemented

        return view('patients::livewire.sidebar', [
            'patients' => $patientsToShow,
            'hasMorePatients' => $hasMorePatients,
            'allPatientsCount' => $allPatients->count(),
        ]);
    }
}
