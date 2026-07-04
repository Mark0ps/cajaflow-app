<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarValeRequest;
use App\Http\Requests\ValeRequest;
use App\Models\CierreCaja;
use App\Models\Vale;
use App\Services\CierreCajaService;
use Illuminate\Http\Request;

class ValeController extends Controller
{
    public function __construct(private CierreCajaService $service)
    {
    }

    public function store(ValeRequest $request, CierreCaja $cierre)
    {
        $vale = $this->service->agregarVale($cierre, $request->validated(), $request->user(), $request->input('motivo'));

        return response()->json($vale->load('empleado:id,nombre,apellido'), 201);
    }

    public function update(ActualizarValeRequest $request, CierreCaja $cierre, Vale $vale)
    {
        $this->authorize('update', $cierre);

        $vale = $this->service->actualizarVale(
            $cierre,
            $vale,
            $request->validated(),
            $request->user(),
            $request->input('motivo'),
        );

        return response()->json($vale->fresh('empleado:id,nombre,apellido'));
    }

    public function destroy(Request $request, CierreCaja $cierre, Vale $vale)
    {
        $this->authorize('update', $cierre);

        $this->service->eliminarVale($cierre, $vale, $request->user(), $request->input('motivo'));

        return response()->noContent();
    }

    /** Vales de un empleado en un rango — insumo para el reporte "vales por empleado". */
    public function porEmpleado(int $empleadoId)
    {
        $vales = Vale::where('empleado_id', $empleadoId)
            ->with('cierreCaja:id,fecha,turno')
            ->latest()
            ->paginate(20);

        return response()->json($vales);
    }
}
