<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarMovimientoEfectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cierre'));
    }

    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', 'in:entrada,salida'],
            'monto' => ['sometimes', 'numeric', 'min:0.01'],
            // Motivo del movimiento en sí (columna obligatoria) — editable
            // pero no puede quedar vacío.
            'motivo' => ['sometimes', 'string', 'max:500'],
            // Justificación de la edición, aparte del motivo del movimiento.
            // Obligatoria solo si el cierre ya no está abierto y quien edita
            // es Admin — esa regla vive en CierreCajaService, igual que en
            // gastos/vales.
            'motivo_edicion' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
