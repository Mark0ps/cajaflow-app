<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LlegadaTardeRequest extends FormRequest
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
            'fecha' => [$obligatorio, 'date'],
            'minutos_tarde' => [$obligatorio, 'integer', 'min:1'],
            'valor_deduccion' => [$obligatorio, 'numeric', 'min:0'],
        ];
    }
}
