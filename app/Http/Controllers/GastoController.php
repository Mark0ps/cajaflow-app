<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarFacturaRequest;
use App\Http\Requests\ActualizarGastoExternoRequest;
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

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->input('categoria'));
        }

        if ($request->filled('tipo_pago')) {
            $query->where('tipo_pago', $request->input('tipo_pago'));
        }

        if ($request->filled('agregado_por_rol')) {
            $query->whereHas('agregadoPor', fn ($q) => $q->where('role', $request->input('agregado_por_rol')));
        }

        // fecha_emision es la fecha real del gasto (no created_at, que es
        // cuándo se cargó al sistema) — tanto el rango libre como el
        // selector rápido de mes/año filtran sobre esta.
        if ($request->filled('mes') && $request->filled('anio')) {
            $query->whereYear('fecha_emision', $request->integer('anio'))
                ->whereMonth('fecha_emision', $request->integer('mes'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->date('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->date('fecha_hasta'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(fn ($sub) => $sub
                ->whereHas('proveedor', fn ($p) => $p->where('nombre', 'like', "%{$q}%"))
                ->orWhere('proveedor_nombre_libre', 'like', "%{$q}%")
                ->orWhere('descripcion', 'like', "%{$q}%")
                ->orWhere('numero_factura', 'like', "%{$q}%"));
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
            'categoria' => $data['categoria'] ?? 'gasto_operativo',
            'agregado_por' => $request->user()->id,
        ], $data['proveedor_id'] ?? null);

        $gasto = Gasto::create($gastoData);

        return response()->json($gasto->load('proveedor'), 201);
    }

    /** Editar un gasto externo (Admin/Secretaria) — sin cierre, sin contraseña. */
    public function updateExterno(ActualizarGastoExternoRequest $request, Gasto $gasto)
    {
        if (! $gasto->es_externo) {
            throw ValidationException::withMessages([
                'gasto' => 'Este gasto no es un gasto externo.',
            ]);
        }

        $data = $request->validated();
        $proveedorId = array_key_exists('proveedor_id', $data) ? $data['proveedor_id'] : $gasto->proveedor_id;

        $gasto->update(Gasto::normalizarFacturaPorProveedor($data, $proveedorId));

        return response()->json($gasto->fresh('proveedor'));
    }

    /** Eliminar un gasto externo (Admin/Secretaria) — sin cierre, sin contraseña. */
    public function destroyExterno(Gasto $gasto)
    {
        $this->authorize('eliminarExterno', $gasto);

        if (! $gasto->es_externo) {
            throw ValidationException::withMessages([
                'gasto' => 'Este gasto no es un gasto externo.',
            ]);
        }

        $gasto->delete();

        return response()->noContent();
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
