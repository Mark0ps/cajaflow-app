<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'identidad' => ['nullable', 'string', 'max:50', 'unique:empleados,identidad'],
            'cargo' => ['required', Rule::in([
                'gerente', 'administrador', 'cajero_barista', 'cocinero', 'secretaria', 'seguridad', 'otro',
            ])],
            'fecha_ingreso' => ['required', 'date'],
            'sueldo_quincenal' => ['required', 'numeric', 'min:0.01'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
