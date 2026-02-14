<?php

use Platform\Patients\Livewire\Patient;
use Platform\Patients\Livewire\Dashboard;
use Platform\Patients\Livewire\AnamnesisBoard;
use Platform\Patients\Livewire\AnamnesisBoardSection;
use Platform\Patients\Livewire\AnamnesisBoardBlockTextEdit;
use Platform\Patients\Livewire\ProgressBoard;
use Platform\Patients\Livewire\ProgressCard;
use Platform\Patients\Livewire\KanbanBoard;
use Platform\Patients\Livewire\KanbanCard;

Route::get('/', Dashboard::class)->name('patients.dashboard');

// Patient Routes
Route::get('/patients/{patientsPatient}', Patient::class)
    ->name('patients.patients.show');

// Anamnesis Board Routes
Route::get('/anamnesis-boards/{patientsAnamnesisBoard}', AnamnesisBoard::class)
    ->name('patients.anamnesis-boards.show');

// Anamnesis Board Section Routes
Route::get('/anamnesis-board-sections/{patientsAnamnesisBoardSection}', AnamnesisBoardSection::class)
    ->name('patients.anamnesis-board-sections.show');

// Anamnesis Board Block Routes
Route::get('/anamnesis-board-blocks/{patientsAnamnesisBoardBlock}/{type}', AnamnesisBoardBlockTextEdit::class)
    ->name('patients.anamnesis-board-blocks.show');

// Kanban Board Routes
Route::get('/kanban-boards/{patientsKanbanBoard}', KanbanBoard::class)
    ->name('patients.kanban-boards.show');

// Kanban Card Routes
Route::get('/kanban-cards/{patientsKanbanCard}', KanbanCard::class)
    ->name('patients.kanban-cards.show');

// Progress Board Routes
Route::get('/progress-boards/{patientsProgressBoard}', ProgressBoard::class)
    ->name('patients.progress-boards.show');

// Progress Card Routes
Route::get('/progress-cards/{patientsProgressCard}', ProgressCard::class)
    ->name('patients.progress-cards.show');
