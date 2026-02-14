<?php

namespace Platform\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;
use Platform\Core\Contracts\HasDisplayName;

/**
 * Model for Anamnesis Board Rows
 */
class PatientsAnamnesisBoardRow extends Model implements HasDisplayName
{
    protected $table = 'patients_anamnesis_board_rows';

    protected $fillable = [
        'uuid',
        'section_id',
        'name',
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
                $maxOrder = self::where('section_id', $model->section_id)->max('order') ?? 0;
                $model->order = $maxOrder + 1;
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(PatientsAnamnesisBoardSection::class, 'section_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PatientsAnamnesisBoardBlock::class, 'row_id')->orderBy('order');
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }
}
