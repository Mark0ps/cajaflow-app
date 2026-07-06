<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ActualizarPrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real ocurre en PrestamoController::update(), igual
        // que ActualizarValeRequest.
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_original' => ['sometimes', 'numeric', 'min:0.01'],
            'fecha_otorgado' => ['sometimes', 'date', 'before_or_equal:today'],
            'motivo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metodo_cobro' => ['sometimes', Rule::in(['quincenal', 'mensual'])],
            'monto_cuota' => ['sometimes', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $prestamo = $this->route('prestamo');
            $montoOriginal = $this->input('monto_original', $prestamo->monto_original);
            $montoCuota = $this->input('monto_cuota', $prestamo->monto_cuota);

            if ((float) $montoCuota > (float) $montoOriginal) {
                $validator->errors()->add('monto_cuota', 'La cuota no puede ser mayor al monto del préstamo.');
            }
        });
    }
}
