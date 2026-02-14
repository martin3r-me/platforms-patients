<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsKanbanCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen einer einzelnen KanbanCard
 */
class GetKanbanCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.kanban_card.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/kanban_cards/{id} - Retrieves a single Kanban Card. REST parameters: id (required, integer) - Kanban Card ID. Use "patients.kanban_cards.GET" to see available Kanban Card IDs.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST parameter (required): ID of the Kanban Card. Use "patients.kanban_cards.GET" to see available Kanban Card IDs.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Kanban Card ID is required. Use "patients.kanban_cards.GET" to find Kanban Cards.');
            }

            // KanbanCard fetch
            $kanbanCard = PatientsKanbanCard::with(['kanbanBoard', 'slot', 'user', 'team'])
                ->find($arguments['id']);

            if (!$kanbanCard) {
                return ToolResult::error('KANBAN_CARD_NOT_FOUND', 'The specified Kanban Card was not found. Use "patients.kanban_cards.GET" to see all available Kanban Cards to find.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('view', $kanbanCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Kanban Card (Policy).');
            }

            $data = [
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

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading the Kanban Card: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'kanban_card', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
