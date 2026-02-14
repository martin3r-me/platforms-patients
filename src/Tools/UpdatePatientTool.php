<?php

namespace Platform\Patients\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Patients\Models\PatientsPatient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool for updating patients in the Patients module
 */
class UpdatePatientTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.patients.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /patients/{id} - Updates an existing patient. REST parameters: patient_id (required, integer) - patient ID. name (optional, string) - patient name. description (optional, string) - description. done (optional, boolean) - mark patient as done.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'patient_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the patient to update (REQUIRED). Use "patients.patients.GET" to find patients.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: New patient name. Ask if the user wants to change the name.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: New patient description. Ask if the user wants to change the description.'
                ],
                'done' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Mark patient as done. Ask if the user wants to complete the patient.'
                ]
            ],
            'required' => ['patient_id']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Use standardized ID validation
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

            // Collect update data
            $updateData = [];

            if (isset($arguments['name'])) {
                $updateData['name'] = $arguments['name'];
            }

            if (isset($arguments['description'])) {
                $updateData['description'] = $arguments['description'];
            }

            if (isset($arguments['done'])) {
                $updateData['done'] = $arguments['done'];
                if ($arguments['done']) {
                    $updateData['done_at'] = now();
                } else {
                    $updateData['done_at'] = null;
                }
            }

            // Update patient
            if (!empty($updateData)) {
                $patient->update($updateData);
            }

            $patient->refresh();
            $patient->load(['user', 'team', 'companyLinks.company', 'crmContactLinks.contact']);

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
                'updated_at' => $patient->updated_at->toIso8601String(),
                'company_id' => $company?->id,
                'company_name' => $company ? $companyResolver->displayName($company->id) : null,
                'contact_id' => $contact?->id,
                'contact_name' => $contact ? $contactResolver->displayName($contact->id) : null,
                'message' => "Patient '{$patient->name}' successfully updated."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error updating patient: ' . $e->getMessage());
        }
    }
}
