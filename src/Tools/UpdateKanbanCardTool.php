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
 * Tool zum Bearbeiten von KanbanCards
 */
class UpdateKanbanCardTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.kanban_cards.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/kanban_cards/{id} - Updates a Kanban Card. REST parameters: kanban_card_id (required, integer) - Kanban Card ID. title (optional, string) - title. description (optional, string) - description.';
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
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Title of the Kanban Card.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Description of the Kanban Card.'
                ],
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
                Gate::forUser($context->user)->authorize('update', $kanbanCard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Kanban Card edit (policy).');
            }

            // Collect update data
            $updateData = [];

            if (isset($arguments['title'])) {
                $updateData['title'] = $arguments['title'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            // Update Kanban Card
            if (!empty($updateData)) {
                $kanbanCard->update($updateData);
            }

            $kanbanCard->refresh();
            $kanbanCard->load(['kanbanBoard', 'slot', 'user', 'team']);

            return ToolResult::success([
                'kanban_card_id' => $kanbanCard->id,
                'title' => $kanbanCard->title,
                'description' => $kanbanCard->description,
                'kanban_board_id' => $kanbanCard->kanban_board_id,
                'kanban_board_name' => $kanbanCard->kanbanBoard->name,
                'updated_at' => $kanbanCard->updated_at->toIso8601String(),
                'message' => "Kanban Card '{$kanbanCard->title}' successfully updated."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating Kanban Card: ' . $e->getMessage());
        }
    }
}
