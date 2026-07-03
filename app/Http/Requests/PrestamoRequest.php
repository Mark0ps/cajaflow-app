<?php

namespace App\Http\Requests;

use App\Models\Prestamo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PrestamoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Prestamo::class);
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'exists:empleados,id'],
            'monto_original' => ['required', 'numeric', 'min:0.01'],
            'fecha_otorgado' => ['required', 'date', 'before_or_equal:today'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'metodo_cobro' => ['required', Rule::in(['quincenal', 'mensual'])],
            'monto_cuota' => ['required', 'numeric', 'min:0.01', 'lte:monto_original'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $empleadoId = $this->input('empleado_id');

            if (! $empleadoId) {
                return;
            }

            $tieneActivo = Prestamo::where('empleado_id', $empleadoId)
                ->where('estado', 'activo')
                ->exists();

            if ($tieneActivo) {
                $validator->errors()->add(
                    'empleado_id',
                    'Este empleado ya tiene un préstamo activo. Debe saldarlo antes de otorgar uno nuevo.'
                );
            }
        });
    }
}
