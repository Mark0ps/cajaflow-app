<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarValeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real ocurre en ValeController::update() vía
        // $this->authorize('update', $cierre), igual que destroy() en este controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['sometimes', 'exists:empleados,id'],
            'monto' => ['sometimes', 'numeric', 'min:0.01'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
