<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['user', 'admin'],
            true
        );
    }

    public function rules(): array
    {
        return [
            'equipment_id' => [
                'required', 'integer', 'exists:equipment,id',
            ],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => 'fallo'
        ];
    }
}
