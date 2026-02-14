<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsPatient;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmCompanyLink;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for linking a patient with a CRM company
 */
class LinkPatientCompanyTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.patient_companies.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/{patient_id}/companies - Links a patient with a CRM company. Parameter: patient_id (required, integer) - Patient ID. company_id (required, integer) - CRM company ID. Use "patients.patients.GET" to find patients and "crm.companies.GET" to find companies.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'patient_id' => [
                    'type' => 'integer',
                    'description' => 'Patient ID (REQUIRED). Use "patients.patients.GET" to find patients.'
                ],
                'company_id' => [
                    'type' => 'integer',
                    'description' => 'CRM company ID (REQUIRED). Use "crm.companies.GET" to find companies.'
                ],
            ],
            'required' => ['patient_id', 'company_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'No user found in context.');
            }

            // Find patient
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'patient_id',
                PatientsPatient::class,
                'PATIENT_NOT_FOUND',
                'The specified patient was not found.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $patient = $validation['model'];
            
            // Check policy
            try {
                Gate::forUser($context->user)->authorize('update', $patient);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to edit this patient (policy).');
            }

            $companyId = (int)($arguments['company_id'] ?? 0);
            if ($companyId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'company_id is required.');
            }

            // Check if Company existiert via resolver (loose coupling)
            $companyResolver = app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class);
            $companyName = $companyResolver->displayName($companyId);
            if (!$companyName) {
                return ToolResult::error('COMPANY_NOT_FOUND', 'CRM company not found.');
            }

            // Link company via links table (via HasCompanyLinksTrait)
            $link = $patient->companyLinks()->firstOrCreate(
                [
                    'company_id' => $companyId,
                ],
                [
                    'team_id' => $context->team->id,
                    'created_by_user_id' => $context->user->id,
                ]
            );

            return ToolResult::success([
                'patient_id' => $patient->id,
                'patient_name' => $patient->name,
                'company_id' => $companyId,
                'company_name' => $companyName,
                'already_linked' => !$link->wasRecentlyCreated,
                'message' => 'CRM company linked with patient.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error linking: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['patients', 'patient', 'crm', 'company', 'link'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
