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
 * Tool for deleting patients in the Patients module
 */
class DeletePatientTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'patients.patients.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /patients/{id} - Deletes a patient. REST parameters: patient_id (required, integer) - patient ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'patient_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the patient to delete (REQUIRED). Use "patients.patients.GET" to find patients.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Confirmation that the patient should really be deleted.'
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
                Gate::forUser($context->user)->authorize('delete', $patient);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'You are not allowed to delete this patient (policy).');
            }

            $patientName = $patient->name;
            $patientId = $patient->id;
            $teamId = $patient->team_id;

            // Delete patient
            $patient->delete();

            // Invalidate cache
            try {
                $cacheService = app(\Platform\Core\Services\ToolCacheService::class);
                if ($cacheService) {
                    $cacheService->invalidate('patients.patients.GET', $context->user->id, $teamId);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }

            return ToolResult::success([
                'patient_id' => $patientId,
                'patient_name' => $patientName,
                'message' => "Patient '{$patientName}' wurde successfully deleted."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Error deleting patient: ' . $e->getMessage());
        }
    }
}
