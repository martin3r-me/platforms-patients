<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsPatient;
use Platform\Patients\Models\PatientsKanbanBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for creating Kanban Boards in the Patients module
 */
class CreateKanbanBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.kanban_boards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/{patient_id}/kanban_boards - Creates a new Kanban Board for a patient. REST parameters: patient_id (required, integer) - patient ID. name (optional, string) - Board-Name. description (optional, string) - description.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'patient_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the patient to which the Kanban Board belongs (REQUIRED). Use "patients.patients.GET" to find patients.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name of the Kanban Board. If not specified, defaults to "New Kanban Board".'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of the Kanban Board.'
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

            $name = $arguments['name'] ?? 'New Kanban Board';

            // Create Kanban Board directly
            $kanbanBoard = PatientsKanbanBoard::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $patient->team_id,
                'patient_id' => $patient->id,
            ]);

            $kanbanBoard->load(['patient', 'user', 'team']);

            return ToolResult::success([
                'id' => $kanbanBoard->id,
                'uuid' => $kanbanBoard->uuid,
                'name' => $kanbanBoard->name,
                'description' => $kanbanBoard->description,
                'patient_id' => $kanbanBoard->patient_id,
                'patient_name' => $kanbanBoard->patient->name,
                'team_id' => $kanbanBoard->team_id,
                'created_at' => $kanbanBoard->created_at->toIso8601String(),
                'message' => "Kanban Board '{$kanbanBoard->name}' successfully created for patient '{$kanbanBoard->patient->name}' ."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating Kanban Board: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'kanban_board', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
