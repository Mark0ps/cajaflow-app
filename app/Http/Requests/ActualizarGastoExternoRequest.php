<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarGastoExternoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editarExterno', $this->route('gasto'));
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['sometimes', 'nullable', 'exists:proveedores,id'],
            'proveedor_nombre_libre' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fecha_emision' => ['sometimes', 'date'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'numero_factura' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tipo_pago' => ['sometimes', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque'])],
            'valor' => ['sometimes', 'numeric', 'min:0.01'],
            'categoria' => ['sometimes', 'nullable', Rule::in(['gasto_operativo', 'pago_tarjeta_credito'])],
        ];
    }
}
