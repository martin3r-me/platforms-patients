<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsProgressBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for deleting Progress Boards in the Patients module
 */
class DeleteProgressBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.progress_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /patients/progress_boards/{id} - Deletes a Progress Board. REST parameters: progress_board_id (required, integer) - Progress Board ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'progress_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Progress Boards (REQUIRED). Use "patients.progress_boards.GET" to find Progress Boards.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Confirmation that the Progress Board should really be deleted.'
                ]
            ],
            'required' => ['progress_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Use standardized ID validation
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'progress_board_id',
                PatientsProgressBoard::class,
                'PROGRESS_BOARD_NOT_FOUND',
                'The specified Progress Board was not found.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $progressBoard = $validation['model'];
            
            // Check policy
            try {
                Gate::forUser($context->user)->authorize('delete', $progressBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Progress Board delete (policy).');
            }

            $progressBoardName = $progressBoard->name;
            $progressBoardId = $progressBoard->id;
            $patientId = $progressBoard->patient_id;
            $teamId = $progressBoard->team_id;

            // Delete Progress Board
            $progressBoard->delete();

            // Invalidate cache
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('patients.progress_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'progress_board_id' => $progressBoardId,
                'progress_board_name' => $progressBoardName,
                'patient_id' => $patientId,
                'message' => "Progress Board '{$progressBoardName}' wurde successfully deleted."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting Progress Board: ' . $e->getMessage());
        }
    }
}
