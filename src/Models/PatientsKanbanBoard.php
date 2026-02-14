<?php

namespace Platform\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Core\Traits\HasExtraFields;

/**
 * Model for Kanban Boards
 *
 * Fully independent model - inherits directly from Laravel Model
 */
class PatientsKanbanBoard extends Model implements HasDisplayName
{
    use HasExtraFields;
    protected $table = 'patients_kanban_boards';

    protected $fillable = [
        'uuid',
        'patient_id',
        'name',
        'description',
        'order',
        'user_id',
        'team_id',
        'done',
        'done_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'done' => 'boolean',
        'done_at' => 'datetime',
        'order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;

            if (!$model->order) {
                $maxOrder = self::where('patient_id', $model->patient_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientsPatient::class, 'patient_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(PatientsKanbanBoardSlot::class, 'kanban_board_id')->orderBy('order');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(PatientsKanbanCard::class, 'kanban_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}
