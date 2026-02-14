<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Patients\Models\PatientsAnamnesisBoard;
use Platform\Patients\Models\PatientsAnamnesisBoardBlock;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von AnamnesisBoardBlocks im Patients-Modul
 */
class ListAnamnesisBoardBlocksTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'patients.anamnesis_board_blocks.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/anamnesis_boards/{anamnesis_board_id}/anamnesis_board_blocks - Lists them. REST parameters: anamnesis_board_id (required, integer) - Anamnesis Board ID. filters (optional, array) - filter array. search (optional, string) - search term. sort (optional, array) - sorting. limit/offset (optional) - pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'anamnesis_board_id' => [
                        'type' => 'integer',
                        'description' => 'REST parameter (required): ID of the Anamnesis Board. Use "patients.anamnesis_boards.GET" to find Anamnesis Boards.'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            $anamnesisBoardId = $arguments['anamnesis_board_id'] ?? null;
            if (!$anamnesisBoardId) {
                return ToolResult::error('VALIDATION_ERROR', 'anamnesis_board_id is required.');
            }

            $anamnesisBoard = PatientsAnamnesisBoard::find($anamnesisBoardId);
            if (!$anamnesisBoard) {
                return ToolResult::error('ANAMNESIS_BOARD_NOT_FOUND', 'The specified Anamnesis Board was not found.');
            }

            // Check policy
            if (!Gate::forUser($context->user)->allows('view', $anamnesisBoard)) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Anamnesis Board.');
            }
            
            // Build query - Anamnesis Board Blocks via Sections -> Rows
            $query = PatientsAnamnesisBoardBlock::query()
                ->whereHas('row.section', function($q) use ($anamnesisBoardId) {
                    $q->where('anamnesis_board_id', $anamnesisBoardId);
                })
                ->with(['row.section.anamnesisBoard', 'user', 'team', 'content']);

            // Apply standard operations
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'created_at', 'updated_at'
            ]);
            
            // Apply standard search
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            
            // Apply standard sorting
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at', 'order'
            ], 'order', 'asc');
            
            // Apply standard pagination
            $this->applyStandardPagination($query, $arguments);

            // Blocks fetch
            $blocks = $query->get();

            // Blocks formatieren
            $blocksList = $blocks->map(function($block) {
                $data = [
                    'id' => $block->id,
                    'uuid' => $block->uuid,
                    'name' => $block->name,
                    'description' => $block->description,
                    'span' => $block->span,
                    'content_type' => $block->content_type,
                    'content_id' => $block->content_id,
                    'row_id' => $block->row_id,
                    'anamnesis_board_id' => $block->row->section->anamnesis_board_id,
                    'anamnesis_board_name' => $block->row->section->anamnesisBoard->name,
                    'team_id' => $block->team_id,
                    'user_id' => $block->user_id,
                    'created_at' => $block->created_at->toIso8601String(),
                ];
                
                // Add content data if available
                if ($block->content_type === 'text' && $block->content) {
                    $data['text_content_preview'] = mb_substr($block->content->content ?? '', 0, 100);
                }
                
                return $data;
            })->values()->toArray();

            return ToolResult::success([
                'anamnesis_board_blocks' => $blocksList,
                'count' => count($blocksList),
                'anamnesis_board_id' => $anamnesisBoardId,
                'anamnesis_board_name' => $anamnesisBoard->name,
                'message' => count($blocksList) > 0 
                    ? count($blocksList) . ' Anamnesis Board Block(s) found for Anamnesis Board "' . $anamnesisBoard->name . '".'
                    : 'No Anamnesis Board Blocks found for Anamnesis Board "' . $anamnesisBoard->name . '".'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading the Anamnesis Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'anamnesis_board_block', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
