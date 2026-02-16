<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsPatient;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllPatients = false;
    public string $search = '';

    public function mount()
    {
        $this->showAllPatients = false;
    }

    #[On('updateSidebar')]
    public function updateSidebar()
    {
        // Refresh patient list
    }

    public function toggleShowAllPatients()
    {
        $this->showAllPatients = !$this->showAllPatients;
    }

    public function updatedSearch()
    {
        // Livewire auto-updates on property change
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

        // Build query with search
        $query = PatientsPatient::query()
            ->where('team_id', $teamId)
            ->orderBy('name');

        // Apply search filter
        if (!empty($this->search)) {
            $searchTerm = $this->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        $allPatientsCount = PatientsPatient::where('team_id', $teamId)->count();
        $patients = $query->get();
        $hasMorePatients = false;

        return view('patients::livewire.sidebar', [
            'patients' => $patients,
            'hasMorePatients' => $hasMorePatients,
            'allPatientsCount' => $allPatientsCount,
        ]);
    }
}
