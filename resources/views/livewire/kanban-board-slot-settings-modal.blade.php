<x-ui-modal size="md" model="modalShow" header="Slot Settings">
    @if($slot)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Slot Name --}}
            <x-ui-input-text
                name="slot.name"
                label="Slot Name"
                wire:model.live.debounce.500ms="slot.name"
                placeholder="Enter slot name..."
                required
                :errorKey="'slot.name'"
            />
        </x-ui-form-grid>

        {{-- Delete Slot --}}
        <div class="mt-4">
            <x-ui-confirm-button action="deleteSlot" text="Delete Slot" confirmText="Really delete? All cards in this slot will also be deleted." />
        </div>
    @endif

    <x-slot name="footer">
        @if($slot)
            <x-ui-button variant="secondary" wire:click="closeModal">Cancel</x-ui-button>
            <x-ui-button variant="primary" wire:click="save">Save</x-ui-button>
        @endif
    </x-slot>
</x-ui-modal>
