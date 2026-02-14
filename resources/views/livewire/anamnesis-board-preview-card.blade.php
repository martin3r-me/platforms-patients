@props(['anamnesisBoard'])

<x-ui-kanban-card 
    :title="''" 
    :sortable-id="$anamnesisBoard->id" 
    :href="route('patients.anamnesis-boards.show', $anamnesisBoard)"
>
    <!-- Title -->
    <div class="mb-3">
        <h4 class="text-sm font-medium text-[var(--ui-secondary)] m-0">
            {{ $anamnesisBoard->name }}
        </h4>
    </div>

    <!-- Description -->
    @if($anamnesisBoard->description)
        <div class="text-xs text-[var(--ui-muted)] my-1.5 mb-3 line-clamp-2">
            {{ Str::limit($anamnesisBoard->description, 120) }}
        </div>
    @endif

    <!-- Meta: Sections Count -->
    @if($anamnesisBoard->sections)
        <div class="mb-2">
            <span class="inline-flex items-center gap-1 text-xs text-[var(--ui-muted)]">
                @svg('heroicon-o-squares-2x2','w-2.5 h-2.5')
                <span>{{ $anamnesisBoard->sections->count() }} {{ Str::plural('Section', $anamnesisBoard->sections->count()) }}</span>
            </span>
        </div>
    @endif
</x-ui-kanban-card>
