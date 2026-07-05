<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarMovimientoEfectivoRequest;
use App\Http\Requests\MovimientoEfectivoRequest;
use App\Models\CierreCaja;
use App\Models\MovimientoEfectivo;
use App\Services\CierreCajaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MovimientoEfectivoController extends Controller
{
    public function __construct(private CierreCajaService $service)
    {
    }

    /** Mismo chequeo de pertenencia que en los recursos anidados de planillas. */
    private function verificarPertenencia(CierreCaja $cierre, MovimientoEfectivo $movimiento): void
    {
        if ($movimiento->cierre_caja_id !== $cierre->id) {
            throw ValidationException::withMessages([
                'movimiento' => 'Este movimiento no pertenece al cierre indicado.',
            ]);
        }
    }

    public function store(MovimientoEfectivoRequest $request, CierreCaja $cierre)
    {
        $movimiento = $this->service->agregarMovimientoEfectivo(
            $cierre,
            $request->validated(),
            $request->user(),
            $request->validated()['motivo'],
        );

        return response()->json($movimiento->load('registradoPor:id,name'), 201);
    }

    public function update(ActualizarMovimientoEfectivoRequest $request, CierreCaja $cierre, MovimientoEfectivo $movimiento)
    {
        $this->verificarPertenencia($cierre, $movimiento);

        $movimiento = $this->service->actualizarMovimientoEfectivo(
            $cierre,
            $movimiento,
            $request->validated(),
            $request->user(),
            $request->input('motivo_edicion'),
        );

        return response()->json($movimiento->fresh('registradoPor:id,name'));
    }

    public function destroy(Request $request, CierreCaja $cierre, MovimientoEfectivo $movimiento)
    {
        $this->authorize('update', $cierre);
        $this->verificarPertenencia($cierre, $movimiento);

        $request->validate(['motivo' => ['required', 'string', 'max:500']]);

        $this->service->eliminarMovimientoEfectivo($cierre, $movimiento, $request->user(), $request->input('motivo'));

        return response()->noContent();
    }
}
