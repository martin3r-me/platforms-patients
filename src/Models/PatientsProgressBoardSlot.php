<?php

namespace Platform\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

/**
 * Model for Progress Board Slots
 *
 * Slots are the columns in the board
 */
class PatientsProgressBoardSlot extends Model
{
    protected $table = 'patients_progress_board_slots';

    protected $fillable = [
        'uuid',
        'progress_board_id',
        'name',
        'order',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
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
                $maxOrder = self::where('progress_board_id', $model->progress_board_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function progressBoard(): BelongsTo
    {
        return $this->belongsTo(PatientsProgressBoard::class, 'progress_board_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(PatientsProgressCard::class, 'progress_board_slot_id')->orderBy('order');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }
}
