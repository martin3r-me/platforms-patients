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
 * Tool zum Bearbeiten von ProgressCards
 */
class UpdateProgressCardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.progress_cards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/progress_cards/{id} - Updates a Progress Card. REST parameters: progress_card_id (required, integer) - Progress Card ID. title (optional, string) - title. body_md (optional, string) - Markdown content (caption/text). description (optional, string) - description.';
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
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Title of the Progress Card.'
                ],
                'body_md' => [
                    'type' => 'string',
                    'description' => 'Optional: Markdown content of the Progress Card (caption/text).'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Description of the Progress Card.'
                ],
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
                Gate::forUser($context->user)->authorize('update', $progressCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Progress Card edit (policy).');
            }

            // Collect update data
            $updateData = [];

            if (isset($arguments['title'])) {
                $updateData['title'] = $arguments['title'];
            }

            if (isset($arguments['body_md'])) {
                $updateData['body_md'] = $arguments['body_md'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            // Update Progress Card
            if (!empty($updateData)) {
                $progressCard->update($updateData);
            }

            $progressCard->refresh();
            $progressCard->load(['progressBoard', 'slot', 'user', 'team']);

            return ToolResult::success([
                'progress_card_id' => $progressCard->id,
                'title' => $progressCard->title,
                'body_md' => $progressCard->body_md,
                'description' => $progressCard->description,
                'progress_board_id' => $progressCard->progress_board_id,
                'progress_board_name' => $progressCard->progressBoard->name,
                'updated_at' => $progressCard->updated_at->toIso8601String(),
                'message' => "Progress Card '{$progressCard->title}' successfully updated."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating Progress Card: ' . $e->getMessage());
        }
    }
}
