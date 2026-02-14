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
 * Tool zum Abrufen von Text-Content eines Anamnesis Board Blocks
 */
class GetAnamnesisBoardBlockTextTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.anamnesis_board_block_texts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/anamnesis_board_blocks/{block_id}/texts - Ruft Text-Content eines Anamnesis Board Blocks. REST parameters: anamnesis_board_block_id (required, integer) - block ID.';
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
                Gate::forUser($context->user)->authorize('view', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this text content (Policy).');
            }

            // Check if block has text content
            if ($block->content_type !== 'text' || !$block->content) {
                return ToolResult::error('NO_TEXT_CONTENT', 'This block has no text content.');
            }

            $textContent = $block->content;

            return ToolResult::success([
                'anamnesis_board_block_id' => $block->id,
                'anamnesis_board_block_name' => $block->name,
                'text_content_id' => $textContent->id,
                'text_content_uuid' => $textContent->uuid,
                'content' => $textContent->content,
                'content_length' => strlen($textContent->content ?? ''),
                'word_count' => str_word_count($textContent->content ?? ''),
                'created_at' => $textContent->created_at->toIso8601String(),
                'updated_at' => $textContent->updated_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading Text-Contents: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'anamnesis_board_block', 'text', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
