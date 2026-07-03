<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarIngresosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cierre'));
    }

    public function rules(): array
    {
        return [
            'efectivo' => ['sometimes', 'numeric', 'min:0'],
            'tarjeta_credito' => ['sometimes', 'numeric', 'min:0'],
            'transferencia' => ['sometimes', 'numeric', 'min:0'],
            'venta_sistema_a2' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
