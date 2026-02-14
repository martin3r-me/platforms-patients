<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsAnamnesisBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for deleting text content of an Anamnesis Board Block
 */
class DeleteAnamnesisBoardBlockTextTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.anamnesis_board_block_texts.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /patients/anamnesis_board_blocks/{block_id}/texts - Deletes text content of an Anamnesis Board Block. REST parameters: anamnesis_board_block_id (required, integer) - block ID.';
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
            ],
            'required' => ['anamnesis_board_block_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

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
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this text content delete (policy).');
            }

            // Check if block has text content
            if ($block->content_type !== 'text' || !$block->content) {
                return ToolResult::error('NO_TEXT_CONTENT', 'This block has no text content.');
            }

            $textContent = $block->content;
            $blockName = $block->name;

            // Delete text content
            $textContent->delete();

            // Reset block content type
            $block->content_type = null;
            $block->content_id = null;
            $block->save();

            return ToolResult::success([
                'anamnesis_board_block_id' => $block->id,
                'message' => "Text content for block '{$blockName}' successfully deleted."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting text content: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'anamnesis_board_block', 'text', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'destructive',
            'idempotent' => false,
            'side_effects' => ['deletes'],
        ];
    }
}
