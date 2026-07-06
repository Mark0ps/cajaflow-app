<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real ocurre en UsuarioController::update(), igual
        // que ActualizarEmpleadoRequest.
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('users', 'username')->ignore($this->route('usuario')),
            ],
            'email' => [
                'sometimes', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('usuario')),
            ],
            'role' => ['sometimes', Rule::in(['admin', 'secretaria', 'cajero'])],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
