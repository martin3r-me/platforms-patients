<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsAnamnesisBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for deleting Anamnesis Boards in the Patients module
 */
class DeleteAnamnesisBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.anamnesis_boards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /patients/anamnesis_boards/{id} - Deletes a Anamnesis Board. REST parameters: anamnesis_board_id (required, integer) - Anamnesis Board ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'anamnesis_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Anamnesis Boards (REQUIRED). Use "patients.anamnesis_boards.GET" to find Anamnesis Boards.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Confirmation that the Anamnesis Board should really be deleted.'
                ]
            ],
            'required' => ['anamnesis_board_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Use standardized ID validation
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'anamnesis_board_id',
                PatientsAnamnesisBoard::class,
                'ANAMNESIS_BOARD_NOT_FOUND',
                'The specified Anamnesis Board was not found.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $anamnesisBoard = $validation['model'];
            
            // Check policy
            try {
                Gate::forUser($context->user)->authorize('delete', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Anamnesis Board delete (policy).');
            }

            $anamnesisBoardName = $anamnesisBoard->name;
            $anamnesisBoardId = $anamnesisBoard->id;
            $patientId = $anamnesisBoard->patient_id;
            $teamId = $anamnesisBoard->team_id;

            // Delete Anamnesis Board
            $anamnesisBoard->delete();

            // Invalidate cache
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('patients.anamnesis_boards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'anamnesis_board_id' => $anamnesisBoardId,
                'anamnesis_board_name' => $anamnesisBoardName,
                'patient_id' => $patientId,
                'message' => "Anamnesis Board '{$anamnesisBoardName}' wurde successfully deleted."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting Anamnesis Board: ' . $e->getMessage());
        }
    }
}
