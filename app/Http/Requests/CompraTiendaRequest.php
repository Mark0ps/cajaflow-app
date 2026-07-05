<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompraTiendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planilla = $this->route('planilla');

        return $planilla && $this->user()->can('update', $planilla);
    }

    public function rules(): array
    {
        $obligatorio = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'tipo' => [$obligatorio, 'in:compra_credito,cobro_adicional'],
            'fecha' => [$obligatorio, 'date'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'valor' => [$obligatorio, 'numeric', 'min:0.01'],
        ];
    }

    /** El motivo es obligatorio solo cuando el registro resultante es un cobro adicional. */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $compra = $this->route('compra');
            $tipo = $this->input('tipo', $compra?->tipo ?? 'compra_credito');
            $motivo = $this->input('motivo', $compra?->motivo ?? null);

            if ($tipo === 'cobro_adicional' && empty($motivo)) {
                $validator->errors()->add('motivo', 'El motivo es obligatorio para un cobro adicional.');
            }
        });
    }
}
