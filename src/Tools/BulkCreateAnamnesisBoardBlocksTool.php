<?php

namespace Platform\Patients\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk create: multiple Anamnesis Board Blocks in a single call.
 *
 * Purpose: reduces tool calls/iterations (LLM can create 10-50 Anamnesis Board Blocks in a single step).
 * REST-Idee: POST /patients/anamnesis_board_blocks/bulk
 */
class BulkCreateAnamnesisBoardBlocksTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.anamnesis_board_blocks.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/anamnesis_board_blocks/bulk - Body MUST {anamnesis_board_blocks:[{row_id,name,description?}], defaults?} contain. Creates many Anamnesis Board Blocks (e.g. for multiple contents/texts).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: If true, all creates are executed in a DB transaction (rolled back on error). Default: false.',
                ],
                'defaults' => [
                    'type' => 'object',
                    'description' => 'Optional: Default values applied to each item (can be overridden per item).',
                    'properties' => [
                        'row_id' => ['type' => 'integer'],
                        'span' => ['type' => 'integer'],
                    ],
                    'required' => [],
                ],
                'anamnesis_board_blocks' => [
                    'type' => 'array',
                    'description' => 'List of Anamnesis Board Blocks. Each element corresponds to the parameters of patients.anamnesis_board_blocks.POST (at least row_id, name).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'row_id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'span' => ['type' => 'integer'],
                        ],
                        'required' => ['row_id', 'name'],
                    ],
                ],
            ],
            'required' => ['anamnesis_board_blocks'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            $blocks = $arguments['anamnesis_board_blocks'] ?? null;
            if (!is_array($blocks) || empty($blocks)) {
                return ToolResult::error('INVALID_ARGUMENT', 'anamnesis_board_blocks must be a non-empty array.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new CreateAnamnesisBoardBlockTool();

            $run = function() use ($blocks, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($blocks as $idx => $b) {
                    if (!is_array($b)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Anamnesis Board Block item must be an object.'],
                        ];
                        continue;
                    }

                    // Apply defaults without overriding explicit values
                    $payload = $defaults;
                    foreach ($b as $k => $v) {
                        $payload[$k] = $v;
                    }

                    $res = $singleTool->execute($payload, $context);
                    if ($res->success) {
                        $okCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => true,
                            'data' => $res->data,
                        ];
                    } else {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => [
                                'code' => $res->errorCode,
                                'message' => $res->error,
                            ],
                        ];
                    }
                }

                return [
                    'results' => $results,
                    'summary' => [
                        'requested' => count($blocks),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error during bulk create of Anamnesis Board Blocks: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['patients', 'anamnesis_board_blocks', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
