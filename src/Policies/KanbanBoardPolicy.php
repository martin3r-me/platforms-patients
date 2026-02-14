<?php

namespace Platform\Patients\Policies;

use Platform\Core\Models\User;
use Platform\Patients\Models\PatientsKanbanBoard;

class KanbanBoardPolicy
{
    /**
     * Can the user view this Kanban Board?
     */
    public function view(User $user, PatientsKanbanBoard $kanbanBoard): bool
    {
        // User must be in the same team and have access to the patient
        return $kanbanBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user update this Kanban Board?
     */
    public function update(User $user, PatientsKanbanBoard $kanbanBoard): bool
    {
        // User must be in the same team
        return $kanbanBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user delete this Kanban Board?
     */
    public function delete(User $user, PatientsKanbanBoard $kanbanBoard): bool
    {
        // Only team members in the same team can delete
        return $kanbanBoard->team_id === $user->currentTeam?->id;
    }

    /**
     * Can the user create a Kanban Board?
     */
    public function create(User $user): bool
    {
        // Any team member can create Kanban Boards
        return $user->currentTeam !== null;
    }
}
