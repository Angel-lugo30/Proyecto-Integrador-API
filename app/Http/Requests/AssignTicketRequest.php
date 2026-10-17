<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required', 'integer', 'exists:users,id',
            ],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
