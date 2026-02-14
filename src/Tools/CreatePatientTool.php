<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolDependencyContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsPatient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for creating patients in the Patients module
 */
class CreatePatientTool implements ToolContract, ToolDependencyContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.patients.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients - Creates a new patient. REST parameters: name (required, string) - patient name. team_id (optional, integer) - if not specified, the current team is used. description (optional, string) - description.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Patient name (REQUIRED). Ask the user explicitly for the name if not provided.'
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID of the team in which the patient should be created. If not specified, the current team from context is used.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Patient description. Ask if the user creates a patient but has not provided a description.'
                ],
            ],
            'required' => ['name']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Validation
            if (empty($arguments['name'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Patient name is required');
            }
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            // Determine team: from arguments or context
            $teamId = $arguments['team_id'] ?? null;
            if ($teamId === 0 || $teamId === '0') {
                $teamId = null;
            }
            
            $team = null;
            if (!empty($teamId)) {
                $team = $context->user->teams()->find($teamId);
                if (!$team) {
                    return ToolResult::error('TEAM_NOT_FOUND', 'The specified team was not found or you don't have access to it.');
                }
            } else {
                $team = $context->team;
                if (!$team) {
                    return ToolResult::error('MISSING_TEAM', 'No team specified and no team found in context. Patients require a team.');
                }
            }

            // Policy: Create patient
            try {
                Gate::forUser($context->user)->authorize('create', PatientsPatient::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to create patients (policy).');
            }

            // Create patient
            $patient = PatientsPatient::create([
                'name' => $arguments['name'],
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $team->id,
            ]);

            return ToolResult::success([
                'id' => $patient->id,
                'uuid' => $patient->uuid,
                'name' => $patient->name,
                'description' => $patient->description,
                'team_id' => $patient->team_id,
                'created_at' => $patient->created_at->toIso8601String(),
                'message' => "Patient '{$patient->name}' successfully created."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating patient: ' . $e->getMessage());
        }
    }

    public function getDependencies(): array
    {
        return [
            'required_fields' => [],
            'dependencies' => []
        ];
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'patient', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
