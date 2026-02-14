<?php

namespace Platform\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model for Anamnesis Board Blocks
 */
class PatientsAnamnesisBoardBlock extends Model implements HasDisplayName
{
    protected $table = 'patients_anamnesis_board_blocks';

    protected $fillable = [
        'uuid',
        'row_id',
        'name',
        'description',
        'order',
        'span',
        'content_type',
        'content_id',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'order' => 'integer',
        'span' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
            
            if (!$model->order) {
                $maxOrder = self::where('row_id', $model->row_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
            
            // Set span default value if not set
            if (!$model->span) {
                $model->span = 1;
            }
            
            // Validate span: must be between 1 and 12
            if ($model->span < 1 || $model->span > 12) {
                $model->span = max(1, min(12, $model->span));
            }
        });
        
        static::updating(function (self $model) {
            // Validate span: must be between 1 and 12
            if (isset($model->span) && ($model->span < 1 || $model->span > 12)) {
                $model->span = max(1, min(12, $model->span));
            }
        });
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(PatientsAnamnesisBoardRow::class, 'row_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Polymorphic relationship to content
     */
    public function content(): MorphTo
    {
        return $this->morphTo('content', 'content_type', 'content_id');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}
