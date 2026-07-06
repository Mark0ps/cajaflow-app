<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proveedor = $this->route('proveedor');

        return $proveedor
            ? $this->user()->can('update', $proveedor)
            : $this->user()->can('create', \App\Models\Proveedor::class);
    }

    public function rules(): array
    {
        $proveedorId = $this->route('proveedor')?->id;

        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('proveedores', 'nombre')->ignore($proveedorId)],
            'contacto_nombre' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'factura_nominal' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }
}
