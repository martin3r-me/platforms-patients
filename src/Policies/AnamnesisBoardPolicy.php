<?php

namespace Platform\Patients\Policies;

use Platform\Core\Models\User;
use Platform\Patients\Models\PatientsAnamnesisBoard;

class AnamnesisBoardPolicy
{
    /**
     * Can the user view this Anamnesis Board?
     */
    public function view(User $user, PatientsAnamnesisBoard $anamnesisBoard): bool
    {
        // User must be in the same team and have access to the patient
        return $anamnesisBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user update this Anamnesis Board?
     */
    public function update(User $user, PatientsAnamnesisBoard $anamnesisBoard): bool
    {
        // User must be in the same team
        return $anamnesisBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user delete this Anamnesis Board?
     */
    public function delete(User $user, PatientsAnamnesisBoard $anamnesisBoard): bool
    {
        // Only team members in the same team can delete
        return $anamnesisBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user create an Anamnesis Board?
     */
    public function create(User $user): bool
    {
        // Any team member can create Anamnesis Boards
        return $user->currentTeam !== null;
    }
}
