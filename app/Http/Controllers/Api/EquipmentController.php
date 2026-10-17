<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Equipment::query();

        if ($request->user()->role !== 'administrador') {
            $query->where('status', Equipment::STATUS_ACTIVE);
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($builder) use ($search) {
                $builder->where('asset_tag', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%");
            });
        }

        $equipment = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return response()->json($equipment);
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $equipment = Equipment::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Equipo registrado correctamente.',
            'data' => $equipment,
        ], 201);
    }

    public function show(Request $request, Equipment $equipment): JsonResponse
    {
        if (
            $request->user()->role !== 'administrador' &&
            $equipment->status !== Equipment::STATUS_ACTIVE
        ) {
            abort(404);
        }

        return response()->json([
            'data' => $equipment,
        ]);
    }

    public function update(
        UpdateEquipmentRequest $request,
        Equipment $equipment
    ): JsonResponse {
        $equipment->update($request->validated());

        return response()->json([
            'message' => 'Equipo actualizado correctamente.',
            'data' => $equipment->fresh(),
        ]);
    }
}
