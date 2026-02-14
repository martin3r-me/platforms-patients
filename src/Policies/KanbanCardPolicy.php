<?php

namespace Platform\Patients\Policies;

use Platform\Core\Models\User;
use Platform\Patients\Models\PatientsKanbanCard;

class KanbanCardPolicy
{
    /**
     * Can the user view this Kanban Card?
     */
    public function view(User $user, PatientsKanbanCard $kanbanCard): bool
    {
        // User must be in the same team
        return $kanbanCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user update this Kanban Card?
     */
    public function update(User $user, PatientsKanbanCard $kanbanCard): bool
    {
        // User must be in the same team
        return $kanbanCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user delete this Kanban Card?
     */
    public function delete(User $user, PatientsKanbanCard $kanbanCard): bool
    {
        // Only team members in the same team can delete
        return $kanbanCard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user create a Kanban Card?
     */
    public function create(User $user): bool
    {
        // Any team member can create Kanban Cards
        return $user->currentTeam !== null;
    }
}
