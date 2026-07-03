<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GastoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Gasto::class);
    }

    public function rules(): array
    {
        return [
            // Proveedor: del catálogo o texto libre (creación rápida), no ambos vacíos
            'proveedor_id' => ['nullable', 'exists:proveedores,id', 'required_without:proveedor_nombre_libre'],
            'proveedor_nombre_libre' => ['nullable', 'string', 'max:255', 'required_without:proveedor_id'],

            'descripcion' => ['required', 'string', 'max:255'],
            'numero_factura' => ['nullable', 'string', 'max:100'],
            'tipo_pago' => ['required', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque'])],
            'valor' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
