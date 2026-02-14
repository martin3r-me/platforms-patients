<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsPatient;
use Platform\Patients\Models\PatientsAnamnesisBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for creating Anamnesis Boards in the Patients module
 */
class CreateAnamnesisBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.anamnesis_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/{patient_id}/anamnesis_boards - Creates a new Anamnesis Board for a patient. REST parameters: patient_id (required, integer) - patient ID. name (optional, string) - Board-Name. description (optional, string) - description.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'patient_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the patient to which the Anamnesis Board belongs (REQUIRED). Use "patients.patients.GET" to find patients.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name of the Anamnesis Board. If not specified, defaults to "New Anamnesis Board".'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of the Anamnesis Board.'
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

            $name = $arguments['name'] ?? 'New Anamnesis Board';

            // Create Anamnesis Board directly
            $anamnesisBoard = PatientsAnamnesisBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $patient->team_id,
                'patient_id' => $patient->id,
            ]);

            $anamnesisBoard->load(['patient', 'user', 'team']);

            return ToolResult::success([
                'id' => $anamnesisBoard->id,
                'uuid' => $anamnesisBoard->uuid,
                'name' => $anamnesisBoard->name,
                'description' => $anamnesisBoard->description,
                'patient_id' => $anamnesisBoard->patient_id,
                'patient_name' => $anamnesisBoard->patient->name,
                'team_id' => $anamnesisBoard->team_id,
                'created_at' => $anamnesisBoard->created_at->toIso8601String(),
                'message' => "Anamnesis Board '{$anamnesisBoard->name}' successfully created for patient '{$anamnesisBoard->patient->name}' ."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating Anamnesis Board: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'anamnesis_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
