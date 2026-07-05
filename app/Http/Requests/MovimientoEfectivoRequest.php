<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MovimientoEfectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cierre'));
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', 'in:entrada,salida'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['required', 'string', 'max:500'],
        ];
    }
}
