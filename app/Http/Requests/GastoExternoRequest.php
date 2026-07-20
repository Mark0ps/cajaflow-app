<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GastoExternoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('createExterno', \App\Models\Gasto::class);
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => ['nullable', 'exists:proveedores,id', 'required_without:proveedor_nombre_libre'],
            'proveedor_nombre_libre' => ['nullable', 'string', 'max:255', 'required_without:proveedor_id'],
            'fecha_emision' => ['required', 'date'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'numero_factura' => ['nullable', 'string', 'max:100'],
            'tipo_pago' => ['required', Rule::in(['efectivo', 'tarjeta', 'transferencia', 'cheque'])],
            'valor' => ['required', 'numeric', 'min:0.01'],
            'categoria' => ['nullable', Rule::in(['gasto_operativo', 'pago_tarjeta_credito', 'servicios_publicos'])],

            // Foto/escaneo del comprobante (factura, recibo)
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ];
    }
}
