<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::query()->activos();

        if ($request->filled('q')) {
            $query->where('nombre', 'like', '%' . $request->string('q') . '%');
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

        // Baja lógica en vez de borrar — no queremos perder el historial de
        // gastos que ya referencian a este proveedor.
        $proveedor->update(['activo' => false]);

        return response()->noContent();
    }
}
