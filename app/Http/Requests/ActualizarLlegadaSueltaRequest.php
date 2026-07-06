<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarLlegadaSueltaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'fecha' => ['sometimes', 'date'],
            'minutos_tarde' => ['sometimes', 'integer', 'min:1'],
            'valor_deduccion' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
