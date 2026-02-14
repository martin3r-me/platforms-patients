<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsAnamnesisBoard;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Abrufen eines einzelnen AnamnesisBoards
 */
class GetAnamnesisBoardTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.anamnesis_board.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/anamnesis_boards/{id} - Retrieves a single Anamnesis Board. REST parameters: id (required, integer) - Anamnesis Board ID. Use "patients.anamnesis_boards.GET" to see available Anamnesis Board IDs.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST parameter (required): ID of the Anamnesis Board. Use "patients.anamnesis_boards.GET" to see available Anamnesis Board IDs.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Anamnesis Board ID is required. Use "patients.anamnesis_boards.GET" to find Anamnesis Boards.');
            }

            // AnamnesisBoard fetch
            $anamnesisBoard = PatientsAnamnesisBoard::with(['patient', 'user', 'team'])
                ->find($arguments['id']);

            if (!$anamnesisBoard) {
                return ToolResult::error('ANAMNESIS_BOARD_NOT_FOUND', 'The specified Anamnesis Board was not found. Use "patients.anamnesis_boards.GET" to see all available Anamnesis Boards to find.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('view', $anamnesisBoard);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf this Anamnesis Board (Policy).');
            }

            $data = [
                'id' => $anamnesisBoard->id,
                'uuid' => $anamnesisBoard->uuid,
                'name' => $anamnesisBoard->name,
                'description' => $anamnesisBoard->description,
                'patient_id' => $anamnesisBoard->patient_id,
                'patient_name' => $anamnesisBoard->patient->name,
                'team_id' => $anamnesisBoard->team_id,
                'user_id' => $anamnesisBoard->user_id,
                'done' => $anamnesisBoard->done,
                'done_at' => $anamnesisBoard->done_at?->toIso8601String(),
                'created_at' => $anamnesisBoard->created_at->toIso8601String(),
            ];

            return ToolResult::success($data);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading Anamnesis Boards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'anamnesis_board', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
