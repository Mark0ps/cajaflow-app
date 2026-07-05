<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarValeRequest;
use App\Http\Requests\ValeRequest;
use App\Models\CierreCaja;
use App\Models\Vale;
use App\Services\CierreCajaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValeController extends Controller
{
    public function __construct(private CierreCajaService $service)
    {
    }

    /**
     * Mismo chequeo de pertenencia que en los recursos anidados de planillas:
     * sin esto, la autorización se evalúa contra el cierre de la URL pero la
     * mutación cae sobre un vale de OTRO cierre (y recalcula los totales del
     * cierre equivocado, dejando los del cierre real obsoletos).
     */
    private function verificarPertenencia(CierreCaja $cierre, Vale $vale): void
    {
        if ($vale->cierre_caja_id !== $cierre->id) {
            throw ValidationException::withMessages([
                'vale' => 'Este vale no pertenece al cierre indicado.',
            ]);
        }
    }

    public function store(ValeRequest $request, CierreCaja $cierre)
    {
        $vale = $this->service->agregarVale($cierre, $request->validated(), $request->user(), $request->input('motivo'));

        return response()->json($vale->load('empleado:id,nombre,apellido'), 201);
    }

    public function update(ActualizarValeRequest $request, CierreCaja $cierre, Vale $vale)
    {
        $this->authorize('update', $cierre);
        $this->verificarPertenencia($cierre, $vale);

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
        $this->verificarPertenencia($cierre, $vale);

        $this->service->eliminarVale($cierre, $vale, $request->user(), $request->input('motivo'));

        return response()->noContent();
    }

    /** Vales de un empleado en un rango — insumo para el reporte "vales por empleado". */
    public function porEmpleado(Request $request, int $empleadoId)
    {
        // Datos de nómina de terceros: solo Admin/Secretaria (mismo criterio
        // que los reportes) — un cajero no debe poder listar vales ajenos.
        abort_unless($request->user()->isAdmin() || $request->user()->isSecretaria(), 403);

        $vales = Vale::where('empleado_id', $empleadoId)
            ->with('cierreCaja:id,fecha,turno')
            ->latest()
            ->paginate(20);

        return response()->json($vales);
    }
}
