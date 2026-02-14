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
 * Tool zum Abrufen eines einzelnen AnamnesisBoardBlocks
 */
class GetAnamnesisBoardBlockTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.anamnesis_board_block.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/anamnesis_board_blocks/{id} - Retrieves a single Anamnesis Board Block. REST parameters: id (required, integer) - Anamnesis Board block ID. Use "patients.anamnesis_board_blocks.GET" to see available Anamnesis Board block IDs.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST parameter (required): ID of the Anamnesis Board Block. Use "patients.anamnesis_board_blocks.GET" to see available Anamnesis Board block IDs.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Anamnesis Board block ID is required. Use "patients.anamnesis_board_blocks.GET" to find Anamnesis Board Blocks.');
            }

            // AnamnesisBoardBlock fetch
            $block = PatientsAnamnesisBoardBlock::with(['row.section.anamnesisBoard', 'user', 'team'])
                ->find($arguments['id']);

            if (!$block) {
                return ToolResult::error('ANAMNESIS_BOARD_BLOCK_NOT_FOUND', 'The specified Anamnesis Board Block was not found. Use "patients.anamnesis_board_blocks.GET" to see all available Anamnesis Board Blocks to find.');
            }

            $anamnesisBoard = $block->row->section->anamnesisBoard;

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('view', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Anamnesis Board Block (Policy).');
            }

            $block->load('content');
            
            $data = [
                'id' => $block->id,
                'uuid' => $block->uuid,
                'name' => $block->name,
                'description' => $block->description,
                'span' => $block->span,
                'content_type' => $block->content_type,
                'content_id' => $block->content_id,
                'row_id' => $block->row_id,
                'anamnesis_board_id' => $anamnesisBoard->id,
                'anamnesis_board_name' => $anamnesisBoard->name,
                'team_id' => $block->team_id,
                'user_id' => $block->user_id,
                'created_at' => $block->created_at->toIso8601String(),
            ];
            
            // Add content data if available
            if ($block->content_type === 'text' && $block->content) {
                $data['content'] = [
                    'id' => $block->content->id,
                    'uuid' => $block->content->uuid,
                    'content' => $block->content->content,
                ];
            }

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading Anamnesis Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'anamnesis_board_block', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
