<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsPatient;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactLink;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for linking a patient with a CRM contact
 */
class LinkPatientContactTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.patient_contacts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /patients/{patient_id}/contacts - Links a patient with a CRM contact. Parameter: patient_id (required, integer) - Patient ID. contact_id (required, integer) - CRM contact ID. Use "patients.patients.GET" to find patients and "crm.contacts.GET" to find contacts.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'patient_id' => [
                    'type' => 'integer',
                    'description' => 'Patient ID (REQUIRED). Use "patients.patients.GET" to find patients.'
                ],
                'contact_id' => [
                    'type' => 'integer',
                    'description' => 'CRM contact ID (REQUIRED). Use "crm.contacts.GET" to find contacts.'
                ],
            ],
            'required' => ['patient_id', 'contact_id'],
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

            $contactId = (int)($arguments['contact_id'] ?? 0);
            if ($contactId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'contact_id is required.');
            }

            // Check if Contact existiert via resolver (loose coupling)
            $contactResolver = app(\Platform\Core\Contracts\CrmContactResolverInterface::class);
            $contactName = $contactResolver->displayName($contactId);
            if (!$contactName) {
                return ToolResult::error('CONTACT_NOT_FOUND', 'CRM contact not found.');
            }

            // Link contact via links table (via HasEmployeeContact trait)
            $link = CrmContactLink::firstOrCreate(
                [
                    'contact_id' => $contactId,
                    'linkable_type' => PatientsPatient::class,
                    'linkable_id' => $patient->id,
                ],
                [
                    'team_id' => $context->team->id,
                    'created_by_user_id' => $context->user->id,
                ]
            );

            return ToolResult::success([
                'patient_id' => $patient->id,
                'patient_name' => $patient->name,
                'contact_id' => $contactId,
                'contact_name' => $contactName,
                'already_linked' => !$link->wasRecentlyCreated,
                'message' => 'CRM contact linked with patient.',
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
            'tags' => ['patients', 'patient', 'crm', 'contact', 'link'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
