<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarPlanillaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planilla = $this->route('planilla');

        return $planilla && $this->user()->can('update', $planilla);
    }

    public function rules(): array
    {
        return [
            'empleado_ids' => ['required', 'array', 'min:1'],
            'empleado_ids.*' => ['exists:empleados,id'],
        ];
    }
}
