<?php

namespace Platform\Patients\Livewire\Concerns;

use Platform\Patients\Models\PatientsPatient;

trait HasBoardNavigation
{
    protected function getBoardNavigation(PatientsPatient $patient, string $activeType, int $activeId): array
    {
        $boards = [];

        foreach ($patient->anamnesisBoards as $board) {
            $boards[] = [
                'id' => $board->id,
                'name' => $board->name,
                'type' => 'anamnesis',
                'icon' => 'heroicon-o-document-text',
                'color' => 'blue',
                'route' => route('patients.anamnesis-boards.show', $board),
                'active' => $activeType === 'anamnesis' && $activeId === $board->id,
            ];
        }

        $colorMap = [
            'Findings' => 'amber',
            'Therapy' => 'indigo',
            'Medication' => 'emerald',
        ];
        $iconMap = [
            'Findings' => 'heroicon-o-clipboard-document-check',
            'Therapy' => 'heroicon-o-heart',
            'Medication' => 'heroicon-o-beaker',
        ];

        foreach ($patient->kanbanBoards as $board) {
            $boards[] = [
                'id' => $board->id,
                'name' => $board->name,
                'type' => 'kanban',
                'icon' => $iconMap[$board->name] ?? 'heroicon-o-view-columns',
                'color' => $colorMap[$board->name] ?? 'indigo',
                'route' => route('patients.kanban-boards.show', $board),
                'active' => $activeType === 'kanban' && $activeId === $board->id,
            ];
        }

        foreach ($patient->progressBoards as $board) {
            $boards[] = [
                'id' => $board->id,
                'name' => $board->name,
                'type' => 'progress',
                'icon' => 'heroicon-o-clock',
                'color' => 'purple',
                'route' => route('patients.progress-boards.show', $board),
                'active' => $activeType === 'progress' && $activeId === $board->id,
            ];
        }

        return $boards;
    }
}
