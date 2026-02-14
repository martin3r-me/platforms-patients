<?php

namespace Platform\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Symfony\Component\Uid\UuidV7;

/**
 * Model for Anamnesis Board Block text content
 */
class PatientsAnamnesisBoardBlockText extends Model
{
    protected $table = 'patients_anamnesis_board_block_texts';

    protected $fillable = [
        'uuid',
        'content',
        'user_id',
        'team_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'content' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });
    }

    /**
     * Polymorphic relationship to the block
     */
    public function block(): MorphOne
    {
        return $this->morphOne(PatientsAnamnesisBoardBlock::class, 'content', 'content_type', 'content_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }
}
