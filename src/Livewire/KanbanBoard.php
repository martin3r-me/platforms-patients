<?php

namespace Platform\Patients\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Patients\Models\PatientsKanbanBoard;
use Platform\Patients\Models\PatientsKanbanBoardSlot;
use Platform\Patients\Models\PatientsKanbanCard;
use Livewire\Attributes\On;

class KanbanBoard extends Component
{
    public PatientsKanbanBoard $kanbanBoard;

    public function mount(PatientsKanbanBoard $patientsKanbanBoard)
    {
        // Reload model to ensure all data is available
        $this->kanbanBoard = $patientsKanbanBoard->fresh()->load('slots.cards');

        // Check authorization
        $this->authorize('view', $this->kanbanBoard);
    }

    #[On('updateKanbanBoard')]
    public function updateKanbanBoard()
    {
        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    public function rules(): array
    {
        return [
            'kanbanBoard.name' => 'required|string|max:255',
            'kanbanBoard.description' => 'nullable|string',
        ];
    }

    public function createSlot()
    {
        $this->authorize('update', $this->kanbanBoard);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'No team selected.');
            return;
        }

        $maxOrder = $this->kanbanBoard->slots()->max('order') ?? 0;

        PatientsKanbanBoardSlot::create([
            'name' => 'New Slot',
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'kanban_board_id' => $this->kanbanBoard->id,
        ]);

        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    public function createCard($slotId = null)
    {
        $this->authorize('update', $this->kanbanBoard);

        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            session()->flash('error', 'No team selected.');
            return;
        }

        $maxOrder = PatientsKanbanCard::where('kanban_board_slot_id', $slotId)
            ->max('order') ?? 0;

        PatientsKanbanCard::create([
            'title' => 'New Card',
            'description' => null,
            'order' => $maxOrder + 1,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'kanban_board_id' => $this->kanbanBoard->id,
            'kanban_board_slot_id' => $slotId,
        ]);

        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    /**
     * Updates the order and slot assignment of cards after drag & drop.
     */
    public function updateCardOrder($groups)
    {
        $this->authorize('update', $this->kanbanBoard);

        foreach ($groups as $group) {
            $slotId = ($group['value'] === 'null' || (int) $group['value'] === 0)
                ? null
                : (int) $group['value'];

            foreach ($group['items'] as $item) {
                $card = PatientsKanbanCard::find($item['value']);

                if (!$card) {
                    continue;
                }

                $card->order = $item['order'];
                $card->kanban_board_slot_id = $slotId;
                $card->save();
            }
        }

        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    /**
     * Updates the order of slots after drag & drop.
     */
    public function updateSlotOrder($groups)
    {
        $this->authorize('update', $this->kanbanBoard);

        foreach ($groups as $group) {
            $slot = PatientsKanbanBoardSlot::find($group['value']);
            if ($slot) {
                $slot->order = $group['order'];
                $slot->save();
            }
        }

        $this->kanbanBoard->refresh();
        $this->kanbanBoard->load('slots.cards');
    }

    public function rendered()
    {
        $this->dispatch('extrafields', [
            'context_type' => get_class($this->kanbanBoard),
            'context_id' => $this->kanbanBoard->id,
        ]);
    }

    public function render()
    {
        $user = Auth::user();

        // Load slots with cards and slot relation
        $slots = $this->kanbanBoard->slots()->with(['cards.slot'])->orderBy('order')->get();

        return view('patients::livewire.kanban-board', [
            'user' => $user,
            'slots' => $slots,
        ])->layout('platform::layouts.app');
    }
}
