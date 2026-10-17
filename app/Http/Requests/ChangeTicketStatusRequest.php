<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && in_array(
                $user->role,
                ['administrador', 'tecnico'],
                true
            );
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['finalizado', 'inhabilitado']),
            ],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
