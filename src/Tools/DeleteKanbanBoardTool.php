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
 * Tool for deleting Kanban Boards in the Patients module
 */
class DeleteKanbanBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.kanban_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /patients/kanban_boards/{id} - Deletes a Kanban Board. REST parameters: kanban_board_id (required, integer) - Kanban Board ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Kanban Boards (REQUIRED). Use "patients.kanban_boards.GET" to find Kanban Boards.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Confirmation that the Kanban Board should really be deleted.'
                ]
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
                Gate::forUser($context->user)->authorize('delete', $kanbanBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Kanban Board delete (policy).');
            }

            $kanbanBoardName = $kanbanBoard->name;
            $kanbanBoardId = $kanbanBoard->id;
            $patientId = $kanbanBoard->patient_id;
            $teamId = $kanbanBoard->team_id;

            // Delete Kanban Board
            $kanbanBoard->delete();

            // Invalidate cache
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('patients.kanban_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'kanban_board_id' => $kanbanBoardId,
                'kanban_board_name' => $kanbanBoardName,
                'patient_id' => $patientId,
                'message' => "Kanban Board '{$kanbanBoardName}' wurde successfully deleted."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting Kanban Board: ' . $e->getMessage());
        }
    }
}
