<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPrestamoRequest;
use App\Http\Requests\PrestamoRequest;
use App\Models\Empleado;
use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PrestamoController extends Controller
{
    public function index(Empleado $empleado)
    {
        $this->authorize('viewAny', Prestamo::class);

        return $empleado->prestamos()->with('abonos')->latest()->get();
    }

    /** Listado global (todos los empleados) con filtros y KPIs agregados, para la pantalla /prestamos. */
    public function listado(Request $request)
    {
        $this->authorize('viewAny', Prestamo::class);

        $todos = Prestamo::query()->get(['estado', 'monto_original', 'saldo_pendiente']);

        $kpis = [
            'total_prestado_activo' => (float) $todos->where('estado', 'activo')->sum('monto_original'),
            'total_pendiente_cobro' => (float) $todos->sum('saldo_pendiente'),
            'cantidad_activos' => $todos->where('estado', 'activo')->count(),
            'cantidad_pagados' => $todos->where('estado', 'pagado')->count(),
        ];

        $prestamos = Prestamo::with('empleado:id,nombre,apellido')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')))
            ->when($request->filled('empleado_id'), fn ($q) => $q->where('empleado_id', $request->input('empleado_id')))
            ->orderByDesc('fecha_otorgado')
            ->get();

        return response()->json(['data' => $prestamos, 'kpis' => $kpis]);
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

    public function update(ActualizarPrestamoRequest $request, Prestamo $prestamo)
    {
        $this->authorize('update', $prestamo);
        $this->asegurarSinAbonos($prestamo);

        $data = $request->validated();

        // Sin abonos aplicados todavía, el saldo pendiente sigue siendo
        // exactamente el monto original — si se edita el monto, el saldo
        // debe moverse junto con él.
        if (array_key_exists('monto_original', $data)) {
            $data['saldo_pendiente'] = $data['monto_original'];
        }

        $prestamo->update($data);

        return response()->json($prestamo->fresh());
    }

    public function destroy(Prestamo $prestamo)
    {
        $this->authorize('delete', $prestamo);
        $this->asegurarSinAbonos($prestamo);

        $prestamo->delete();

        return response()->noContent();
    }

    private function asegurarSinAbonos(Prestamo $prestamo): void
    {
        if ($prestamo->abonos()->exists()) {
            throw ValidationException::withMessages([
                'prestamo' => 'Este préstamo ya tiene abonos aplicados y no se puede editar ni eliminar.',
            ]);
        }
    }
}
