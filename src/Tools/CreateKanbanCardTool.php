<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsKanbanBoard;
use Platform\Patients\Models\PatientsKanbanBoardSlot;
use Platform\Patients\Models\PatientsKanbanCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for creating Kanban Cards in the Patients module
 */
class CreateKanbanCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.kanban_cards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/kanban_boards/{kanban_board_id}/slots/{slot_id}/kanban_cards - Creates a new Kanban Card. REST parameters: kanban_board_id (required, integer) - Kanban Board ID. kanban_board_slot_id (required, integer) - slot ID. title (optional, string) - title. description (optional, string) - description.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Kanban Board (REQUIRED). Use "patients.kanban_boards.GET" to find Kanban Boards.'
                ],
                'kanban_board_slot_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the slot in the Kanban Board (REQUIRED). Use "patients.kanban_board.GET" to find slots.'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Title of the Kanban Card. If not specified, defaults to "New Card".'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of the Kanban Card.'
                ],
            ],
            'required' => ['kanban_board_id', 'kanban_board_slot_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            // KanbanBoard finden
            $kanbanBoardId = $arguments['kanban_board_id'] ?? null;
            if (!$kanbanBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'kanban_board_id is required.');
            }

            $kanbanBoard = PatientsKanbanBoard::find($kanbanBoardId);
            if (!$kanbanBoard) {
                return ToolResult::error('KANBAN_BOARD_NOT_FOUND', 'The specified Kanban Board was not found.');
            }

            // Slot finden
            $slotId = $arguments['kanban_board_slot_id'] ?? null;
            if (!$slotId) {
                return ToolResult::error('VALIDATION_ERROR', 'kanban_board_slot_id is required.');
            }

            $slot = PatientsKanbanBoardSlot::find($slotId);
            if (!$slot || $slot->kanban_board_id != $kanbanBoardId) {
                return ToolResult::error('SLOT_NOT_FOUND', 'The specified slot was not found or does not belong to this Kanban Board.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('update', $kanbanBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to create cards for this Kanban Board create (policy).');
            }

            $title = $arguments['title'] ?? 'New Card';

            // Create Kanban Card directly
            $kanbanCard = PatientsKanbanCard::create([
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $kanbanBoard->team_id,
                'kanban_board_id' => $kanbanBoard->id,
                'kanban_board_slot_id' => $slot->id,
            ]);

            $kanbanCard->load(['kanbanBoard', 'slot', 'user', 'team']);

            return ToolResult::success([
                'id' => $kanbanCard->id,
                'uuid' => $kanbanCard->uuid,
                'title' => $kanbanCard->title,
                'description' => $kanbanCard->description,
                'kanban_board_id' => $kanbanCard->kanban_board_id,
                'kanban_board_name' => $kanbanCard->kanbanBoard->name,
                'slot_id' => $kanbanCard->kanban_board_slot_id,
                'slot_name' => $kanbanCard->slot->name,
                'team_id' => $kanbanCard->team_id,
                'created_at' => $kanbanCard->created_at->toIso8601String(),
                'message' => "Kanban Card '{$kanbanCard->title}' successfully created."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating Kanban Card: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'kanban_card', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
