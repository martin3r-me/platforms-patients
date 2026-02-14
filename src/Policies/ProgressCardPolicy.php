<?php

namespace Platform\Patients\Policies;

use Platform\Core\Models\User;
use Platform\Patients\Models\PatientsProgressCard;

class ProgressCardPolicy
{
    /**
     * Can the user view this Progress Card?
     */
    public function view(User $user, PatientsProgressCard $progressCard): bool
    {
        // User must be in the same team
        return $progressCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user update this Progress Card?
     */
    public function update(User $user, PatientsProgressCard $progressCard): bool
    {
        // User must be in the same team
        return $progressCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user delete this Progress Card?
     */
    public function delete(User $user, PatientsProgressCard $progressCard): bool
    {
        // Only team members in the same team can delete
        return $progressCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user create a Progress Card?
     */
    public function create(User $user): bool
    {
        // Any team member can create Progress Cards
        return $user->currentTeam !== null;
    }
}
