<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Patients\Models\PatientsPatient;
use Platform\Patients\Models\PatientsKanbanBoard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool for listing Kanban Boards in the Patients module
 */
class ListKanbanBoardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'patients.kanban_boards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/{patient_id}/kanban_boards - Lists them. REST parameters: patient_id (required, integer) - patient ID. filters (optional, array) - filter array. search (optional, string) - search term. sort (optional, array) - sorting. limit/offset (optional) - pagination.';
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

            // Build query - Kanban Boards
            $query = PatientsKanbanBoard::query()
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
            $boardsList = $boards->map(function($kanbanBoard) {
                return [
                    'id' => $kanbanBoard->id,
                    'uuid' => $kanbanBoard->uuid,
                    'name' => $kanbanBoard->name,
                    'description' => $kanbanBoard->description,
                    'patient_id' => $kanbanBoard->patient_id,
                    'patient_name' => $kanbanBoard->patient->name,
                    'team_id' => $kanbanBoard->team_id,
                    'user_id' => $kanbanBoard->user_id,
                    'done' => $kanbanBoard->done,
                    'done_at' => $kanbanBoard->done_at?->toIso8601String(),
                    'created_at' => $kanbanBoard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'kanban_boards' => $boardsList,
                'count' => count($boardsList),
                'patient_id' => $patientId,
                'patient_name' => $patient->name,
                'message' => count($boardsList) > 0
                    ? count($boardsList) . ' Kanban Board(s) found for Patient "' . $patient->name . '".'
                    : 'No Kanban Boards found for Patient "' . $patient->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading the Kanban Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'kanban_board', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
