<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarFacturaRequest;
use App\Http\Requests\ActualizarGastoRequest;
use App\Http\Requests\GastoExternoRequest;
use App\Http\Requests\GastoRequest;
use App\Models\CierreCaja;
use App\Models\Gasto;
use App\Services\CierreCajaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GastoController extends Controller
{
    public function __construct(private CierreCajaService $service)
    {
    }

    /**
     * Mismo chequeo de pertenencia que en los recursos anidados de planillas:
     * sin esto, la mutación cae sobre un gasto de OTRO cierre y recalcula los
     * totales del cierre equivocado, dejando los del cierre real obsoletos.
     */
    private function verificarPertenencia(CierreCaja $cierre, Gasto $gasto): void
    {
        if ($gasto->cierre_caja_id !== $cierre->id) {
            throw ValidationException::withMessages([
                'gasto' => 'Este gasto no pertenece al cierre indicado.',
            ]);
        }
    }

    /**
     * Listado con filtros — usado principalmente por Secretaria/Admin para
     * ver facturas pendientes y gastos externos.
     */
    public function index(Request $request)
    {
        $query = Gasto::query()->with(['proveedor', 'agregadoPor:id,name', 'cierreCaja:id,fecha,turno']);

        if ($request->user()->isCajero()) {
            $query->whereHas('cierreCaja', fn ($q) => $q->where('user_id', $request->user()->id));
        }

        if ($request->boolean('es_externo', false)) {
            $query->where('es_externo', true);
        }

        if ($request->filled('factura_pendiente')) {
            $query->where('factura_pendiente', $request->boolean('factura_pendiente'));
        }

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->integer('proveedor_id'));
        }

        return $query->latest()->paginate(20);
    }

    /** Gasto dentro de un cierre de caja (cajero registrando su turno). */
    public function store(GastoRequest $request, CierreCaja $cierre)
    {
        $this->authorize('update', $cierre); // el turno debe estar abierto y ser del cajero (o admin)

        $gasto = $this->service->agregarGasto(
            $cierre,
            $request->user(),
            $request->validated(),
            $request->input('motivo'),
        );

        return response()->json($gasto->load('proveedor'), 201);
    }

    /** Gasto externo del negocio (Secretaria/Admin), sin cierre asociado. */
    public function storeExterno(GastoExternoRequest $request)
    {
        $data = $request->validated();

        $gastoData = Gasto::normalizarFacturaPorProveedor([
            ...$data,
            'cierre_caja_id' => null,
            'es_externo' => true,
            'factura_pendiente' => empty($data['numero_factura']),
            'agregado_por' => $request->user()->id,
        ], $data['proveedor_id'] ?? null);

        $gasto = Gasto::create($gastoData);

        return response()->json($gasto->load('proveedor'), 201);
    }

    /** Secretaria/Admin completan un N° de factura que quedó pendiente. */
    public function actualizarFactura(ActualizarFacturaRequest $request, Gasto $gasto)
    {
        $gasto->update([
            'numero_factura' => $request->validated()['numero_factura'],
            'factura_pendiente' => false,
        ]);

        return response()->json($gasto);
    }

    public function update(ActualizarGastoRequest $request, CierreCaja $cierre, Gasto $gasto)
    {
        $this->verificarPertenencia($cierre, $gasto);

        $gasto = $this->service->actualizarGasto(
            $cierre,
            $gasto,
            $request->validated(),
            $request->user(),
            $request->input('motivo'),
        );

        // fresh() en vez de load(): evita devolver una relación cierreCaja
        // obsoleta que ActualizarGastoRequest::authorize() ya dejó en caché
        // (vía GastoPolicy::editarPropio()) antes de recalcularTotales().
        return response()->json($gasto->fresh('proveedor'));
    }

    public function destroy(Request $request, CierreCaja $cierre, Gasto $gasto)
    {
        $this->authorize('delete', $gasto);
        $this->verificarPertenencia($cierre, $gasto);

        $this->service->eliminarGasto($cierre, $gasto, $request->user(), $request->input('motivo'));

        return response()->noContent();
    }
}
