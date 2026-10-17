<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_FAILURE = 'fallo'; 
    public const STATUS_IN_REVIEW = 'en_revision';
    public const STATUS_FINISHED = 'finalizado';
    public const STATUS_DISABLED = 'inhabilitado';

    protected $fillable = [
        'ticket_number',
        'requester_id',
        'equipment_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'started_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_IN_REVIEW,
            self::STATUS_FINISHED,
            self::STATUS_DISABLED,
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stateLogs(): HasMany
    {
        return $this->hasMany(TicketStateLog::class)
            ->orderBy('started_at');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class)
            ->orderBy('assigned_at');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_FINISHED,
            self::STATUS_DISABLED,
        ], true);
    }

    /**
     * Duración total desde el inicio de la atención
     * hasta el cierre del ticket.
     *
     * Devuelve null si el ticket sigue abierto
     * o si faltan las fechas necesarias.
     */
    public function getResolutionTimeMinutesAttribute(): ?int
    {
        if (!$this->started_at || !$this->closed_at) {
            return null;
        }

        $seconds = $this->started_at
            ->diffInSeconds($this->closed_at);

        return (int) floor($seconds / 60);
    }

    public function getResolutionTimeHoursAttribute(): ?float
    {
        $minutes = $this->resolution_time_minutes;

        if ($minutes === null) {
            return null;
        }

        return round($minutes / 60, 2);
    }


    public function getElapsedSecondsAttribute(): int
    {
        $firstReview = $this->stateLogs
            ->firstWhere('state', self::STATUS_IN_REVIEW);

        if (!$firstReview || !$firstReview->started_at) {
            return 0;
        }

        $end = $this->closed_at ?? now('UTC');

        return max(
            0,
            (int) $firstReview->started_at->diffInSeconds($end)
        );
    }
}

