<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\PagoPlanilla::class);
    }

    public function rules(): array
    {
        return [
            'fecha_pago' => ['required', 'date', 'before_or_equal:today'],
            'monto_total' => ['required', 'numeric', 'min:0.01'],
            'metodo' => ['required', Rule::in(['efectivo', 'transferencia', 'cheque'])],
            'notas' => ['nullable', 'string', 'max:500'],

            // Foto/escaneo del formato firmado a mano
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],

            // Quincenas seleccionadas (checkboxes del estado de cuenta)
            'planilla_detalle_ids' => ['required', 'array', 'min:1'],
            'planilla_detalle_ids.*' => ['integer', 'exists:planilla_detalles,id'],

            // Opcional: reparto manual monto por quincena. Si se omite, se
            // reparte automático en el orden de planilla_detalle_ids.
            'montos' => ['nullable', 'array'],
            'montos.*' => ['numeric', 'min:0.01'],
        ];
    }
}
