<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsProgressBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen ProgressBoards
 */
class GetProgressBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.progress_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/progress_boards/{id} - Retrieves a single Progress Board. REST parameters: id (required, integer) - Progress Board ID. Use "patients.progress_boards.GET" to see available Progress Board IDs.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST parameter (required): ID of the Progress Board. Use "patients.progress_boards.GET" to see available Progress Board IDs.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Progress Board ID is required. Use "patients.progress_boards.GET" to find Progress Boards.');
            }

            // ProgressBoard fetch
            $progressBoard = PatientsProgressBoard::with(['patient', 'user', 'team', 'slots', 'cards'])
                ->find($arguments['id']);

            if (!$progressBoard) {
                return ToolResult::error('PROGRESS_BOARD_NOT_FOUND', 'The specified Progress Board was not found. Use "patients.progress_boards.GET" to see all available Progress Boards to find.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('view', $progressBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Progress Board (Policy).');
            }

            $data = [
                'id' => $progressBoard->id,
                'uuid' => $progressBoard->uuid,
                'name' => $progressBoard->name,
                'description' => $progressBoard->description,
                'patient_id' => $progressBoard->patient_id,
                'patient_name' => $progressBoard->patient->name,
                'team_id' => $progressBoard->team_id,
                'user_id' => $progressBoard->user_id,
                'done' => $progressBoard->done,
                'done_at' => $progressBoard->done_at?->toIso8601String(),
                'slots_count' => $progressBoard->slots->count(),
                'cards_count' => $progressBoard->cards->count(),
                'created_at' => $progressBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading Progress Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'progress_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
