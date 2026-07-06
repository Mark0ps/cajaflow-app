<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarLlegadaSueltaRequest;
use App\Http\Requests\LlegadaTardeRequest;
use App\Models\LlegadaTarde;
use App\Models\Planilla;
use App\Models\PlanillaDetalle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PlanillaDetalleLlegadasController extends Controller
{
    public function store(LlegadaTardeRequest $request, Planilla $planilla, PlanillaDetalle $detalle)
    {
        $this->verificarDetalle($planilla, $detalle);

        $llegada = $detalle->llegadasTarde()->create([
            'empleado_id' => $detalle->empleado_id,
            'fecha' => $request->input('fecha'),
            'minutos_tarde' => $request->input('minutos_tarde'),
            'valor_deduccion' => $request->input('valor_deduccion'),
        ]);

        $detalle->recalcularTodo();

        return response()->json([
            'llegada' => $llegada,
            'detalle' => $detalle->fresh(['comprasTienda', 'llegadasTarde', 'prestamoAbonos.prestamo']),
        ], 201);
    }

    public function update(LlegadaTardeRequest $request, Planilla $planilla, PlanillaDetalle $detalle, LlegadaTarde $llegada)
    {
        $this->verificarDetalle($planilla, $detalle);
        $this->verificarLlegada($detalle, $llegada);

        $llegada->update($request->validated());

        $detalle->recalcularTodo();

        return response()->json([
            'llegada' => $llegada->fresh(),
            'detalle' => $detalle->fresh(['comprasTienda', 'llegadasTarde', 'prestamoAbonos.prestamo']),
        ]);
    }

    public function destroy(Planilla $planilla, PlanillaDetalle $detalle, LlegadaTarde $llegada)
    {
        $this->authorize('update', $planilla);
        $this->verificarDetalle($planilla, $detalle);
        $this->verificarLlegada($detalle, $llegada);

        $llegada->delete();

        $detalle->recalcularTodo();

        return response()->json(['detalle' => $detalle->fresh(['comprasTienda', 'llegadasTarde', 'prestamoAbonos.prestamo'])]);
    }

    /**
     * Edición/eliminación de una llegada tarde "suelta" (planilla_detalle_id
     * = null) — mismo caso y mismo patrón que verificarSuelta() en
     * PlanillaDetalleComprasController: sin esto, un registro que queda
     * desligado (empleado quitado de la planilla, o planilla eliminada) no
     * tiene ninguna ruta que lo alcance para corregirlo/eliminarlo.
     */
    public function updateSuelta(ActualizarLlegadaSueltaRequest $request, LlegadaTarde $llegada)
    {
        $this->verificarSuelta($llegada);

        $llegada->update($request->validated());

        return response()->json($llegada->fresh());
    }

    public function destroySuelta(Request $request, LlegadaTarde $llegada)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->verificarSuelta($llegada);

        $llegada->delete();

        return response()->noContent();
    }

    private function verificarSuelta(LlegadaTarde $llegada): void
    {
        if ($llegada->planilla_detalle_id !== null) {
            throw ValidationException::withMessages([
                'llegada' => 'Esta llegada tarde ya está asignada a una planilla; edítala desde el detalle correspondiente.',
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

    private function verificarLlegada(PlanillaDetalle $detalle, LlegadaTarde $llegada): void
    {
        if ($llegada->planilla_detalle_id !== $detalle->id) {
            throw ValidationException::withMessages([
                'llegada' => 'Esta llegada tarde no pertenece al detalle indicado.',
            ]);
        }
    }
}
