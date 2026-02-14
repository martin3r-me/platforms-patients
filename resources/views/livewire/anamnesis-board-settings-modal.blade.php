<x-ui-modal size="md" model="modalShow" header="Anamnesis Board Settings">
    @if($anamnesisBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Anamnesis Board Name --}}
            @can('update', $anamnesisBoard)
                <x-ui-input-text 
                    name="anamnesisBoard.name"
                    label="Anamnesis Board Name"
                    wire:model.live.debounce.500ms="anamnesisBoard.name"
                    placeholder="Enter Anamnesis Board name..."
                    required
                    :errorKey="'anamnesisBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Anamnesis Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $anamnesisBoard->name }}</span>
                </div>
            @endcan

            {{-- Description --}}
            @can('update', $anamnesisBoard)
                <x-ui-input-textarea
                    name="anamnesisBoard.description"
                    label="Description"
                    wire:model.live.debounce.500ms="anamnesisBoard.description"
                    placeholder="Enter Anamnesis Board description..."
                    :errorKey="'anamnesisBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Description</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $anamnesisBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>
        
        {{-- Delete Anamnesis Board --}}
        @can('delete', $anamnesisBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteAnamnesisBoard" text="Delete Anamnesis Board" confirmText="Really delete?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($anamnesisBoard)
            @can('update', $anamnesisBoard)
                <x-ui-button variant="success" wire:click="save">Save</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
