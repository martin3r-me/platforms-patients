<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Platform\Patients\Models\PatientsKanbanBoardSlot;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class KanbanBoardSlotSettingsModal extends Component
{
    public $modalShow = false;
    public $slot;

    #[On('open-modal-kanban-board-slot-settings')]
    public function openModalKanbanBoardSlotSettings(...$args)
    {
        // Payload can come as ID or as array/object { slotId: X }
        $payload = $args[0] ?? null;
        $id = is_array($payload)
            ? ($payload['slotId'] ?? $payload['id'] ?? null)
            : (is_object($payload) ? ($payload->slotId ?? $payload->id ?? null) : $payload);

        if (!$id) {
            return; // no valid payload, silently ignore
        }

        $this->slot = PatientsKanbanBoardSlot::findOrFail($id);

        // Check policy authorization
        $this->authorize('update', $this->slot->kanbanBoard);

        $this->modalShow = true;
    }

    public function mount()
    {
        $this->modalShow = false;
    }

    public function rules(): array
    {
        return [
            'slot.name' => 'required|string|max:255',
        ];
    }

    public function save()
    {
        if (!$this->slot) {
            return;
        }

        $this->validate();

        // Check policy authorization
        $this->authorize('update', $this->slot->kanbanBoard);

        $this->slot->save();

        $this->dispatch('updateKanbanBoard');
        $this->closeModal();
    }

    public function deleteSlot()
    {
        if (!$this->slot) {
            return;
        }

        // Check policy authorization
        $this->authorize('update', $this->slot->kanbanBoard);

        $this->slot->delete();

        $this->dispatch('updateKanbanBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('patients::livewire.kanban-board-slot-settings-modal')->layout('platform::layouts.app');
    }
}
