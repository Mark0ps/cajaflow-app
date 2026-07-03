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
            'fecha' => [$obligatorio, 'date'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'valor' => [$obligatorio, 'numeric', 'min:0.01'],
        ];
    }
}
