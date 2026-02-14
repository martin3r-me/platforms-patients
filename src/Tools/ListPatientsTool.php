<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Patients\Models\PatientsPatient;
use Illuminate\Support\Facades\Gate;

/**
 * Tool for listing patients in the Patients module
 */
class ListPatientsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'patients.patients.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients?team_id={id}&filters=[...]&search=...&sort=[...] - Lists patients. REST parameters: team_id (optional, integer) - filter by team ID. If not specified, the current team from context is used automatically. filters (optional, array) - filter array. search (optional, string) - search term. sort (optional, array) - sorting. limit/offset (optional) - pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (optional): Filter nach Team-ID. If not specified, the current team from context is used automatically.'
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

            // Determine team filter
            $teamIdArg = $arguments['team_id'] ?? null;
            if ($teamIdArg === 0 || $teamIdArg === '0') {
                $teamIdArg = null;
            }
            
            if ($teamIdArg === null) {
                $teamIdArg = $context->team?->id;
            }
            
            if (!$teamIdArg) {
                return ToolResult::error('MISSING_TEAM', 'No team specified and no team found in context.');
            }
            
            // Check if user has access to this team
            $userHasAccess = $context->user->teams()->where('teams.id', $teamIdArg)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', "You don't have access to team ID {$teamIdArg}.");
            }
            
            // Build query
            $query = PatientsPatient::query()
                ->where('team_id', $teamIdArg)
                ->with(['user', 'team', 'companyLinks.company', 'crmContactLinks.contact']);

            // Apply standard operations
            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'done', 'created_at', 'updated_at'
            ]);
            
            // Apply standard search
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            
            // Apply standard sorting
            $this->applyStandardSort($query, $arguments, [
                'name', 'created_at', 'updated_at', 'done'
            ], 'name', 'asc');
            
            // Apply standard pagination
            $this->applyStandardPagination($query, $arguments);

            // Fetch patients and filter by policy
            $patients = $query->get()->filter(function ($patient) use ($context) {
                try {
                    return Gate::forUser($context->user)->allows('view', $patient);
                } catch (\Throwable $e) {
                    return false;
                }
            })->values();

            // Format patients
            $patientsList = $patients->map(function($patient) {
                $company = $patient->getCompany();
                $contact = $patient->getContact();
                
                return [
                    'id' => $patient->id,
                    'uuid' => $patient->uuid,
                    'name' => $patient->name,
                    'description' => $patient->description,
                    'team_id' => $patient->team_id,
                    'user_id' => $patient->user_id,
                    'done' => $patient->done,
                    'done_at' => $patient->done_at?->toIso8601String(),
                    'created_at' => $patient->created_at->toIso8601String(),
                    'company_id' => $company?->id,
                    'company_name' => $company ? app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class)->displayName($company->id) : null,
                    'contact_id' => $contact?->id,
                    'contact_name' => $contact ? app(\Platform\Core\Contracts\CrmContactResolverInterface::class)->displayName($contact->id) : null,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'patients' => $patientsList,
                'count' => count($patientsList),
                'team_id' => $teamIdArg,
                'message' => count($patientsList) > 0 
                    ? count($patientsList) . ' patient(s) found (Team-ID: ' . $teamIdArg . ').'
                    : 'No patients found for team ID: ' . $teamIdArg . '.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading patients: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'patient', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
