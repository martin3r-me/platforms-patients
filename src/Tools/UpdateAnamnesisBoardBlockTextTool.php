<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsAnamnesisBoardBlock;
use Platform\Patients\Models\PatientsAnamnesisBoardBlockText;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for updating text content for Anamnesis Board Blocks
 */
class UpdateAnamnesisBoardBlockTextTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.anamnesis_board_block_texts.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/anamnesis_board_blocks/{block_id}/texts - Updates text content of an Anamnesis Board Block. REST parameters: anamnesis_board_block_id (required, integer) - block ID. content (optional, string) - text content (Markdown).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'anamnesis_board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Anamnesis Board Block (REQUIRED). The block must have the content type "text".'
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Text content (Markdown). If not specified, the content remains unchanged.'
                ],
            ],
            'required' => ['anamnesis_board_block_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $blockId = $arguments['anamnesis_board_block_id'] ?? null;
            if (!$blockId) {
                return ToolResult::error('VALIDATION_ERROR', 'anamnesis_board_block_id is required.');
            }

            $block = PatientsAnamnesisBoardBlock::with('row.section.anamnesisBoard', 'content')->find($blockId);
            if (!$block) {
                return ToolResult::error('BLOCK_NOT_FOUND', 'The specified Anamnesis Board Block was not found.');
            }

            $anamnesisBoard = $block->row->section->anamnesisBoard;

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('update', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this text content edit (policy).');
            }

            // Check if block has text content
            if ($block->content_type !== 'text' || !$block->content) {
                return ToolResult::error('NO_TEXT_CONTENT', 'This block has no text content. Use "patients.anamnesis_board_block_texts.POST" to create text content.');
            }

            $textContent = $block->content;

            // Update content
            if (isset($arguments['content'])) {
                $textContent->update([
                    'content' => $arguments['content'],
                ]);
            }

            $textContent->refresh();

            return ToolResult::success([
                'anamnesis_board_block_id' => $block->id,
                'text_content_id' => $textContent->id,
                'text_content_uuid' => $textContent->uuid,
                'content' => $textContent->content,
                'updated_at' => $textContent->updated_at->toIso8601String(),
                'message' => "Text content for block '{$block->name}' successfully updated."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating text content: ' . $e->getMessage());
        }
    }
}
