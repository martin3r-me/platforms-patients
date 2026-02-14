<?php

namespace Platform\Patients\Tools;

use Illuminate\Support\Facades\DB;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Bulk update: multiple Progress Cards in a single call.
 *
 * Purpose: reduces tool calls/iterations (LLM can handle 10+ updates in a single step).
 * REST-Idee: PUT /patients/progress_cards/bulk
 */
class BulkUpdateProgressCardsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.progress_cards.bulk.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/progress_cards/bulk - Updates multiple Progress Cards in a single request. Useful for batch operations (e.g. updating multiple captions/texts) without many tool calls.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'atomic' => [
                    'type' => 'boolean',
                    'description' => 'Optional: If true, all updates are executed in a DB transaction (rolled back on error). Default: false.',
                ],
                'updates' => [
                    'type' => 'array',
                    'description' => 'List of updates. Each element corresponds to the parameters of patients.progress_cards.PUT.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'progress_card_id' => ['type' => 'integer'],
                            'title' => ['type' => 'string'],
                            'body_md' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                        ],
                        'required' => ['progress_card_id'],
                    ],
                ],
            ],
            'required' => ['updates'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            $updates = $arguments['updates'] ?? null;
            if (!is_array($updates) || empty($updates)) {
                return ToolResult::error('INVALID_ARGUMENT', 'updates must be a non-empty array.');
            }

            $atomic = (bool)($arguments['atomic'] ?? false);
            $singleTool = new UpdateProgressCardTool();

            $run = function() use ($updates, $singleTool, $context) {
                $results = [];
                $okCount = 0;
                $failCount = 0;

                foreach ($updates as $idx => $u) {
                    if (!is_array($u)) {
                        $failCount++;
                        $results[] = [
                            'index' => $idx,
                            'ok' => false,
                            'error' => ['code' => 'INVALID_ITEM', 'message' => 'Update item must be an object.'],
                        ];
                        continue;
                    }

                    $res = $singleTool->execute($u, $context);
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
                        'requested' => count($updates),
                        'ok' => $okCount,
                        'failed' => $failCount,
                    ],
                ];
            };

            $payload = $atomic ? DB::transaction(fn() => $run()) : $run();

            return ToolResult::success($payload);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error during bulk update of Progress Cards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'bulk',
            'tags' => ['patients', 'progress_cards', 'bulk', 'batch', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}
