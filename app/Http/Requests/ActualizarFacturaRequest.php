<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarFacturaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('gasto'));
    }

    public function rules(): array
    {
        return [
            'numero_factura' => ['required', 'string', 'max:100'],
        ];
    }
}
