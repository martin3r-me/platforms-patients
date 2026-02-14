<?php

namespace Platform\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model for Progress Boards
 *
 * Fully independent model - inherits directly from Laravel Model
 */
class PatientsProgressBoard extends Model implements HasDisplayName
{
    protected $table = 'patients_progress_boards';

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
        return $this->hasMany(PatientsProgressBoardSlot::class, 'progress_board_id')->orderBy('order');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(PatientsProgressCard::class, 'progress_board_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}
