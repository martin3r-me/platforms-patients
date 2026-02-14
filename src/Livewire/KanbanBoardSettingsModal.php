<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Platform\Patients\Models\PatientsKanbanBoard;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class KanbanBoardSettingsModal extends Component
{
    public $modalShow = false;
    public $kanbanBoard;

    #[On('open-modal-kanban-board-settings')]
    public function openModalKanbanBoardSettings($kanbanBoardId)
    {
        $this->kanbanBoard = PatientsKanbanBoard::findOrFail($kanbanBoardId);

        // Check policy authorization
        $this->authorize('update', $this->kanbanBoard);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'kanbanBoard.name' => 'required|string|max:255',
            'kanbanBoard.description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();

        // Check policy authorization
        $this->authorize('update', $this->kanbanBoard);

        $this->kanbanBoard->save();
        $this->kanbanBoard->refresh();

        $this->dispatch('updateSidebar');
        $this->dispatch('updateKanbanBoard');

        $this->dispatch('notifications:store', [
            'title' => 'Kanban Board saved',
            'message' => 'The Kanban Board has been successfully updated.',
            'notice_type' => 'success',
            'noticable_type' => get_class($this->kanbanBoard),
            'noticable_id'   => $this->kanbanBoard->getKey(),
        ]);

        $this->closeModal();
    }

    public function deleteKanbanBoard()
    {
        // Check policy authorization
        $this->authorize('delete', $this->kanbanBoard);

        $patient = $this->kanbanBoard->patient;
        $this->kanbanBoard->delete();

        // Redirect back to patient
        $this->redirect(route('patients.patients.show', $patient), navigate: true);
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('patients::livewire.kanban-board-settings-modal')->layout('platform::layouts.app');
    }
}
