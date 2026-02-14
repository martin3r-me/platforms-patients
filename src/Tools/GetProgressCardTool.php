<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsProgressCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen einer einzelnen ProgressCard
 */
class GetProgressCardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.progress_card.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/progress_cards/{id} - Retrieves a single Progress Card. REST parameters: id (required, integer) - Progress Card ID. Use "patients.progress_cards.GET" to see available Progress Card IDs.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST parameter (required): ID of the Progress Card. Use "patients.progress_cards.GET" to see available Progress Card IDs.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Progress Card ID is required. Use "patients.progress_cards.GET" to find Progress Cards.');
            }

            // ProgressCard fetch
            $progressCard = PatientsProgressCard::with(['progressBoard', 'slot', 'user', 'team'])
                ->find($arguments['id']);

            if (!$progressCard) {
                return ToolResult::error('PROGRESS_CARD_NOT_FOUND', 'The specified Progress Card was not found. Use "patients.progress_cards.GET" to see all available Progress Cards to find.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('view', $progressCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Progress Card (Policy).');
            }

            $data = [
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

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading the Progress Card: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'progress_card', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
