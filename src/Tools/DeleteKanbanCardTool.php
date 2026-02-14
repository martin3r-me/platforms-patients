<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsKanbanCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for deleting Kanban Cards in the Patients module
 */
class DeleteKanbanCardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.kanban_cards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /patients/kanban_cards/{id} - Deletes ae Kanban Card. REST parameters: kanban_card_id (required, integer) - Kanban Card ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'kanban_card_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Kanban Card (REQUIRED). Use "patients.kanban_cards.GET" to find Kanban Cards.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Confirmation that the Kanban Card should really be deleted.'
                ]
            ],
            'required' => ['kanban_card_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Use standardized ID validation
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'kanban_card_id',
                PatientsKanbanCard::class,
                'KANBAN_CARD_NOT_FOUND',
                'The specified Kanban Card was not found.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $kanbanCard = $validation['model'];

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('delete', $kanbanCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Kanban Card delete (policy).');
            }

            $kanbanCardTitle = $kanbanCard->title;
            $kanbanCardId = $kanbanCard->id;
            $kanbanBoardId = $kanbanCard->kanban_board_id;
            $teamId = $kanbanCard->team_id;

            // Delete Kanban Card
            $kanbanCard->delete();

            // Invalidate cache
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('patients.kanban_cards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'kanban_card_id' => $kanbanCardId,
                'kanban_card_title' => $kanbanCardTitle,
                'kanban_board_id' => $kanbanBoardId,
                'message' => "Kanban Card '{$kanbanCardTitle}' wurde successfully deleted."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting Kanban Card: ' . $e->getMessage());
        }
    }
}
