<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsKanbanBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen KanbanBoards
 */
class GetKanbanBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.kanban_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/kanban_boards/{id} - Retrieves a single Kanban Board. REST parameters: id (required, integer) - Kanban Board ID. Use "patients.kanban_boards.GET" to see available Kanban Board IDs.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST parameter (required): ID of the Kanban Board. Use "patients.kanban_boards.GET" to see available Kanban Board IDs.'
                ]
            ],
            'required' => ['id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            if (empty($arguments['id'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Kanban Board ID is required. Use "patients.kanban_boards.GET" to find Kanban Boards.');
            }

            // KanbanBoard fetch
            $kanbanBoard = PatientsKanbanBoard::with(['patient', 'user', 'team', 'slots', 'cards'])
                ->find($arguments['id']);

            if (!$kanbanBoard) {
                return ToolResult::error('KANBAN_BOARD_NOT_FOUND', 'The specified Kanban Board was not found. Use "patients.kanban_boards.GET" to see all available Kanban Boards to find.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('view', $kanbanBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Kanban Board (Policy).');
            }

            $data = [
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
                'slots_count' => $kanbanBoard->slots->count(),
                'cards_count' => $kanbanBoard->cards->count(),
                'created_at' => $kanbanBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading Kanban Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'kanban_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
