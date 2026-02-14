<x-ui-modal size="md" model="modalShow" header="Block Settings">
    @if($block)
        <x-ui-form-grid :cols="1" :gap="4">
            {{-- Block Name --}}
            <x-ui-input-text 
                name="name"
                label="Block Name"
                wire:model="name"
                placeholder="Enter block name..."
                required
                :errorKey="'name'"
            />

            {{-- Description --}}
            <x-ui-input-textarea
                name="description"
                label="Description"
                wire:model="description"
                placeholder="Enter block description..."
                :errorKey="'description'"
            />

            {{-- Content Type --}}
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-3">
                    Content Type
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        wire:click="setContentType('text')"
                        class="px-4 py-2 text-sm font-medium rounded-lg border transition-all
                            @if($contentType === 'text')
                                bg-[var(--ui-primary)] text-white border-[var(--ui-primary)] shadow-sm
                            @else
                                bg-white text-[var(--ui-secondary)] border-[var(--ui-border)]/40 hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-muted-5)]
                            @endif
                        "
                    >
                        Text
                    </button>
                    <button
                        type="button"
                        disabled
                        class="px-4 py-2 text-sm font-medium rounded-lg border bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                        title="Coming soon"
                    >
                        Image
                    </button>
                    <button
                        type="button"
                        disabled
                        class="px-4 py-2 text-sm font-medium rounded-lg border bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                        title="Coming soon"
                    >
                        Carousel
                    </button>
                    <button
                        type="button"
                        disabled
                        class="px-4 py-2 text-sm font-medium rounded-lg border bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed"
                        title="Coming soon"
                    >
                        Video
                    </button>
                </div>
                @if($contentType)
                    <p class="mt-2 text-xs text-[var(--ui-muted)]">
                        Current type: <span class="font-medium">{{ $contentType }}</span>
                    </p>
                @else
                    <p class="mt-2 text-xs text-[var(--ui-muted)]">
                        No content type selected yet
                    </p>
                @endif
            </div>

            {{-- Span --}}
            <div>
                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-3">
                    Span (1-12)
                </label>
                @if($block)
                    @php
                        $row = $block->row;
                        $row->load('blocks');
                        $currentSum = $row->blocks->sum('span');
                        $currentBlockSpan = $block->span;
                        // Calculate available spans: 12 - (current sum - current block span)
                        $availableSpan = 12 - ($currentSum - $currentBlockSpan);
                        // Maximum available value is 12
                        $maxAvailable = min(12, $availableSpan);
                    @endphp
                    <div class="grid grid-cols-6 gap-2">
                        @for($i = 1; $i <= $maxAvailable; $i++)
                            <button
                                type="button"
                                wire:click="$set('span', {{ $i }})"
                                class="px-3 py-2 text-sm font-medium rounded-lg border transition-all
                                    @if($span == $i)
                                        bg-[var(--ui-primary)] text-white border-[var(--ui-primary)] shadow-sm
                                    @else
                                        bg-white text-[var(--ui-secondary)] border-[var(--ui-border)]/40 hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-muted-5)]
                                    @endif
                                "
                            >
                                {{ $i }}
                            </button>
                        @endfor
                    </div>
                    @error('span')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-3 text-xs text-[var(--ui-muted)]">
                        Available: <span class="font-medium">{{ $availableSpan }}/12</span> Spans
                        @if($span && $span != $currentBlockSpan)
                            @php
                                $newSum = $currentSum - $currentBlockSpan + $span;
                            @endphp
                            <br>With new value: <span class="font-medium {{ $newSum > 12 ? 'text-red-600' : '' }}">{{ $newSum }}/12</span> Spans
                        @endif
                    </p>
                @endif
            </div>
        </x-ui-form-grid>
        
        {{-- Delete Block --}}
        <div class="mt-4">
            <x-ui-confirm-button action="deleteBlock" text="Delete Block" confirmText="Really delete?" />
        </div>
    @endif

    <x-slot name="footer">
        @if($block)
            <x-ui-button variant="success" wire:click="save">Save</x-ui-button>
        @endif
    </x-slot>
</x-ui-modal>
