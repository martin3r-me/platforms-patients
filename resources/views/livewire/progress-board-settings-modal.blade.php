<x-ui-modal size="md" model="modalShow" header="Progress Board Settings">
    @if($progressBoard)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Progress Board Name --}}
            @can('update', $progressBoard)
                <x-ui-input-text 
                    name="progressBoard.name"
                    label="Progress Board Name"
                    wire:model.live.debounce.500ms="progressBoard.name"
                    placeholder="Enter Progress Board name..."
                    required
                    :errorKey="'progressBoard.name'"
                />
            @else
                <div class="flex items-center justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)]">Progress Board Name</span>
                    <span class="font-medium text-[var(--ui-body-color)]">{{ $progressBoard->name }}</span>
                </div>
            @endcan

            {{-- Description --}}
            @can('update', $progressBoard)
                <x-ui-input-textarea
                    name="progressBoard.description"
                    label="Description"
                    wire:model.live.debounce.500ms="progressBoard.description"
                    placeholder="Enter Progress Board description..."
                    :errorKey="'progressBoard.description'"
                />
            @else
                <div class="flex items-start justify-between text-sm p-2 rounded border border-[var(--ui-border)] bg-white">
                    <span class="text-[var(--ui-muted)] mr-3">Description</span>
                    <span class="font-medium text-[var(--ui-body-color)] text-right">{{ $progressBoard->description ?? '–' }}</span>
                </div>
            @endcan
        </x-ui-form-grid>
        
        {{-- Delete Progress Board --}}
        @can('delete', $progressBoard)
            <div class="mt-4">
                <x-ui-confirm-button action="deleteProgressBoard" text="Delete Progress Board" confirmText="Really delete?" />
            </div>
        @endcan
    @endif

    <x-slot name="footer">
        @if($progressBoard)
            @can('update', $progressBoard)
                <x-ui-button variant="success" wire:click="save">Save</x-ui-button>
            @endcan
        @endif
    </x-slot>
</x-ui-modal>
