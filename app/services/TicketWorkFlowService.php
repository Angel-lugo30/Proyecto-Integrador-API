<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\TicketStateLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketWorkflowService
{
    /**
     * Crear un ticket e iniciar su primer periodo de revisión.
     */
    public function createTicket(
        User $requester,
        array $data
    ): Ticket {
        return DB::transaction(function () use ($requester, $data) {
            $equipment = Equipment::query()
                ->whereKey($data['equipment_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($equipment->status !== Equipment::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'equipment_id' => [
                        'No se pueden crear tickets para equipos inactivos o en mantenimiento.',
                    ],
                ]);
            }

            $ticket = Ticket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'equipment_id' => $equipment->id,
                'requester_id' => $requester->id,
                'assigned_to' => null,
                'title' => $data['title'],
                'description' => $data['description'],
                'location' => $data['location'] ?? null,
                'status' => Ticket::STATUS_FAILURE,
                'closed_at' => null,
            ]);

            TicketStateLog::create([
                'ticket_id' => $ticket->id,
                'changed_by' => $requester->id,
                'assigned_to' => null,
                'status' => Ticket::STATUS_FAILURE,
                'started_at' => now('UTC'),
                'ended_at' => null,
                'duration_seconds' => null,
                'comment' => 'Ticket registrado.',
            ]);

            return $ticket->load([
                'equipment',
                'requester',
                'assignedTechnician',
                'stateLogs',
            ]);
        }, 3);
    }

    /**
     * Cambiar un ticket de revisión a un estado terminal.
     */
    public function changeStatus(
        int $ticketId,
        User $actor,
        string $newStatus,
        ?string $comment = null
    ): Ticket {
        if (!in_array($newStatus, Ticket::TERMINAL_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['El estado terminal solicitado no es válido.'],
            ]);
        }

        return DB::transaction(function () use (
            $ticketId,
            $actor,
            $newStatus,
            $comment
        ) {
            $ticket = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticketId);

            if ($ticket->status !== Ticket::STATUS_IN_REVIEW) {
                throw ValidationException::withMessages([
                    'status' => [
                        'Solo se pueden cerrar tickets que estén en revisión.',
                    ],
                ]);
            }

            if (
                $actor->role === User::ROLE_TECHNICIAN &&
                $ticket->assigned_to !== $actor->id
            ) {
                abort(403, 'No puedes cerrar un ticket que no tienes asignado.');
            }

            $now = now('UTC');

            $currentLog = TicketStateLog::query()
                ->where('ticket_id', $ticket->id)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->lockForUpdate()
                ->first();

            if (
                !$currentLog ||
                $currentLog->state !== Ticket::STATUS_IN_REVIEW
            ) {
                throw ValidationException::withMessages([
                    'status' => [
                        'No se encontró un periodo de revisión abierto válido.',
                    ],
                ]);
            }

            $duration = max(
                0,
                (int) $currentLog->started_at->diffInSeconds($now)
            );

            $currentLog->update([
                'ended_at' => $now,
                'duration_seconds' => $duration,
            ]);

            $ticket->update([
                'status' => $newStatus,
                'closed_at' => $now,
            ]);

            // Se registra el estado terminal como un evento de duración cero.
            // Así no queda ningún periodo abierto tras el cierre.
            TicketStateLog::create([
                'ticket_id' => $ticket->id,
                'changed_by' => $actor->id,
                'assigned_to' => $ticket->assigned_to,
                'state' => $newStatus,
                'started_at' => $now,
                'ended_at' => $now,
                'duration_seconds' => 0,
                'comment' => $comment,
            ]);

            return $ticket->fresh()->load([
                'equipment',
                'requester',
                'assignedTechnician',
                'stateLogs',
            ]);
        }, 3);
    }

    /**
     * Reasignar un ticket conservando el historial de periodos.
     */
    public function assignTechnician(
        int $ticketId,
        User $actor,
        int $technicianId,
        ?string $comment = null
    ): Ticket {
        return DB::transaction(function () use (
            $ticketId,
            $actor,
            $technicianId,
            $comment
        ) {
            $ticket = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticketId);

            if ($ticket->status !== Ticket::STATUS_IN_REVIEW) {
                throw ValidationException::withMessages([
                    'ticket' => [
                        'No se puede reasignar un ticket cerrado.',
                    ],
                ]);
            }

            $technician = User::query()
                ->whereKey($technicianId)
                ->where('role', User::ROLE_TECHNICIAN)
                ->where('is_active', true)
                ->first();

            if (!$technician) {
                throw ValidationException::withMessages([
                    'assigned_to' => [
                        'El usuario seleccionado no es un técnico activo.',
                    ],
                ]);
            }

            if ($ticket->assigned_to === $technician->id) {
                throw ValidationException::withMessages([
                    'assigned_to' => [
                        'El ticket ya está asignado a ese técnico.',
                    ],
                ]);
            }

            $now = now('UTC');

            $currentLog = TicketStateLog::query()
                ->where('ticket_id', $ticket->id)
                ->whereNull('ended_at')
                ->orderByDesc('started_at')
                ->lockForUpdate()
                ->first();

            if (
                !$currentLog ||
                $currentLog->state !== Ticket::STATUS_IN_REVIEW
            ) {
                throw ValidationException::withMessages([
                    'ticket' => [
                        'No se encontró un periodo de revisión abierto válido.',
                    ],
                ]);
            }

            $duration = max(
                0,
                (int) $currentLog->started_at->diffInSeconds($now)
            );

            $currentLog->update([
                'ended_at' => $now,
                'duration_seconds' => $duration,
            ]);

            $ticket->update([
                'assigned_to' => $technician->id,
            ]);

            TicketStateLog::create([
                'ticket_id' => $ticket->id,
                'changed_by' => $actor->id,
                'assigned_to' => $technician->id,
                'state' => Ticket::STATUS_IN_REVIEW,
                'started_at' => $now,
                'ended_at' => null,
                'duration_seconds' => null,
                'comment' => $comment
                    ? 'Reasignación: ' . $comment
                    : 'Ticket reasignado.',
            ]);

            return $ticket->fresh()->load([
                'equipment',
                'requester',
                'assignedTechnician',
                'stateLogs',
            ]);
        }, 3);
    }

    /**
     * Generar un folio legible y único.
     */
    private function generateTicketNumber(): string
    {
        do {
            $number = 'IDEB-' . now('UTC')->format('Ymd')
                . '-' . Str::upper(Str::random(6));
        } while (
            Ticket::where('ticket_number', $number)->exists()
        );

        return $number;
    }
}
