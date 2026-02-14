<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Platform\Patients\Models\PatientsProgressBoardSlot;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ProgressBoardSlotSettingsModal extends Component
{
    public $modalShow = false;
    public $slot;

    #[On('open-modal-progress-board-slot-settings')] 
    public function openModalProgressBoardSlotSettings(...$args)
    {
        // Payload can come as ID or as array/object { slotId: X }
        $payload = $args[0] ?? null;
        $id = is_array($payload)
            ? ($payload['slotId'] ?? $payload['id'] ?? null)
            : (is_object($payload) ? ($payload->slotId ?? $payload->id ?? null) : $payload);

        if(!$id){
            return; // no valid payload, silently ignore
        }

        $this->slot = PatientsProgressBoardSlot::findOrFail($id);
        
        // Check policy authorization
        $this->authorize('update', $this->slot->progressBoard);
        
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
        $this->authorize('update', $this->slot->progressBoard);

        $this->slot->save();
        
        $this->dispatch('updateProgressBoard');
        $this->closeModal();
    }

    public function deleteSlot()
    {
        if (!$this->slot) {
            return;
        }

        // Check policy authorization
        $this->authorize('update', $this->slot->progressBoard);

        $this->slot->delete();
        
        $this->dispatch('updateProgressBoard');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('patients::livewire.progress-board-slot-settings-modal')->layout('platform::layouts.app');
    }
}
