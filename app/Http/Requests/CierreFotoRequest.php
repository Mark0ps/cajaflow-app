<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CierreFotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('cierre'));
    }

    public function rules(): array
    {
        return [
            'foto' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:10240'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            // Obligatorio solo si el cierre ya no está abierto y quien sube es
            // Admin — esa regla de negocio vive en CierreCajaService, no aquí.
            'motivo' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
