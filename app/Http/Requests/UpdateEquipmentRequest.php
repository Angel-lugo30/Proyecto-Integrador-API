<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $equipment = $this->route('equipment');
        $id = $equipment instanceof \App\Models\Equipment
            ? $equipment->id
            : $equipment;

        return [
            'asset_tag' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('equipment', 'asset_tag')->ignore($id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'equipment_type' => ['sometimes', 'required', 'string', 'max:100'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:100'],
            'model' => ['sometimes', 'nullable', 'string', 'max:100'],
            'serial_number' => [
                'sometimes', 'nullable', 'string', 'max:100',
                Rule::unique('equipment', 'serial_number')->ignore($id),
            ],
            'location' => ['sometimes', 'nullable', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => [
                'sometimes', 'required',
                Rule::in(['activo', 'mantenimiento', 'inactivo']),
            ],
        ];
    }
}
