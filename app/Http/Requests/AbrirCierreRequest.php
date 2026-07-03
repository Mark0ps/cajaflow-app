<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbrirCierreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\CierreCaja::class);
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'turno' => ['required', 'in:matutino,tarde,nocturno'],
            'monto_inicial' => ['required', 'numeric', 'min:0'],
        ];
    }
}
