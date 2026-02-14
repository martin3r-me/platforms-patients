<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Patients\Models\PatientsPatient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for retrieving a single patient
 */
class GetPatientTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'patients.patient.GET';
    }

    public function getDescription(): string
    {
        return 'GET /patients/{id} - Retrieves a single patient. REST parameters: id (required, integer) - patient ID. Use "patients.patients.GET" to see available patient IDs.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'REST parameter (required): Patient ID. Use "patients.patients.GET" to see available patient IDs.'
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
                return ToolResult::error('VALIDATION_ERROR', 'Patient ID is required. Use "patients.patients.GET" to find patients.');
            }

            // Patient fetch
            $patient = PatientsPatient::with(['user', 'team', 'companyLinks.company', 'crmContactLinks.contact'])
                ->find($arguments['id']);

            if (!$patient) {
                return ToolResult::error('PATIENT_NOT_FOUND', 'The specified patient was not found. Use "patients.patients.GET" to see all available patients.');
            }

            // Check policy
            try {
                Gate::forUser($context->user)->authorize('view', $patient);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Patient (Policy).');
            }

            $company = $patient->getCompany();
            $contact = $patient->getContact();
            $companyResolver = app(\Platform\Core\Contracts\CrmCompanyResolverInterface::class);
            $contactResolver = app(\Platform\Core\Contracts\CrmContactResolverInterface::class);

            return ToolResult::success([
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
                'company_name' => $company ? $companyResolver->displayName($company->id) : null,
                'company_url' => $company ? $companyResolver->url($company->id) : null,
                'contact_id' => $contact?->id,
                'contact_name' => $contact ? $contactResolver->displayName($contact->id) : null,
                'contact_url' => $contact ? $contactResolver->url($contact->id) : null,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error loading the Patient: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['patients', 'patient', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
