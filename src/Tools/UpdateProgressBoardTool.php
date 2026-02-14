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
 * Tool zum Bearbeiten von ProgressBoards
 */
class UpdateProgressBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.progress_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/progress_boards/{id} - Updates a Progress Board. REST parameters: progress_board_id (required, integer) - Progress Board ID. name (optional, string) - Name. description (optional, string) - description. done (optional, boolean) - Mark as done.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'progress_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Progress Board (REQUIRED). Use "patients.progress_boards.GET" to find Progress Boards.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name of the Progress Board.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Description of the Progress Board.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Progress Board als erledigt markieren.'
                ],
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
                Gate::forUser($context->user)->authorize('update', $progressBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Progress Board edit (policy).');
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

            // Update Progress Board
            if (!empty($updateData)) {
                $progressBoard->update($updateData);
            }

            $progressBoard->refresh();
            $progressBoard->load(['patient', 'user', 'team']);

            return ToolResult::success([
                'progress_board_id' => $progressBoard->id,
                'progress_board_name' => $progressBoard->name,
                'description' => $progressBoard->description,
                'patient_id' => $progressBoard->patient_id,
                'patient_name' => $progressBoard->patient->name,
                'team_id' => $progressBoard->team_id,
                'done' => $progressBoard->done,
                'done_at' => $progressBoard->done_at?->toIso8601String(),
                'updated_at' => $progressBoard->updated_at->toIso8601String(),
                'message' => "Progress Board '{$progressBoard->name}' successfully updated."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating Progress Board: ' . $e->getMessage());
        }
    }
}
