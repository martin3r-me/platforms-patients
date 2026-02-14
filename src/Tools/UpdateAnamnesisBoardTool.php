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
 * Tool zum Bearbeiten von AnamnesisBoards
 */
class UpdateAnamnesisBoardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.anamnesis_boards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/anamnesis_boards/{id} - Updates an Anamnesis Board. REST parameters: anamnesis_board_id (required, integer) - Anamnesis Board ID. name (optional, string) - Name. description (optional, string) - description. done (optional, boolean) - Mark as done.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'anamnesis_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Anamnesis Board (REQUIRED). Use "patients.anamnesis_boards.GET" to find Anamnesis Boards.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name of the Anamnesis Board.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Description of the Anamnesis Board.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Anamnesis Board als erledigt markieren.'
                ],
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
                Gate::forUser($context->user)->authorize('update', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Anamnesis Board edit (policy).');
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

            // Update Anamnesis Board
            if (!empty($updateData)) {
                $anamnesisBoard->update($updateData);
            }

            $anamnesisBoard->refresh();
            $anamnesisBoard->load(['patient', 'user', 'team']);

            return ToolResult::success([
                'anamnesis_board_id' => $anamnesisBoard->id,
                'anamnesis_board_name' => $anamnesisBoard->name,
                'description' => $anamnesisBoard->description,
                'patient_id' => $anamnesisBoard->patient_id,
                'patient_name' => $anamnesisBoard->patient->name,
                'team_id' => $anamnesisBoard->team_id,
                'done' => $anamnesisBoard->done,
                'done_at' => $anamnesisBoard->done_at?->toIso8601String(),
                'updated_at' => $anamnesisBoard->updated_at->toIso8601String(),
                'message' => "Anamnesis Board '{$anamnesisBoard->name}' successfully updated."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating Anamnesis Board: ' . $e->getMessage());
        }
    }
}
