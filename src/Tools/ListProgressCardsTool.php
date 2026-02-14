<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Patients\Models\PatientsProgressBoard;
use Platform\Patients\Models\PatientsProgressCard;
use Illuminate\Support\Facades\Gate;

/**
 * Tool for listing Progress Cards in the Patients module
 */
class ListProgressCardsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'patients.progress_cards.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/progress_boards/{progress_board_id}/progress_cards - Lists them. REST parameters: progress_board_id (required, integer) - Progress Board ID. filters (optional, array) - filter array. search (optional, string) - search term. sort (optional, array) - sorting. limit/offset (optional) - pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'progress_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST parameter (required): ID of the Progress Board. Use "patients.progress_boards.GET" to find Progress Boards.'
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

            $progressBoardId = $arguments['progress_board_id'] ?? null;
            if (!$progressBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'progress_board_id is required.');
            }

            $progressBoard = PatientsProgressBoard::find($progressBoardId);
            if (!$progressBoard) {
                return ToolResult::error('PROGRESS_BOARD_NOT_FOUND', 'The specified Progress Board was not found.');
            }

            // Check policy
            if (!Gate::forUser($context->user)->allows('view', $progressBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Progress Board.');
            }
            
            // Build query - Progress Cards
            $query = PatientsProgressCard::query()
                ->where('progress_board_id', $progressBoardId)
                ->with(['progressBoard', 'slot', 'user', 'team']);

            // Apply standard operations
            $this->applyStandardFilters($query, $arguments, [
                'title', 'description', 'created_at', 'updated_at'
            ]);
            
            // Apply standard search
            $this->applyStandardSearch($query, $arguments, ['title', 'body_md', 'description']);
            
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
            $cardsList = $cards->map(function($progressCard) {
                return [
                    'id' => $progressCard->id,
                    'uuid' => $progressCard->uuid,
                    'title' => $progressCard->title,
                    'body_md' => $progressCard->body_md,
                    'description' => $progressCard->description,
                    'progress_board_id' => $progressCard->progress_board_id,
                    'progress_board_name' => $progressCard->progressBoard->name,
                    'slot_id' => $progressCard->progress_board_slot_id,
                    'slot_name' => $progressCard->slot->name ?? null,
                    'team_id' => $progressCard->team_id,
                    'user_id' => $progressCard->user_id,
                    'created_at' => $progressCard->created_at->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'progress_cards' => $cardsList,
                'count' => count($cardsList),
                'progress_board_id' => $progressBoardId,
                'progress_board_name' => $progressBoard->name,
                'message' => count($cardsList) > 0 
                    ? count($cardsList) . ' Progress Card(s) found for Progress Board "' . $progressBoard->name . '".'
                    : 'No Progress Cards found for Progress Board "' . $progressBoard->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading the Progress Cards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'progress_card', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
