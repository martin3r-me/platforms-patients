<?php

namespace Platform\Patients\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk create: multiple Progress Cards in a single call.
 *
 * Purpose: reduces tool calls/iterations (LLM can create 10-50 Progress Cards in a single step).
 * REST-Idee: POST /patients/progress_cards/bulk
 */
class BulkCreateProgressCardsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.progress_cards.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/progress_cards/bulk - Body MUST {progress_cards:[{progress_board_id,progress_board_slot_id,title,body_md?}], defaults?} contain. Creates many Progress Cards (e.g. for multiple posts/captions).';
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
                        'progress_board_id' => ['type' => 'integer'],
                        'progress_board_slot_id' => ['type' => 'integer'],
                        'description' => ['type' => 'string'],
                    ],
                    'required' => [],
                ],
                'progress_cards' => [
                    'type' => 'array',
                    'description' => 'List of Progress Cards. Each element corresponds to the parameters of patients.progress_cards.POST (at least progress_board_id, progress_board_slot_id, title).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'progress_board_id' => ['type' => 'integer'],
                            'progress_board_slot_id' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'body_md' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                        ],
                        'required' => ['progress_board_id', 'progress_board_slot_id', 'title'],
                    ],
                ],
            ],
            'required' => ['progress_cards'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            $progressCards = $arguments['progress_cards'] ?? null;
            if (!is_array($progressCards) || empty($progressCards)) {
                return ToolResult::error('INVALID_ARGUMENT', 'progress_cards must be a non-empty array.');
            }

            $defaults = $arguments['defaults'] ?? [];
            if (!is_array($defaults)) {
                $defaults = [];
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new CreateProgressCardTool();

            $run = function() use ($progressCards, $defaults, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($progressCards as $idx => $sc) {
                    if (!is_array($sc)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Progress Card item must be an object.'],
                        ];
                        continue;
                    }

                    // Apply defaults without overriding explicit values
                    $payload = $defaults;
                    foreach ($sc as $k => $v) {
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
                        'requested' => count($progressCards),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error during bulk create of Progress Cards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['patients', 'progress_cards', 'bulk', 'batch', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
