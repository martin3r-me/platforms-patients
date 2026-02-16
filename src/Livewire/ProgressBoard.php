<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsProgressBoard;
use Platform\Patients\Models\PatientsProgressBoardSlot;
use Platform\Patients\Models\PatientsProgressCard;
use Livewire\Attributes\On;

class ProgressBoard extends Component
{
    use Concerns\HasBoardNavigation;

    public PatientsProgressBoard $progressBoard;

    public function mount(PatientsProgressBoard $patientsProgressBoard)
    {
        // Reload model to ensure all data is available
        $this->progressBoard = $patientsProgressBoard->fresh()->load('slots.cards');
        
        // Check authorization
        $this->authorize('view', $this->progressBoard);
    }

    #[On('updateProgressBoard')] 
    public function updateProgressBoard()
    {
        $this->progressBoard->refresh();
        $this->progressBoard->load('slots.cards');
    }

    public function rules(): array
    {
        return [
            'progressBoard.name' => 'required|string|max:255',
            'progressBoard.description' => 'nullable|string',
        ];
    }

    public function createSlot()
    {
        $this->authorize('update', $this->progressBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'No team selected.');
            return;
        }

        $maxOrder = $this->progressBoard->slots()->max('order') ?? 0;

        PatientsProgressBoardSlot::create([
            'name' => 'New Slot',
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'progress_board_id' => $this->progressBoard->id,
        ]);

        $this->progressBoard->refresh();
        $this->progressBoard->load('slots.cards');
    }

    public function createCard($slotId = null)
    {
        $this->authorize('update', $this->progressBoard);
        
        $user = Auth::user();
        $team = $user->currentTeam;
        
        if (!$team) {
            session()->flash('error', 'No team selected.');
            return;
        }

        $maxOrder = PatientsProgressCard::where('progress_board_slot_id', $slotId)
            ->max('order') ?? 0;

        PatientsProgressCard::create([
            'title' => 'New Card',
            'body_md' => '',
            'description' => null,
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'progress_board_id' => $this->progressBoard->id,
            'progress_board_slot_id' => $slotId,
        ]);

        $this->progressBoard->refresh();
        $this->progressBoard->load('slots.cards');
    }

    /**
     * Updates the order and slot assignment of cards after drag & drop.
     */
    public function updateCardOrder($groups)
    {
        $this->authorize('update', $this->progressBoard);
        
        foreach ($groups as $group) {
            $slotId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $card = PatientsProgressCard::find($item['value']);

                if (!$card) {
                    continue;
                }

                $card->order = $item['order'];
                $card->progress_board_slot_id = $slotId;
                $card->save();
            }
        }

        $this->progressBoard->refresh();
        $this->progressBoard->load('slots.cards');
    }

    /**
     * Updates the order of slots after drag & drop.
     */
    public function updateSlotOrder($groups)
    {
        $this->authorize('update', $this->progressBoard);
        
        foreach ($groups as $group) {
            $slot = PatientsProgressBoardSlot::find($group['value']);
            if ($slot) {
                $slot->order = $group['order'];
                $slot->save();
            }
        }

        $this->progressBoard->refresh();
        $this->progressBoard->load('slots.cards');
    }

    public function render()
    {
        $user = Auth::user();
        $patient = $this->progressBoard->patient;
        $boardNavigation = $this->getBoardNavigation($patient, 'progress', $this->progressBoard->id);

        // Load slots with cards and slot relation
        $slots = $this->progressBoard->slots()->with(['cards.slot'])->orderBy('order')->get();

        return view('patients::livewire.progress-board', [
            'user' => $user,
            'slots' => $slots,
            'patient' => $patient,
            'boardNavigation' => $boardNavigation,
        ])->layout('platform::layouts.app');
    }
}
