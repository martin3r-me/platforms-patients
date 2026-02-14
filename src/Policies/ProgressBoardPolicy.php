<?php

namespace Platform\Patients\Policies;

use Platform\Core\Models\User;
use Platform\Patients\Models\PatientsProgressBoard;

class ProgressBoardPolicy
{
    /**
     * Can the user view this Progress Board?
     */
    public function view(User $user, PatientsProgressBoard $progressBoard): bool
    {
        // User must be in the same team and have access to the patient
        return $progressBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user update this Progress Board?
     */
    public function update(User $user, PatientsProgressBoard $progressBoard): bool
    {
        // User must be in the same team
        return $progressBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user delete this Progress Board?
     */
    public function delete(User $user, PatientsProgressBoard $progressBoard): bool
    {
        // Only team members in the same team can delete
        return $progressBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user create a Progress Board?
     */
    public function create(User $user): bool
    {
        // Any team member can create Progress Boards
        return $user->currentTeam !== null;
    }
}
