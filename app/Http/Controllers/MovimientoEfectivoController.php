<?php

namespace App\Http\Controllers;

use App\Http\Requests\MovimientoEfectivoRequest;
use App\Models\CierreCaja;
use App\Models\MovimientoEfectivo;
use App\Services\CierreCajaService;
use Illuminate\Http\Request;

class MovimientoEfectivoController extends Controller
{
    public function __construct(private CierreCajaService $service)
    {
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

    public function destroy(Request $request, CierreCaja $cierre, MovimientoEfectivo $movimiento)
    {
        $this->authorize('update', $cierre);

        $request->validate(['motivo' => ['required', 'string', 'max:500']]);

        $this->service->eliminarMovimientoEfectivo($cierre, $movimiento, $request->user(), $request->input('motivo'));

        return response()->noContent();
    }
}
