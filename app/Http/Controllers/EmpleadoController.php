<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarEmpleadoRequest;
use App\Http\Requests\EmpleadoRequest;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        // `todos=1` (solo Admin) incluye inactivos y el estado `activo` —
        // lo usa la pantalla de gestión de Empleados. El resto de la app
        // (checklist de turno, asignar vale, generar planilla) sigue viendo
        // solo activos con los campos mínimos, sin cambios.
        $verTodos = $request->boolean('todos') && $request->user()->isAdmin();

        $query = Empleado::query();

        if (! $verTodos) {
            $query->where('activo', true);
        }

        if ($request->filled('cargo')) {
            $cargos = array_filter(array_map('trim', explode(',', $request->string('cargo'))));
            $query->whereIn('cargo', $cargos);
        }

        $campos = $verTodos
            ? ['id', 'nombre', 'apellido', 'identidad', 'cargo', 'sueldo_quincenal', 'activo']
            : ['id', 'nombre', 'apellido', 'cargo'];

        return $query->orderBy('nombre')->get($campos);
    }

    public function show(Request $request, Empleado $empleado)
    {
        // La ficha completa incluye sueldo_quincenal — datos de nómina, solo
        // Admin (la pantalla de Empleados del frontend ya es solo-admin; esto
        // cierra el mismo límite en la API). El index de arriba sí queda
        // abierto: solo expone id/nombre/apellido/cargo, que el cajero
        // necesita para registrar vales.
        abort_unless($request->user()->isAdmin(), 403);

        return $empleado;
    }

    public function store(EmpleadoRequest $request)
    {
        $empleado = Empleado::create($request->validated());

        return response()->json($empleado, 201);
    }

    public function update(ActualizarEmpleadoRequest $request, Empleado $empleado)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $empleado->update($request->validated());

        return response()->json($empleado->fresh());
    }

    public function destroy(Request $request, Empleado $empleado)
    {
        abort_unless($request->user()->isAdmin(), 403);

        // Igual que Proveedores: si ya tiene cualquier historial real
        // (nómina, vales, préstamos) o una cuenta de usuario propia, no se
        // borra de verdad — se pierde trazabilidad. Se sugiere desactivar.
        $tieneHistorial = $empleado->planillaDetalles()->exists()
            || $empleado->vales()->exists()
            || $empleado->prestamos()->exists()
            || $empleado->usuario()->exists();

        if ($tieneHistorial) {
            throw ValidationException::withMessages([
                'empleado' => 'No se puede eliminar: este empleado ya tiene planillas, vales, préstamos o una cuenta de usuario asociados. Desactívalo en su lugar.',
            ]);
        }

        $empleado->delete();

        return response()->noContent();
    }
}
