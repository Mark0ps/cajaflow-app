<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrestamoRequest;
use App\Models\Empleado;
use App\Models\Prestamo;

class PrestamoController extends Controller
{
    public function index(Empleado $empleado)
    {
        $this->authorize('viewAny', Prestamo::class);

        return $empleado->prestamos()->with('abonos')->latest()->get();
    }

    public function store(PrestamoRequest $request)
    {
        $data = $request->validated();

        $prestamo = Prestamo::create([
            ...$data,
            'saldo_pendiente' => $data['monto_original'],
            'estado' => 'activo',
        ]);

        return response()->json($prestamo, 201);
    }

    public function show(Prestamo $prestamo)
    {
        $this->authorize('view', $prestamo);

        return $prestamo->load(['empleado:id,nombre,apellido', 'abonos']);
    }
}
