<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsAnamnesisBoardBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for deleting Anamnesis Board Blocks in the Patients module
 */
class DeleteAnamnesisBoardBlockTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.anamnesis_board_blocks.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /patients/anamnesis_board_blocks/{id} - Deletes aen Anamnesis Board Block. REST parameters: anamnesis_board_block_id (required, integer) - Anamnesis Board block ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'anamnesis_board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Anamnesis Board Blocks (REQUIRED). Use "patients.anamnesis_board_blocks.GET" to find Anamnesis Board Blocks.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Confirmation that the Anamnesis Board Block should really be deleted.'
                ]
            ],
            'required' => ['anamnesis_board_block_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Use standardized ID validation
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'anamnesis_board_block_id',
                PatientsAnamnesisBoardBlock::class,
                'ANAMNESIS_BOARD_BLOCK_NOT_FOUND',
                'The specified Anamnesis Board Block was not found.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $block = $validation['model'];
            $block->load('row.section.anamnesisBoard');
            $anamnesisBoard = $block->row->section->anamnesisBoard;
            
            // Check policy
            try {
                Gate::forUser($context->user)->authorize('delete', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Anamnesis Board Block delete (policy).');
            }

            $blockName = $block->name;
            $blockId = $block->id;
            $anamnesisBoardId = $anamnesisBoard->id;
            $teamId = $block->team_id;

            // Delete Anamnesis Board Block
            $block->delete();

            // Invalidate cache
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('patients.anamnesis_board_blocks.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'anamnesis_board_block_id' => $blockId,
                'anamnesis_board_block_name' => $blockName,
                'anamnesis_board_id' => $anamnesisBoardId,
                'message' => "Anamnesis Board Block '{$blockName}' wurde successfully deleted."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting Anamnesis Board Block: ' . $e->getMessage());
        }
    }
}
