<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Core\Livewire\Concerns\WithExtraFields;
use Platform\Patients\Models\PatientsKanbanCard;
use Livewire\Attributes\On;

class KanbanCard extends Component
{
    use WithExtraFields;
    public PatientsKanbanCard $card;
    public string $title = '';
    public string $description = '';

    public function mount(PatientsKanbanCard $patientsKanbanCard)
    {
        $this->card = $patientsKanbanCard->fresh()->load('slot', 'kanbanBoard.patient');

        // Check authorization
        $this->authorize('view', $this->card);

        $this->title = $this->card->title ?? '';
        $this->description = $this->card->description ?? '';

        // Load extra fields (definitions from board, values from card)
        $this->loadExtraFieldValuesFromParent($this->card, $this->card->kanbanBoard);
    }

    #[On('updateKanbanCard')]
    public function updateKanbanCard()
    {
        $this->card->refresh();
        $this->title = $this->card->title ?? '';
        $this->description = $this->card->description ?? '';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        $this->authorize('update', $this->card);

        $this->card->update([
            'title' => $this->title,
            'description' => $this->description,
        ]);
        $this->saveExtraFieldValues($this->card);

        $this->card->refresh();
        $this->title = $this->card->title ?? '';

        // Reload extra fields (definitions from board)
        $this->loadExtraFieldValuesFromParent($this->card, $this->card->kanbanBoard);

        // UI can show "saved"
        $this->dispatch('patients-kanban-saved', [
            'cardId' => $this->card->id,
            'savedAt' => now()->toIso8601String(),
        ]);

        // Update navbar title
        $this->dispatch('updateSidebar');
    }

    public function rendered()
    {
        $this->dispatch('extrafields', [
            'context_type' => get_class($this->card->kanbanBoard),
            'context_id' => $this->card->kanbanBoard->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();

        return view('patients::livewire.kanban-card', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
