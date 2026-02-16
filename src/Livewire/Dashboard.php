<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsPatient;

class Dashboard extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = 'active';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilter()
    {
        $this->resetPage();
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => 'Platform\Patients\Models\PatientsPatient',
            'modelId' => null,
            'subject' => 'Patients Dashboard',
            'description' => 'Overview of all patients',
            'url' => route('patients.dashboard'),
            'source' => 'patients.dashboard',
            'recipients' => [],
            'meta' => [
                'view_type' => 'dashboard',
            ],
        ]);
    }

    public function createPatient()
    {
        $user = Auth::user();

        // Check policy authorization
        $this->authorize('create', PatientsPatient::class);

        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'No team selected.');
            return;
        }

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
        $user = Auth::user();
        $team = $user->currentTeam;

        // Stats query (fast, no eager loading)
        $totalPatients = PatientsPatient::where('team_id', $team->id)->count();
        $activePatients = PatientsPatient::where('team_id', $team->id)
            ->where(function ($q) {
                $q->whereNull('done')->orWhere('done', false);
            })
            ->count();
        $completedPatients = $totalPatients - $activePatients;

        // Paginated patient list with search and filter
        $query = PatientsPatient::where('team_id', $team->id)->orderBy('name');

        if (!empty($this->search)) {
            $searchTerm = $this->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        if ($this->filter === 'active') {
            $query->where(function ($q) {
                $q->whereNull('done')->orWhere('done', false);
            });
        } elseif ($this->filter === 'completed') {
            $query->where('done', true);
        }

        $patients = $query->paginate(20);

        return view('patients::livewire.dashboard', [
            'activePatients' => $activePatients,
            'totalPatients' => $totalPatients,
            'completedPatients' => $completedPatients,
            'patients' => $patients,
        ])->layout('platform::layouts.app');
    }
}
