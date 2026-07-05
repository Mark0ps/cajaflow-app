<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $query = Empleado::where('activo', true);

        if ($request->filled('cargo')) {
            $cargos = array_filter(array_map('trim', explode(',', $request->string('cargo'))));
            $query->whereIn('cargo', $cargos);
        }

        return $query->orderBy('nombre')->get(['id', 'nombre', 'apellido', 'cargo']);
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
}
