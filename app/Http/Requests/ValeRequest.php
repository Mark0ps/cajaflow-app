<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cierre'));
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
