<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

            'descripcion' => ['nullable', 'string', 'max:255'],
            'numero_factura' => ['nullable', 'string', 'max:100'],
            // tipo_pago no se valida aquí: los gastos de un cierre de caja son
            // siempre en efectivo — lo fija CierreCajaService::agregarGasto().
            'valor' => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
