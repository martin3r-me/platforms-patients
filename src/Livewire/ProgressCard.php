<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsProgressCard;
use Livewire\Attributes\On;

class ProgressCard extends Component
{
    public PatientsProgressCard $card;
    public string $title = '';
    public string $bodyMd = '';
    public string $description = '';

    public function mount(PatientsProgressCard $patientsProgressCard)
    {
        $this->card = $patientsProgressCard->fresh()->load('slot', 'progressBoard.patient');
        
        // Check authorization
        $this->authorize('view', $this->card);
        
        $this->title = $this->card->title ?? '';
        $this->bodyMd = $this->card->body_md ?? '';
        $this->description = $this->card->description ?? '';
    }

    #[On('updateProgressCard')] 
    public function updateProgressCard()
    {
        $this->card->refresh();
        $this->title = $this->card->title ?? '';
        $this->bodyMd = $this->card->body_md ?? '';
        $this->description = $this->card->description ?? '';

        // Editor sync (wire:ignore)
        $this->dispatch('patients-sync-editor', [
            'cardId' => $this->card->id,
            'title' => $this->title,
            'bodyMd' => $this->bodyMd,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'bodyMd' => 'nullable|string',
            'description' => 'nullable|string',
        ];
    }

    public function save()
    {
        $this->validate();
        $this->authorize('update', $this->card);
        
        $this->card->update([
            'title' => $this->title,
            'body_md' => $this->bodyMd,
            'description' => $this->description,
        ]);
        
        $this->card->refresh();
        $this->title = $this->card->title ?? '';

        // Editor sync (wire:ignore) + UI can show "saved"
        $this->dispatch('patients-saved', [
            'cardId' => $this->card->id,
            'savedAt' => now()->toIso8601String(),
        ]);
        
        // Update navbar title
        $this->dispatch('updateSidebar');
    }

    public function render()
    {
        $user = Auth::user();

        return view('patients::livewire.progress-card', [
            'user' => $user,
        ])->layout('platform::layouts.app');
    }
}
