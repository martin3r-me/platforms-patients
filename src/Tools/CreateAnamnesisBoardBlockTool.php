<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsAnamnesisBoardRow;
use Platform\Patients\Models\PatientsAnamnesisBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for creating AnamnesisBoardBlocks in the Patients module
 */
class CreateAnamnesisBoardBlockTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.anamnesis_board_blocks.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/anamnesis_board_rows/{row_id}/anamnesis_board_blocks - Creates a new Anamnesis Board Block. REST parameters: row_id (required, integer) - Row ID. name (optional, string) - block name. description (optional, string) - description. span (optional, integer) - column width (1-12). content_type (optional, string) - content type: "text", "image", "carousel", "video". If "text" is selected, an empty text content is automatically created.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'row_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Anamnesis Board Row (REQUIRED). Use "patients.anamnesis_board.GET" to find rows.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name of the block. If not specified, defaults to "New Block".'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Description of the block (content/text).'
                ],
                'span' => [
                    'type' => 'integer',
                    'description' => 'Column width of the block (1-12). Default: 1.'
                ],
                'content_type' => [
                    'type' => 'string',
                    'description' => 'Content type of the block. Possible values: "text", "image", "carousel", "video". If not specified, the block remains without a content type.',
                    'enum' => ['text', 'image', 'carousel', 'video']
                ],
            ],
            'required' => ['row_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            // Row finden
            $rowId = $arguments['row_id'] ?? null;
            if (!$rowId) {
                return ToolResult::error('VALIDATION_ERROR', 'row_id is required.');
            }

            $row = PatientsAnamnesisBoardRow::with('section.anamnesisBoard')->find($rowId);
            if (!$row) {
                return ToolResult::error('ROW_NOT_FOUND', 'The specified Row was not found.');
            }

            $anamnesisBoard = $row->section->anamnesisBoard;

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('update', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to create blocks for this Anamnesis Board create (policy).');
            }

            $name = $arguments['name'] ?? 'New Block';
            $span = isset($arguments['span']) ? max(1, min(12, (int)$arguments['span'])) : 1;
            $contentType = $arguments['content_type'] ?? null;

            // Create Anamnesis Board Block directly
            $block = PatientsAnamnesisBoardBlock::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'span' => $span,
                'content_type' => $contentType,
                'content_id' => null,
                'user_id' => $context->user->id,
                'team_id' => $anamnesisBoard->team_id,
                'row_id' => $row->id,
            ]);

            // Wenn content_type "text" ist, Create text content
            if ($contentType === 'text') {
                $textContent = \Platform\Patients\Models\PatientsAnamnesisBoardBlockText::create([
                    'content' => '',
                    'user_id' => $context->user->id,
                    'team_id' => $anamnesisBoard->team_id,
                ]);
                
                $block->content_type = 'text';
                $block->content_id = $textContent->id;
                $block->save();
            }

            $block->load(['row.section.anamnesisBoard', 'user', 'team', 'content']);

            $result = [
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
                'created_at' => $block->created_at->toIso8601String(),
                'message' => "Anamnesis Board Block '{$block->name}' successfully created."
            ];
            
            // Add content data if available
            if ($block->content_type === 'text' && $block->content) {
                $result['text_content'] = [
                    'id' => $block->content->id,
                    'uuid' => $block->content->uuid,
                    'content' => $block->content->content,
                ];
            }

            return ToolResult::success($result);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error creating Anamnesis Board Block: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action',
            'tags' => ['patients', 'anamnesis_board_block', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'write',
            'idempotent' => false,
            'side_effects' => ['creates'],
        ];
    }
}
