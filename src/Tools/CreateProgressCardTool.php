<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsProgressBoard;
use Platform\Patients\Models\PatientsProgressBoardSlot;
use Platform\Patients\Models\PatientsProgressCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for creating Progress Cards in the Patients module
 */
class CreateProgressCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.progress_cards.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/progress_boards/{progress_board_id}/slots/{slot_id}/progress_cards - Creates a new Progress Card. REST parameters: progress_board_id (required, integer) - Progress Board ID. progress_board_slot_id (required, integer) - slot ID. title (optional, string) - title. body_md (optional, string) - Markdown content. description (optional, string) - description.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'progress_board_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Progress Board (REQUIRED). Use "patients.progress_boards.GET" to find Progress Boards.'
                ],
                'progress_board_slot_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the slot in the Progress Board (REQUIRED). Use "patients.progress_board.GET" to find slots.'
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Title of the Progress Card. If not specified, defaults to "New Progress Card".'
                ],
                'body_md' => [
                    'type' => 'string',
                    'description' => 'Markdown content of the Progress Card (caption/text).'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of the Progress Card.'
                ],
            ],
            'required' => ['progress_board_id', 'progress_board_slot_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            // ProgressBoard finden
            $progressBoardId = $arguments['progress_board_id'] ?? null;
            if (!$progressBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'progress_board_id is required.');
            }

            $progressBoard = PatientsProgressBoard::find($progressBoardId);
            if (!$progressBoard) {
                return ToolResult::error('PROGRESS_BOARD_NOT_FOUND', 'The specified Progress Board was not found.');
            }

            // Slot finden
            $slotId = $arguments['progress_board_slot_id'] ?? null;
            if (!$slotId) {
                return ToolResult::error('VALIDATION_ERROR', 'progress_board_slot_id is required.');
            }

            $slot = PatientsProgressBoardSlot::find($slotId);
            if (!$slot || $slot->progress_board_id != $progressBoardId) {
                return ToolResult::error('SLOT_NOT_FOUND', 'The specified slot was not found or does not belong to this Progress Board.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('update', $progressBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to create cards for this Progress Board create (policy).');
            }

            $title = $arguments['title'] ?? 'New Progress Card';

            // Create Progress Card directly
            $progressCard = PatientsProgressCard::create([
                'title' => $title,
                'body_md' => $arguments['body_md'] ?? null,
                'description' => $arguments['description'] ?? null,
                'user_id' => $context->user->id,
                'team_id' => $progressBoard->team_id,
                'progress_board_id' => $progressBoard->id,
                'progress_board_slot_id' => $slot->id,
            ]);

            $progressCard->load(['progressBoard', 'slot', 'user', 'team']);

            return ToolResult::success([
                'id' => $progressCard->id,
                'uuid' => $progressCard->uuid,
                'title' => $progressCard->title,
                'body_md' => $progressCard->body_md,
                'description' => $progressCard->description,
                'progress_board_id' => $progressCard->progress_board_id,
                'progress_board_name' => $progressCard->progressBoard->name,
                'slot_id' => $progressCard->progress_board_slot_id,
                'slot_name' => $progressCard->slot->name,
                'team_id' => $progressCard->team_id,
                'created_at' => $progressCard->created_at->toIso8601String(),
                'message' => "Progress Card '{$progressCard->title}' successfully created."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating Progress Card: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'progress_card', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
