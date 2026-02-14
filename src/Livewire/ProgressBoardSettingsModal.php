<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Platform\Patients\Models\PatientsProgressBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ProgressBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $progressBoard;

    #[On('open-modal-progress-board-settings')] 
    public function openModalProgressBoardSettings($progressBoardId)
    {
        $this->progressBoard = PatientsProgressBoard::findOrFail($progressBoardId);
        
        // Check policy authorization
        $this->authorize('update', $this->progressBoard);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'progressBoard.name' => 'required|string|max:255',
            'progressBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        
        // Check policy authorization
        $this->authorize('update', $this->progressBoard);

        $this->progressBoard->save();
        $this->progressBoard->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateProgressBoard');

        $this->dispatch('notifications:store', [
            'title' => 'Progress Board saved',
            'message' => 'The Progress Board has been successfully updated.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->progressBoard),
            'noticable_id'   => $this->progressBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteProgressBoard()
    {
        // Check policy authorization
        $this->authorize('delete', $this->progressBoard);

        $patient = $this->progressBoard->patient;
        $this->progressBoard->delete();

        // Redirect back to patient
        $this->redirect(route('patients.patients.show', $patient), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('patients::livewire.progress-board-settings-modal')->layout('platform::layouts.app');
    }
}
