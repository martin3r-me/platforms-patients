<?php

namespace Platform\Patients\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Platform\Organization\Traits\HasOrganizationContexts;
use Platform\Core\Traits\HasColors;
use Platform\Core\Contracts\HasTimeAncestors;
use Platform\Core\Contracts\HasKeyResultAncestors;
use Platform\Core\Contracts\HasDisplayName;
use Platform\Crm\Traits\HasCompanyLinksTrait;
use Platform\Crm\Contracts\CompanyInterface;
use Platform\Crm\Contracts\ContactInterface;

/**
 * @ai.description Patient serves as a container for the patient record within the team.
 */
class PatientsPatient extends Model implements HasTimeAncestors, HasKeyResultAncestors, HasDisplayName
{
    use HasOrganizationContexts, HasColors, HasCompanyLinksTrait;

    protected $table = 'patients_patients';

    protected $fillable = [
        'uuid',
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
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            do {
                $uuid = UuidV7::generate();
            } while (self::where('uuid', $uuid)->exists());

            $model->uuid = $uuid;
        });

        // Auto-create the 5 boards when creating a patient
        static::created(function (self $patient) {
            // 1x Anamnesis Board
            PatientsAnamnesisBoard::create([
                'name' => 'Anamnesis',
                'patient_id' => $patient->id,
                'user_id' => $patient->user_id,
                'team_id' => $patient->team_id,
            ]);

            // 3x Kanban Boards
            foreach (['Findings', 'Therapy', 'Medication'] as $name) {
                PatientsKanbanBoard::create([
                    'name' => $name,
                    'patient_id' => $patient->id,
                    'user_id' => $patient->user_id,
                    'team_id' => $patient->team_id,
                ]);
            }

            // 1x Progress Board
            PatientsProgressBoard::create([
                'name' => 'Progress',
                'patient_id' => $patient->id,
                'user_id' => $patient->user_id,
                'team_id' => $patient->team_id,
            ]);
        });
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
     * Relationship to CRM contacts via polymorphic links
     */
    public function crmContactLinks()
    {
        return $this->morphMany(
            \Platform\Crm\Models\CrmContactLink::class,
            'linkable'
        );
    }

    /**
     * Returns the primary linked company (via interface)
     */
    public function getCompany(): ?CompanyInterface
    {
        return $this->companyLinks()->first()?->company;
    }

    /**
     * Returns the primary linked contact (via interface)
     */
    public function getContact(): ?ContactInterface
    {
        return $this->crmContactLinks()->first()?->contact;
    }

    public function timeAncestors(): array
    {
        return [];
    }

    public function keyResultAncestors(): array
    {
        return [];
    }

    public function getDisplayName(): ?string
    {
        return $this->name;
    }

    /**
     * Anamnesis Boards for this patient
     */
    public function anamnesisBoards()
    {
        return $this->hasMany(PatientsAnamnesisBoard::class, 'patient_id')->orderBy('order');
    }

    /**
     * Kanban Boards for this patient
     */
    public function kanbanBoards()
    {
        return $this->hasMany(PatientsKanbanBoard::class, 'patient_id')->orderBy('order');
    }

    /**
     * Progress Boards for this patient
     */
    public function progressBoards()
    {
        return $this->hasMany(PatientsProgressBoard::class, 'patient_id')->orderBy('order');
    }

    // --- Convenience Methods ---

    public function anamnesisBoard()
    {
        return $this->anamnesisBoards()->first();
    }

    public function befundeBoard()
    {
        return $this->kanbanBoards()->where('name', 'Findings')->first();
    }

    public function therapieBoard()
    {
        return $this->kanbanBoards()->where('name', 'Therapy')->first();
    }

    public function medikationBoard()
    {
        return $this->kanbanBoards()->where('name', 'Medication')->first();
    }

    public function progressBoard()
    {
        return $this->progressBoards()->first();
    }

    public function getTeamId(): int
    {
        return $this->team_id ?? 0;
    }
}
