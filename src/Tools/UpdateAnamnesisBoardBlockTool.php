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
 * Tool zum Bearbeiten von AnamnesisBoardBlocks
 */
class UpdateAnamnesisBoardBlockTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.anamnesis_board_blocks.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/anamnesis_board_blocks/{id} - Updates an Anamnesis Board Block. REST parameters: anamnesis_board_block_id (required, integer) - Anamnesis Board block ID. name (optional, string) - Name. description (optional, string) - description (content/text). span (optional, integer) - column width (1-12). content_type (optional, string) - Change content type: "text", "image", "carousel", "video". If "text" is selected, an empty text content is automatically created (existing content is deleted).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'anamnesis_board_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the Anamnesis Board Block (REQUIRED). Use "patients.anamnesis_board_blocks.GET" to find Anamnesis Board Blocks.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Name of the block.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Description of the block (content/text).'
                ],
                'span' => [
                    'type' => 'integer',
                    'description' => 'Optional: Column width of the block (1-12).'
                ],
                'content_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Content type of the block. Possible values: "text", "image", "carousel", "video". If set, the content type is changed (existing content is deleted).',
                    'enum' => ['text', 'image', 'carousel', 'video']
                ],
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
                Gate::forUser($context->user)->authorize('update', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to this Anamnesis Board Block edit (policy).');
            }

            // Collect update data
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['span'])) {
                $updateData['span'] = max(1, min(12, (int)$arguments['span']));
            }

            // Change content type
            if (isset($arguments['content_type'])) {
                $newContentType = $arguments['content_type'];
                
                // If content already exists, delete it
                if ($block->content) {
                    $block->content->delete();
                }
                
                // Create new content if type is "text"
                if ($newContentType === 'text') {
                    $textContent = \Platform\Patients\Models\PatientsAnamnesisBoardBlockText::create([
                        'content' => '',
                        'user_id' => $context->user?->id ?? auth()->id(),
                        'team_id' => $anamnesisBoard->team_id,
                    ]);
                    
                    $updateData['content_type'] = 'text';
                    $updateData['content_id'] = $textContent->id;
                } else {
                    $updateData['content_type'] = $newContentType;
                    $updateData['content_id'] = null;
                }
            }

            // Update Anamnesis Board Block
            if (!empty($updateData)) {
                $block->update($updateData);
            }

            $block->refresh();
            $block->load(['row.section.anamnesisBoard', 'user', 'team', 'content']);

            $result = [
                'anamnesis_board_block_id' => $block->id,
                'name' => $block->name,
                'description' => $block->description,
                'span' => $block->span,
                'content_type' => $block->content_type,
                'content_id' => $block->content_id,
                'anamnesis_board_id' => $anamnesisBoard->id,
                'anamnesis_board_name' => $anamnesisBoard->name,
                'updated_at' => $block->updated_at->toIso8601String(),
                'message' => "Anamnesis Board Block '{$block->name}' successfully updated."
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
            return ToolResult::error('EXECUTION_ERROR', 'Error updating Anamnesis Board Block: ' . $e->getMessage());
        }
    }
}
