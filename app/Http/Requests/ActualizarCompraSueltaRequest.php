<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarCompraSueltaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'tipo' => ['sometimes', 'in:compra_credito,cobro_adicional'],
            'fecha' => ['sometimes', 'date'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'valor' => ['sometimes', 'numeric', 'min:0.01'],
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
