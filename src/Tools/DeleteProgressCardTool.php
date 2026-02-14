<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsProgressCard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for deleting Progress Cards in the Patients module
 */
class DeleteProgressCardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.progress_cards.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /patients/progress_cards/{id} - Deletes ae Progress Card. REST parameters: progress_card_id (required, integer) - Progress Card ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'progress_card_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Progress Card (REQUIRED). Use "patients.progress_cards.GET" to find Progress Cards.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Confirmation that the Progress Card should really be deleted.'
                ]
            ],
            'required' => ['progress_card_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Use standardized ID validation
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'progress_card_id',
                PatientsProgressCard::class,
                'PROGRESS_CARD_NOT_FOUND',
                'The specified Progress Card was not found.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $progressCard = $validation['model'];
            
            // Check policy
            try {
                Gate::forUser($context->user)->authorize('delete', $progressCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Progress Card delete (policy).');
            }

            $progressCardTitle = $progressCard->title;
            $progressCardId = $progressCard->id;
            $progressBoardId = $progressCard->progress_board_id;
            $teamId = $progressCard->team_id;

            // Delete Progress Card
            $progressCard->delete();

            // Invalidate cache
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('patients.progress_cards.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'progress_card_id' => $progressCardId,
                'progress_card_title' => $progressCardTitle,
                'progress_board_id' => $progressBoardId,
                'message' => "Progress Card '{$progressCardTitle}' wurde successfully deleted."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting Progress Card: ' . $e->getMessage());
        }
    }
}
