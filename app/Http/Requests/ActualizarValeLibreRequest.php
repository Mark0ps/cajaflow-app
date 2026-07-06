<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarValeLibreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['sometimes', 'exists:empleados,id'],
            'monto' => ['sometimes', 'numeric', 'min:0.01'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fecha_emision' => ['sometimes', 'date', 'before_or_equal:today'],
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ];
    }
}
