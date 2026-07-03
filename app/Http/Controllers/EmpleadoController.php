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

    public function show(Empleado $empleado)
    {
        return $empleado;
    }
}
