<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditarPlanillaDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planilla = $this->route('planilla');

        return $planilla && $this->user()->can('update', $planilla);
    }

    public function rules(): array
    {
        return [
            'dias_laborados' => ['sometimes', 'integer', 'min:0', 'max:31'],
            'horas_extras_cantidad' => ['sometimes', 'numeric', 'min:0'],
            'valor_hora_extra' => ['sometimes', 'numeric', 'min:0'],
            'horas_extras_valor' => ['sometimes', 'numeric', 'min:0'],
            'bonificaciones' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
