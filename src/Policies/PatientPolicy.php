<?php

namespace Platform\Patients\Policies;

use Platform\Core\Models\User;
use Platform\Patients\Models\PatientsPatient;

class PatientPolicy
{
    /**
     * Can the user view this patient?
     */
    public function view(User $user, PatientsPatient $patient): bool
    {
        // User must be in the same team
        return $patient->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user update this patient?
     */
    public function update(User $user, PatientsPatient $patient): bool
    {
        // User must be in the same team
        return $patient->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user delete this patient?
     */
    public function delete(User $user, PatientsPatient $patient): bool
    {
        // Only team members in the same team can delete
        return $patient->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user create a patient?
     */
    public function create(User $user): bool
    {
        // Any team member can create patients
        return $user->currentTeam !== null;
    }

    /**
     * Can the user open the settings?
     * Anyone with view permissions can open settings
     */
    public function settings(User $user, PatientsPatient $patient): bool
    {
        // Anyone with view permissions can open settings
        return $this->view($user, $patient);
    }
}
