@props(['card'])

<x-ui-kanban-card
    :title="''"
    :sortable-id="$card->id"
    :href="route('patients.kanban-cards.show', $card)"
>
    <!-- Title -->
    <div class="mb-3">
        <h4 class="text-sm font-medium text-[var(--ui-secondary)] m-0">
            {{ $card->title }}
        </h4>
    </div>

    <!-- Description -->
    @if($card->description)
        <div class="text-xs text-[var(--ui-muted)] my-1.5 mb-3 line-clamp-2">
            {{ Str::limit($card->description, 120) }}
        </div>
    @endif

    <!-- Meta: Slot -->
    @if($card->slot)
        <div class="mb-2">
            <span class="inline-flex items-start gap-1 text-xs text-[var(--ui-muted)] min-w-0">
                @svg('heroicon-o-view-columns','w-2.5 h-2.5 mt-0.5')
                <span class="truncate max-w-[9rem]">{{ $card->slot->name }}</span>
            </span>
        </div>
    @endif
</x-ui-kanban-card>
