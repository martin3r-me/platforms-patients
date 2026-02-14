<?php

namespace Platform\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model for Progress Cards
 *
 * Cards are the individual entries within a slot
 */
class PatientsProgressCard extends Model implements HasDisplayName
{
    protected $table = 'patients_progress_cards';

    protected $fillable = [
        'uuid',
        'progress_board_id',
        'progress_board_slot_id',
        'title',
        'body_md',
        'description',
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
                $maxOrder = self::where('progress_board_slot_id', $model->progress_board_slot_id)
                    ->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function progressBoard(): BelongsTo
    {
        return $this->belongsTo(PatientsProgressBoard::class, 'progress_board_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(PatientsProgressBoardSlot::class, 'progress_board_slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function getDisplayName(): ?string
    {
        return $this->title;
    }
}
