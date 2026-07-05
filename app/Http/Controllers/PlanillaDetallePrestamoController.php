<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbonoPrestamoRequest;
use App\Models\Planilla;
use App\Models\PlanillaDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanillaDetallePrestamoController extends Controller
{
    /**
     * Edita el abono de préstamo ya aplicado a este detalle: revierte el
     * monto anterior al saldo_pendiente del préstamo, aplica el nuevo monto
     * y actualiza el mismo registro de PrestamoAbono (no crea uno nuevo).
     */
    public function update(AbonoPrestamoRequest $request, Planilla $planilla, PlanillaDetalle $detalle)
    {
        if ($detalle->planilla_id !== $planilla->id) {
            throw ValidationException::withMessages([
                'detalle' => 'Este detalle no pertenece a la planilla indicada.',
            ]);
        }

        $abono = $detalle->prestamoAbonos()->first();

        if (! $abono) {
            throw ValidationException::withMessages([
                'abono' => 'Este detalle no tiene abono de préstamo para editar.',
            ]);
        }

        DB::transaction(function () use ($request, $detalle, $abono) {
            $prestamo = $abono->prestamo()->lockForUpdate()->first();

            $prestamo->saldo_pendiente = round((float) $prestamo->saldo_pendiente + (float) $abono->monto, 2);

            $montoAplicado = min((float) $request->input('monto'), (float) $prestamo->saldo_pendiente);

            $prestamo->saldo_pendiente = round($prestamo->saldo_pendiente - $montoAplicado, 2);
            $prestamo->estado = $prestamo->saldo_pendiente <= 0 ? 'pagado' : 'activo';
            $prestamo->save();

            $abono->update([
                'monto' => $montoAplicado,
                'motivo' => $request->input('motivo'),
            ]);

            $detalle->recalcularTodo();
        });

        return response()->json([
            'abono' => $abono->fresh(),
            'detalle' => $detalle->fresh(['comprasTienda', 'llegadasTarde', 'prestamoAbonos.prestamo']),
            'prestamo' => $abono->prestamo()->first(),
        ]);
    }
}
