<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Platform\Patients\Models\PatientsPatient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Platform\Core\Contracts\CrmCompanyOptionsProviderInterface;
use Platform\Core\Contracts\CrmContactOptionsProviderInterface;
use Platform\Core\Contracts\CrmCompanyResolverInterface;
use Platform\Core\Contracts\CrmContactResolverInterface;
use Platform\Crm\Contracts\CompanyInterface;
use Platform\Crm\Contracts\ContactInterface;

class PatientSettingsModal extends Component
{
    public $modalShow = false;
    public $patient;
    public $selectedCompanyId = null;
    public $selectedContactId = null;

    #[On('open-modal-patient-settings')] 
    public function openModalPatientSettings($patientId)
    {
        $this->patient = PatientsPatient::with(['companyLinks.company', 'crmContactLinks.contact'])->findOrFail($patientId);
        
        // Check policy authorization - settings requires view permissions
        $this->authorize('settings', $this->patient);
        
        // Load current links
        $this->selectedCompanyId = $this->patient->getCompany()?->id;
        $this->selectedContactId = $this->patient->getContact()?->id;
        
        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'patient.name' => 'required|string|max:255',
            'patient.description' => 'nullable|string',
            'selectedCompanyId' => 'nullable|integer|exists:crm_companies,id',
            'selectedContactId' => 'nullable|integer|exists:crm_contacts,id',
        ];
    }

    public function getCompanyOptionsProperty()
    {
        /** @var CrmCompanyOptionsProviderInterface $provider */
        $provider = app(CrmCompanyOptionsProviderInterface::class);
        $options = $provider->options(null, 50);
        return collect($options);
    }

    public function getContactOptionsProperty()
    {
        /** @var CrmContactOptionsProviderInterface $provider */
        $provider = app(CrmContactOptionsProviderInterface::class);
        $options = $provider->options(null, 50);
        return collect($options);
    }

    public function save()
    {
        $this->validate();
        
        // Check policy authorization
        $this->authorize('update', $this->patient);

        $this->patient->save();

        // Update company link via links table (loose coupling)
        if ($this->selectedCompanyId) {
            // Remove old links
            $this->patient->detachAllCompanies();
            // Link new company via links table (via HasCompanyLinksTrait)
            // Check if company exists via resolver
            $companyResolver = app(CrmCompanyResolverInterface::class);
            $companyName = $companyResolver->displayName($this->selectedCompanyId);
            if ($companyName) {
                // Link company via links table
                $this->patient->companyLinks()->create([
                    'company_id' => $this->selectedCompanyId,
                    'team_id' => Auth::user()->currentTeam->id,
                    'created_by_user_id' => Auth::id(),
                ]);
            }
        } else {
            // Remove all company links
            $this->patient->detachAllCompanies();
        }
        
        // Update contact link via links table (loose coupling)
        if ($this->selectedContactId) {
            // Remove old links
            $this->patient->crmContactLinks()->delete();
            // Link new contact via links table
            // Check if contact exists via resolver
            $contactResolver = app(CrmContactResolverInterface::class);
            $contactName = $contactResolver->displayName($this->selectedContactId);
            if ($contactName) {
                // Link contact via links table (via HasEmployeeContact trait)
                $this->patient->crmContactLinks()->create([
                    'contact_id' => $this->selectedContactId,
                    'linkable_id' => $this->patient->id,
                    'linkable_type' => get_class($this->patient),
                    'team_id' => Auth::user()->currentTeam->id,
                    'created_by_user_id' => Auth::id(),
                ]);
            }
        } else {
            // Remove all contact links
            $this->patient->crmContactLinks()->delete();
        }
        
        $this->patient->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updatePatient');
        $this->dispatch('updateDashboard');

        $this->dispatch('notifications:store', [
            'title' => 'Patient saved',
            'message' => 'The patient has been successfully updated.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->patient),
            'noticable_id'   => $this->patient->getKey(),
        ]);

        $this->reset('selectedCompanyId', 'selectedContactId');
        $this->closeModal();
    }

    public function markAsDone()
    {
        // Check policy authorization
        $this->authorize('update', $this->patient);

        $this->patient->done = true;
        $this->patient->done_at = now();
        $this->patient->save();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updatePatient');
        $this->dispatch('updateDashboard');
        
        $this->dispatch('notifications:store', [
            'title' => 'Patient completed',
            'message' => 'The patient has been successfully marked as completed.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->patient),
            'noticable_id'   => $this->patient->getKey(),
        ]);
        
        $this->patient->refresh();
    }

    public function deletePatient()
    {
        // Check policy authorization
        $this->authorize('delete', $this->patient);

        $this->patient->delete();
        // Redirect to patients dashboard
        $this->redirect(route('patients.dashboard'), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('patients::livewire.patient-settings-modal')->layout('platform::layouts.app');
    }
}
