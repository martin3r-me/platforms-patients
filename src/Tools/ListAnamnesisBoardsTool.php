<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Patients\Models\PatientsPatient;
use Platform\Patients\Models\PatientsAnamnesisBoard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool for listing Anamnesis Boards in the Patients module
 */
class ListAnamnesisBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'patients.anamnesis_boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/{patient_id}/anamnesis_boards - Lists them. REST parameters: patient_id (required, integer) - patient ID. filters (optional, array) - filter array. search (optional, string) - search term. sort (optional, array) - sorting. limit/offset (optional) - pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'patient_id' => [
                        'type' => 'integer',
                        'description' => 'REST parameter (required): Patient ID. Use "patients.patients.GET" to find patients.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            $patientId = $arguments['patient_id'] ?? null;
            if (!$patientId) {
                return ToolResult::error('VALIDATION_ERROR', 'patient_id is required.');
            }

            $patient = PatientsPatient::find($patientId);
            if (!$patient) {
                return ToolResult::error('PATIENT_NOT_FOUND', 'The specified patient was not found.');
            }

            // Check policy
            if (!Gate::forUser($context->user)->allows('view', $patient)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Patient.');
            }
            
            // Build query - Anamnesis Boards
            $query = PatientsAnamnesisBoard::query()
                ->where('patient_id', $patientId)
                ->with(['patient', 'user', 'team']);

            // Apply standard operations
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'done', 'created_at', 'updated_at'
            ]);
            
            // Apply standard search
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            
            // Apply standard sorting
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');
            
            // Apply standard pagination
            $this->applyStandardPagination($query, $arguments);

            // Fetch boards and filter by policy
            $boards = $query->get()->filter(function ($board) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $board);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Boards formatieren
            $boardsList = $boards->map(function($anamnesisBoard) {
                return [
                    'id' => $anamnesisBoard->id,
                    'uuid' => $anamnesisBoard->uuid,
                    'name' => $anamnesisBoard->name,
                    'description' => $anamnesisBoard->description,
                    'patient_id' => $anamnesisBoard->patient_id,
                    'patient_name' => $anamnesisBoard->patient->name,
                    'team_id' => $anamnesisBoard->team_id,
                    'user_id' => $anamnesisBoard->user_id,
                    'done' => $anamnesisBoard->done,
                    'done_at' => $anamnesisBoard->done_at?->toIso8601String(),
                    'created_at' => $anamnesisBoard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'anamnesis_boards' => $boardsList,
                'count' => count($boardsList),
                'patient_id' => $patientId,
                'patient_name' => $patient->name,
                'message' => count($boardsList) > 0 
                    ? count($boardsList) . ' Anamnesis Board(s) found for Patient "' . $patient->name . '".'
                    : 'No Anamnesis Boards found for Patient "' . $patient->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading the Anamnesis Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'anamnesis_board', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
