<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\ChangeTicketStatusRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketWorkflowService $workflow
    ) {
    }

    /**
     * Listar tickets según el rol del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Ticket::query()->with([
            'equipment:id,asset_code,name',
            'requester:id,employee_number,name',
            'assignedTechnician:id,employee_number,name',
        ]);

        // if ($user->role === User::ROLE_USER) {
        //     $query->where('requester_id', $user->id);
        // } elseif ($user->role === User::ROLE_TECHNICIAN) {
        //     $query->where('assigned_to', $user->id);
        // } elseif ($user->role !== User::ROLE_ADMIN) {
        //     abort(403);
        // }

        if ($request->filled('status')) {
            $request->validate([
                'status' => [
                    'string',
                    'in:fallo,en_revision,finalizado,inhabilitado',
                ],
            ]); 

            $query->where('status', $request->input('status'));
        }

        if ($request->filled('ticket_number')) {
            $query->where(
                'ticket_number',
                $request->input('ticket_number')
            );
        }

        return response()->json(
            $query->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString()
        );
    }

    /**
     * Registrar un nuevo ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->workflow->createTicket(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Ticket registrado correctamente.',
            'data' => $ticket,
        ], 201);
    }

    /**
     * Consultar un ticket y su historial.
     */
    public function show(
        Request $request,
        Ticket $ticket
    ): JsonResponse {
        $user = $request->user();

        $canView = $user->role === User::ROLE_ADMIN
            || (
                $user->role === User::ROLE_USER
                && $ticket->requester_id === $user->id
            )
            || (
                $user->role === User::ROLE_TECHNICIAN
                && $ticket->assigned_to === $user->id
            );

        abort_unless($canView, 403);

        $ticket->load([
            'equipment',
            'requester',
            'assignedTechnician',
            'stateLogs.changedBy:id,employee_number,name',
            'stateLogs.assignedTechnician:id,employee_number,name',
        ]);

        return response()->json([
            'data' => $ticket,
            'elapsed_seconds' => $ticket->elapsed_seconds,
            'elapsed_minutes' => $ticket->elapsed_minutes,
            'elapsed_hours' => $ticket->elapsed_hours,
        ]);
    }

    /**
     * Finalizar o inhabilitar un ticket.
     */
    public function changeStatus(
        ChangeTicketStatusRequest $request,
        Ticket $ticket
    ): JsonResponse {
        $updatedTicket = $this->workflow->changeStatus(
            $ticket->id,
            $request->user(),
            $request->validated()['status'],
            $request->validated()['comment'] ?? null
        );

        return response()->json([
            'message' => 'Estado del ticket actualizado correctamente.',
            'data' => $updatedTicket,
            'elapsed_seconds' => $updatedTicket->elapsed_seconds,
            'elapsed_minutes' => $updatedTicket->elapsed_minutes,
            'elapsed_hours' => $updatedTicket->elapsed_hours,
        ]);
    }

    /**
     * Reasignar un ticket a otro técnico.
     */
    public function assign(
        AssignTicketRequest $request,
        Ticket $ticket
    ): JsonResponse {
        $data = $request->validated();

        $updatedTicket = $this->workflow->assignTechnician(
            $ticket->id,
            $request->user(),
            $data['assigned_to'],
            $data['comment'] ?? null
        );

        return response()->json([
            'message' => 'Técnico asignado correctamente.',
            'data' => $updatedTicket,
        ]);
    }
}
