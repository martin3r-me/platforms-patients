<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsPatient;

class Dashboard extends Component
{
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
        
        // === PATIENTS (team patients only) ===
        $patients = PatientsPatient::where('team_id', $team->id)->orderBy('name')->get();
        $activePatients = $patients->filter(function($patient) {
            return $patient->done === null || $patient->done === false;
        })->count();
        $totalPatients = $patients->count();

        // === PATIENT OVERVIEW (active patients only) ===
        $activePatientsList = $patients->filter(function($patient) {
            return $patient->done === null || $patient->done === false;
        })
        ->map(function ($patient) {
            return [
                'id' => $patient->id,
                'name' => $patient->name,
                'subtitle' => $patient->description ? mb_substr($patient->description, 0, 50) . '...' : '',
            ];
        })
        ->take(5);

        return view('patients::livewire.dashboard', [
            'activePatients' => $activePatients,
            'totalPatients' => $totalPatients,
            'activePatientsList' => $activePatientsList,
        ])->layout('platform::layouts.app');
    }
}
