<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Patients\Models\PatientsKanbanBoard;
use Platform\Patients\Models\PatientsKanbanCard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool for listing Kanban Cards in the Patients module
 */
class ListKanbanCardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'patients.kanban_cards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/kanban_boards/{kanban_board_id}/kanban_cards - Lists them. REST parameters: kanban_board_id (required, integer) - Kanban Board ID. filters (optional, array) - filter array. search (optional, string) - search term. sort (optional, array) - sorting. limit/offset (optional) - pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'kanban_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST parameter (required): ID of the Kanban Board. Use "patients.kanban_boards.GET" to find Kanban Boards.'
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

            $kanbanBoardId = $arguments['kanban_board_id'] ?? null;
            if (!$kanbanBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'kanban_board_id is required.');
            }

            $kanbanBoard = PatientsKanbanBoard::find($kanbanBoardId);
            if (!$kanbanBoard) {
                return ToolResult::error('KANBAN_BOARD_NOT_FOUND', 'The specified Kanban Board was not found.');
            }

            // Check policy
            if (!Gate::forUser($context->user)->allows('view', $kanbanBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Kanban Board.');
            }

            // Build query - Kanban Cards
            $query = PatientsKanbanCard::query()
                ->where('kanban_board_id', $kanbanBoardId)
                ->with(['kanbanBoard', 'slot', 'user', 'team']);

            // Apply standard operations
            $this->applyStandardFilters($query, $arguments, [
                'title', 'description', 'created_at', 'updated_at'
            ]);

            // Apply standard search
            $this->applyStandardSearch($query, $arguments, ['title', 'description']);

            // Apply standard sorting
            $this->applyStandardSort($query, $arguments, [
                'title', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');

            // Apply standard pagination
            $this->applyStandardPagination($query, $arguments);

            // Fetch cards and filter by policy
            $cards = $query->get()->filter(function ($card) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $card);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Cards formatieren
            $cardsList = $cards->map(function($kanbanCard) {
                return [
                    'id' => $kanbanCard->id,
                    'uuid' => $kanbanCard->uuid,
                    'title' => $kanbanCard->title,
                    'description' => $kanbanCard->description,
                    'kanban_board_id' => $kanbanCard->kanban_board_id,
                    'kanban_board_name' => $kanbanCard->kanbanBoard->name,
                    'slot_id' => $kanbanCard->kanban_board_slot_id,
                    'slot_name' => $kanbanCard->slot->name ?? null,
                    'team_id' => $kanbanCard->team_id,
                    'user_id' => $kanbanCard->user_id,
                    'created_at' => $kanbanCard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'kanban_cards' => $cardsList,
                'count' => count($cardsList),
                'kanban_board_id' => $kanbanBoardId,
                'kanban_board_name' => $kanbanBoard->name,
                'message' => count($cardsList) > 0
                    ? count($cardsList) . ' Kanban Card(s) found for Kanban Board "' . $kanbanBoard->name . '".'
                    : 'No Kanban Cards found for Kanban Board "' . $kanbanBoard->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading the Kanban Cards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'kanban_card', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
