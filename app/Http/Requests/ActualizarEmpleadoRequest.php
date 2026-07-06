<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real ocurre en EmpleadoController::update(), igual
        // que ActualizarValeRequest.
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'apellido' => ['sometimes', 'string', 'max:255'],
            'identidad' => [
                'sometimes', 'nullable', 'string', 'max:50',
                Rule::unique('empleados', 'identidad')->ignore($this->route('empleado')),
            ],
            'cargo' => ['sometimes', Rule::in([
                'gerente', 'administrador', 'cajero_barista', 'cocinero', 'secretaria', 'seguridad', 'otro',
            ])],
            'fecha_ingreso' => ['sometimes', 'date'],
            'sueldo_quincenal' => ['sometimes', 'numeric', 'min:0.01'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:50'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
