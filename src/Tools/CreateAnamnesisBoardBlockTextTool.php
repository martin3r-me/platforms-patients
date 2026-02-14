<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsAnamnesisBoardBlock;
use Platform\Patients\Models\PatientsAnamnesisBoardBlockText;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for creating text content for Anamnesis Board Blocks
 */
class CreateAnamnesisBoardBlockTextTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.anamnesis_board_block_texts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/anamnesis_board_blocks/{block_id}/texts - Creates text content for an Anamnesis Board Block. REST parameters: anamnesis_board_block_id (required, integer) - block ID. content (optional, string) - text content (Markdown).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'anamnesis_board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Anamnesis Board Block (REQUIRED). The block must already have the content type "text" or it will be set automatically.'
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Text content (Markdown). If not specified, an empty text is created.'
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

            $block = PatientsAnamnesisBoardBlock::with('row.section.anamnesisBoard')->find($blockId);
            if (!$block) {
                return ToolResult::error('BLOCK_NOT_FOUND', 'The specified Anamnesis Board Block was not found.');
            }

            $anamnesisBoard = $block->row->section->anamnesisBoard;

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('update', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to create text content for this block create (policy).');
            }

            // If text content already exists, return error
            if ($block->content_type === 'text' && $block->content) {
                return ToolResult::error('CONTENT_EXISTS', 'This block already has text content. Use "patients.anamnesis_board_block_texts.PUT" to update the content.');
            }

            // Create text content
            $textContent = PatientsAnamnesisBoardBlockText::create([
                'content' => $arguments['content'] ?? '',
                'user_id' => $context->user->id,
                'team_id' => $anamnesisBoard->team_id,
            ]);

            // Link block with text content
            $block->content_type = 'text';
            $block->content_id = $textContent->id;
            $block->save();

            $block->refresh();
            $block->load('content');

            return ToolResult::success([
                'anamnesis_board_block_id' => $block->id,
                'text_content_id' => $textContent->id,
                'text_content_uuid' => $textContent->uuid,
                'content' => $textContent->content,
                'created_at' => $textContent->created_at->toIso8601String(),
                'message' => "Text content for block '{$block->name}' successfully created."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating text content: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'anamnesis_board_block', 'text', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
