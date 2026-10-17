<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketStateLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'status',
        'changed_by',
        'started_at',
        'ended_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Duración del periodo correspondiente a este estado.
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return (int) floor(
            $this->started_at->diffInSeconds($this->ended_at) / 60
        );
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
