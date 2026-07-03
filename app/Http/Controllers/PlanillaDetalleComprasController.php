<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompraTiendaRequest;
use App\Models\CompraTienda;
use App\Models\Planilla;
use App\Models\PlanillaDetalle;
use Illuminate\Validation\ValidationException;

class PlanillaDetalleComprasController extends Controller
{
    public function store(CompraTiendaRequest $request, Planilla $planilla, PlanillaDetalle $detalle)
    {
        $this->verificarDetalle($planilla, $detalle);

        $compra = $detalle->comprasTienda()->create([
            'empleado_id' => $detalle->empleado_id,
            'fecha' => $request->input('fecha'),
            'descripcion' => $request->input('descripcion'),
            'valor' => $request->input('valor'),
        ]);

        $detalle->recalcularTodo();

        return response()->json([
            'compra' => $compra,
            'detalle' => $detalle->fresh(),
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
            'detalle' => $detalle->fresh(),
        ]);
    }

    public function destroy(Planilla $planilla, PlanillaDetalle $detalle, CompraTienda $compra)
    {
        $this->authorize('update', $planilla);
        $this->verificarDetalle($planilla, $detalle);
        $this->verificarCompra($detalle, $compra);

        $compra->delete();

        $detalle->recalcularTodo();

        return response()->json(['detalle' => $detalle->fresh()]);
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
