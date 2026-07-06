<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        // `todos=1` (solo Admin) incluye inactivos — lo usa la pantalla de
        // gestión de Proveedores. El resto de la app (typeahead de gastos)
        // sigue viendo solo activos vía `q`, sin cambios.
        $verTodos = $request->boolean('todos') && $request->user()->isAdmin();

        $query = Proveedor::query();

        if (! $verTodos) {
            $query->activos();
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(fn ($sub) => $sub
                ->where('nombre', 'like', "%{$q}%")
                ->orWhere('descripcion', 'like', "%{$q}%"));
        }

        return $query->orderBy('nombre')->get();
    }

    /** Creación rápida desde el flujo de gastos (cajero/secretaria/admin). */
    public function store(ProveedorRequest $request)
    {
        $proveedor = Proveedor::create($request->validated());

        return response()->json($proveedor, 201);
    }

    public function update(ProveedorRequest $request, Proveedor $proveedor)
    {
        $this->authorize('update', $proveedor);

        $proveedor->update($request->validated());

        return response()->json($proveedor);
    }

    public function destroy(Proveedor $proveedor)
    {
        $this->authorize('delete', $proveedor);

        // Igual que Empleados: si ya tiene gastos asociados, no se borra de
        // verdad (se perdería trazabilidad) — se bloquea y se sugiere
        // desactivar. Solo se borra real si nunca tuvo historial.
        if ($proveedor->gastos()->exists()) {
            throw ValidationException::withMessages([
                'proveedor' => 'No se puede eliminar: este proveedor ya tiene gastos registrados. Desactívalo en su lugar.',
            ]);
        }

        $proveedor->delete();

        return response()->noContent();
    }
}
