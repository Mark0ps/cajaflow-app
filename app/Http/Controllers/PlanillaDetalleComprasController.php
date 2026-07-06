<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarCompraSueltaRequest;
use App\Http\Requests\CompraTiendaRequest;
use App\Models\CompraTienda;
use App\Models\Planilla;
use App\Models\PlanillaDetalle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PlanillaDetalleComprasController extends Controller
{
    public function store(CompraTiendaRequest $request, Planilla $planilla, PlanillaDetalle $detalle)
    {
        $this->verificarDetalle($planilla, $detalle);

        $compra = $detalle->comprasTienda()->create([
            'empleado_id' => $detalle->empleado_id,
            'tipo' => $request->input('tipo', 'compra_credito'),
            'fecha' => $request->input('fecha'),
            'descripcion' => $request->input('descripcion'),
            'motivo' => $request->input('motivo'),
            'valor' => $request->input('valor'),
        ]);

        $detalle->recalcularTodo();

        return response()->json([
            'compra' => $compra,
            'detalle' => $detalle->fresh(['comprasTienda', 'llegadasTarde', 'prestamoAbonos.prestamo']),
        ], 201);
    }

    public function update(CompraTiendaRequest $request, Planilla $planilla, PlanillaDetalle $detalle, CompraTienda $compra)
    {
        $this->verificarDetalle($planilla, $detalle);
        $this->verificarCompra($detalle, $compra);

        $compra->update($request->validated());

        $detalle->recalcularTodo();

        return response()->json([
            'compra' => $compra->fresh(),
            'detalle' => $detalle->fresh(['comprasTienda', 'llegadasTarde', 'prestamoAbonos.prestamo']),
        ]);
    }

    public function destroy(Planilla $planilla, PlanillaDetalle $detalle, CompraTienda $compra)
    {
        $this->authorize('update', $planilla);
        $this->verificarDetalle($planilla, $detalle);
        $this->verificarCompra($detalle, $compra);

        $compra->delete();

        $detalle->recalcularTodo();

        return response()->json(['detalle' => $detalle->fresh(['comprasTienda', 'llegadasTarde', 'prestamoAbonos.prestamo'])]);
    }

    /**
     * Edición/eliminación de una compra "suelta" (planilla_detalle_id = null)
     * — llega a este estado cuando se quita al empleado de una planilla en
     * borrador o se elimina la planilla (PlanillaService::revertirDetalle()).
     * Antes de esto no existía ninguna ruta que alcanzara estos registros: las
     * únicas rutas son anidadas bajo un {planilla}/{detalle}, y verificarCompra()
     * siempre las rechaza porque su planilla_detalle_id nunca coincide con
     * ningún detalle real. Mismo patrón que los vales libres de ValeController.
     */
    public function updateSuelta(ActualizarCompraSueltaRequest $request, CompraTienda $compra)
    {
        $this->verificarSuelta($compra);

        $compra->update($request->validated());

        return response()->json($compra->fresh());
    }

    public function destroySuelta(Request $request, CompraTienda $compra)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->verificarSuelta($compra);

        $compra->delete();

        return response()->noContent();
    }

    private function verificarSuelta(CompraTienda $compra): void
    {
        if ($compra->planilla_detalle_id !== null) {
            throw ValidationException::withMessages([
                'compra' => 'Esta compra ya está asignada a una planilla; edítala desde el detalle correspondiente.',
            ]);
        }
    }

    private function verificarDetalle(Planilla $planilla, PlanillaDetalle $detalle): void
    {
        if ($detalle->planilla_id !== $planilla->id) {
            throw ValidationException::withMessages([
                'detalle' => 'Este detalle no pertenece a la planilla indicada.',
            ]);
        }
    }

    private function verificarCompra(PlanillaDetalle $detalle, CompraTienda $compra): void
    {
        if ($compra->planilla_detalle_id !== $detalle->id) {
            throw ValidationException::withMessages([
                'compra' => 'Esta compra no pertenece al detalle indicado.',
            ]);
        }
    }
}
