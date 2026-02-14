<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsKanbanBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von KanbanBoards
 */
class UpdateKanbanBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.kanban_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/kanban_boards/{id} - Updates a Kanban Board. REST parameters: kanban_board_id (required, integer) - Kanban Board ID. name (optional, string) - Name. description (optional, string) - description. done (optional, boolean) - Mark as done.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Kanban Board (REQUIRED). Use "patients.kanban_boards.GET" to find Kanban Boards.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name of the Kanban Board.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Description of the Kanban Board.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Kanban Board als erledigt markieren.'
                ],
            ],
            'required' => ['kanban_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Use standardized ID validation
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'kanban_board_id',
                PatientsKanbanBoard::class,
                'KANBAN_BOARD_NOT_FOUND',
                'The specified Kanban Board was not found.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $kanbanBoard = $validation['model'];

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('update', $kanbanBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Kanban Board edit (policy).');
            }

            // Collect update data
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                if ($arguments['done']) {
                    $updateData['done_at'] = now();
                } else {
                    $updateData['done_at'] = null;
                }
            }

            // Update Kanban Board
            if (!empty($updateData)) {
                $kanbanBoard->update($updateData);
            }

            $kanbanBoard->refresh();
            $kanbanBoard->load(['patient', 'user', 'team']);

            return ToolResult::success([
                'kanban_board_id' => $kanbanBoard->id,
                'kanban_board_name' => $kanbanBoard->name,
                'description' => $kanbanBoard->description,
                'patient_id' => $kanbanBoard->patient_id,
                'patient_name' => $kanbanBoard->patient->name,
                'team_id' => $kanbanBoard->team_id,
                'done' => $kanbanBoard->done,
                'done_at' => $kanbanBoard->done_at?->toIso8601String(),
                'updated_at' => $kanbanBoard->updated_at->toIso8601String(),
                'message' => "Kanban Board '{$kanbanBoard->name}' successfully updated."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating Kanban Board: ' . $e->getMessage());
        }
    }
}
