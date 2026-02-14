<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Platform\Patients\Models\PatientsAnamnesisBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AnamnesisBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $anamnesisBoard;

    #[On('open-modal-anamnesis-board-settings')] 
    public function openModalAnamnesisBoardSettings($anamnesisBoardId)
    {
        $this->anamnesisBoard = PatientsAnamnesisBoard::findOrFail($anamnesisBoardId);
        
        // Check policy authorization
        $this->authorize('update', $this->anamnesisBoard);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'anamnesisBoard.name' => 'required|string|max:255',
            'anamnesisBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        
        // Check policy authorization
        $this->authorize('update', $this->anamnesisBoard);

        $this->anamnesisBoard->save();
        $this->anamnesisBoard->refresh();
        
        $this->dispatch('updateSidebar');
        $this->dispatch('updateAnamnesisBoard');

        $this->dispatch('notifications:store', [
            'title' => 'Anamnesis Board saved',
            'message' => 'The Anamnesis Board has been successfully updated.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->anamnesisBoard),
            'noticable_id'   => $this->anamnesisBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteAnamnesisBoard()
    {
        // Check policy authorization
        $this->authorize('delete', $this->anamnesisBoard);

        $patient = $this->anamnesisBoard->patient;
        $this->anamnesisBoard->delete();

        // Redirect back to patient
        $this->redirect(route('patients.patients.show', $patient), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('patients::livewire.anamnesis-board-settings-modal')->layout('platform::layouts.app');
    }
}
