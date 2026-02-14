<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsPatient;
use Platform\Patients\Models\PatientsProgressBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for creating Progress Boards in the Patients module
 */
class CreateProgressBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.progress_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/{patient_id}/progress_boards - Creates a new Progress Board for a patient. REST parameters: patient_id (required, integer) - patient ID. name (optional, string) - Board-Name. description (optional, string) - description.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'patient_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the patient to which the Progress Board belongs (REQUIRED). Use "patients.patients.GET" to find patients.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name of the Progress Board. If not specified, defaults to "New Progress Board".'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of the Progress Board.'
                ],
            ],
            'required' => ['patient_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            // Find patient
            $patientId = $arguments['patient_id'] ?? null;
            if (!$patientId) {
                return ToolResult::error('VALIDATION_ERROR', 'patient_id is required.');
            }

            $patient = PatientsPatient::find($patientId);
            if (!$patient) {
                return ToolResult::error('PATIENT_NOT_FOUND', 'The specified patient was not found. Use "patients.patients.GET" to find patients.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('update', $patient);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to create boards for this patient (policy).');
            }

            $name = $arguments['name'] ?? 'New Progress Board';

            // Create Progress Board directly
            $progressBoard = PatientsProgressBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $patient->team_id,
                'patient_id' => $patient->id,
            ]);

            $progressBoard->load(['patient', 'user', 'team']);

            return ToolResult::success([
                'id' => $progressBoard->id,
                'uuid' => $progressBoard->uuid,
                'name' => $progressBoard->name,
                'description' => $progressBoard->description,
                'patient_id' => $progressBoard->patient_id,
                'patient_name' => $progressBoard->patient->name,
                'team_id' => $progressBoard->team_id,
                'created_at' => $progressBoard->created_at->toIso8601String(),
                'message' => "Progress Board '{$progressBoard->name}' successfully created for patient '{$progressBoard->patient->name}' ."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating Progress Board: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'progress_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
