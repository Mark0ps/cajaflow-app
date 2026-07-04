<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarGastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('editarPropio', $this->route('gasto'));
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['sometimes', 'nullable', 'exists:proveedores,id'],
            'proveedor_nombre_libre' => ['sometimes', 'nullable', 'string', 'max:255'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'numero_factura' => ['sometimes', 'nullable', 'string', 'max:100'],
            // tipo_pago no se valida ni se edita aquí: sigue fijo en efectivo.
            'valor' => ['sometimes', 'numeric', 'min:0.01'],
            'motivo' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
