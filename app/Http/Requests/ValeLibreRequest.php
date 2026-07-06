<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValeLibreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'fecha_emision' => ['required', 'date', 'before_or_equal:today'],
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ];
    }
}
