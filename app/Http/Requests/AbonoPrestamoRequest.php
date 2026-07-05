<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbonoPrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planilla = $this->route('planilla');

        return $planilla && $this->user()->can('update', $planilla);
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** El motivo es obligatorio solo cuando el monto ingresado difiere de la cuota del préstamo. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $detalle = $this->route('detalle');
            $prestamo = $detalle?->prestamoAbonos()->first()?->prestamo;

            if (! $prestamo) {
                return;
            }

            $monto = number_format((float) $this->input('monto'), 2, '.', '');
            $cuota = number_format((float) $prestamo->monto_cuota, 2, '.', '');

            if ($monto !== $cuota && ! $this->filled('motivo')) {
                $validator->errors()->add('motivo', 'El motivo es obligatorio cuando el monto es distinto a la cuota del préstamo.');
            }
        });
    }
}
