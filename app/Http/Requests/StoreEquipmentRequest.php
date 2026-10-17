<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'asset_code' => [
                'required', 'string', 'max:50', 'unique:equipment,asset_code',
            ],
            'name' => ['required', 'string', 'max:150'],
            'equipment_type' => ['required', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => [
                'nullable', 'string', 'max:100', 'unique:equipment,serial_number',
            ],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'in:activo,mantenimiento,inactivo'],
        ];
    }
}
