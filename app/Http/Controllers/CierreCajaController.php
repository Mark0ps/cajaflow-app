<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbrirCierreRequest;
use App\Http\Requests\ActualizarIngresosRequest;
use App\Models\CierreCaja;
use App\Models\Empleado;
use App\Services\CierreCajaService;
use Illuminate\Http\Request;

class CierreCajaController extends Controller
{
    public function __construct(private CierreCajaService $service)
    {
    }

    /**
     * Listado. Admin/Secretaria ven todos (con filtros opcionales);
     * Cajero solo ve los suyos.
     */
    public function index(Request $request)
    {
        $query = CierreCaja::query()->with(['cajero:id,name'])->latest('fecha');

        if ($request->user()->isCajero()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->date('fecha'));
        }

        if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
            $query->whereBetween('fecha', [$request->date('fecha_desde'), $request->date('fecha_hasta')]);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        return $query->paginate(20);
    }

    public function store(AbrirCierreRequest $request)
    {
        $cierre = $this->service->abrirTurno($request->user(), $request->validated());

        return response()->json($cierre, 201);
    }

    public function show(CierreCaja $cierre)
    {
        $this->authorize('view', $cierre);

        return $cierre->load(['cajero:id,name', 'empleadosTurno:id,nombre,apellido', 'gastos.proveedor', 'vales.empleado']);
    }

    public function actualizarIngresos(ActualizarIngresosRequest $request, CierreCaja $cierre)
    {
        $cierre = $this->service->actualizarIngresos($cierre, $request->validated());

        return response()->json($cierre);
    }

    public function agregarEmpleado(Request $request, CierreCaja $cierre)
    {
        $this->authorize('update', $cierre);

        $request->validate(['empleado_id' => 'required|exists:empleados,id']);
        $empleado = Empleado::findOrFail($request->integer('empleado_id'));

        $this->service->agregarEmpleadoTurno($cierre, $empleado);

        return response()->json($cierre->fresh('empleadosTurno'));
    }

    public function quitarEmpleado(CierreCaja $cierre, Empleado $empleado)
    {
        $this->authorize('update', $cierre);

        $this->service->quitarEmpleadoTurno($cierre, $empleado);

        return response()->json($cierre->fresh('empleadosTurno'));
    }

    public function cerrar(CierreCaja $cierre)
    {
        $this->authorize('cerrar', $cierre);

        $cierre = $this->service->cerrar($cierre);

        return response()->json($cierre);
    }

    public function revisar(CierreCaja $cierre)
    {
        $this->authorize('revisar', $cierre);

        $cierre = $this->service->marcarRevisado($cierre, request()->user());

        return response()->json($cierre);
    }
}
