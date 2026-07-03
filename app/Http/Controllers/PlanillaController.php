<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPlanillaRequest;
use App\Http\Requests\EditarPlanillaDetalleRequest;
use App\Http\Requests\GenerarPlanillaRequest;
use App\Models\Planilla;
use App\Models\PlanillaDetalle;
use App\Services\PlanillaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PlanillaController extends Controller
{
    public function __construct(private PlanillaService $service)
    {
    }

    public function index()
    {
        $this->authorize('viewAny', Planilla::class);

        return Planilla::withCount('detalles')
            ->withSum('detalles', 'total_a_pagar')
            ->orderByDesc('anio')->orderByDesc('mes')->orderByDesc('quincena')
            ->paginate(12);
    }

    public function store(GenerarPlanillaRequest $request)
    {
        $planilla = $this->service->generar(
            $request->integer('anio'),
            $request->integer('mes'),
            $request->integer('quincena'),
            $request->input('empleado_ids'),
            $request->user(),
        );

        return response()->json($planilla, 201);
    }

    public function show(Planilla $planilla)
    {
        $this->authorize('view', $planilla);

        return $planilla->load('detalles.empleado', 'detalles.comprasTienda');
    }

    /** Agrega/quita empleados de una planilla en borrador (empleado_ids nuevo). */
    public function update(ActualizarPlanillaRequest $request, Planilla $planilla)
    {
        $planilla = $this->service->actualizarEmpleados($planilla, $request->input('empleado_ids'));

        return response()->json($planilla);
    }

    public function cerrar(Request $request, Planilla $planilla)
    {
        $this->authorize('cerrar', $planilla);
        $this->verificarPassword($request);

        return response()->json($this->service->cerrar($planilla));
    }

    public function destroy(Request $request, Planilla $planilla)
    {
        $this->authorize('eliminar', $planilla);
        $this->verificarPassword($request);

        $this->service->eliminar($planilla);

        return response()->noContent();
    }

    /** Confirmación de contraseña del admin, mismo patrón que verificar-admin. */
    private function verificarPassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Contraseña incorrecta.',
            ]);
        }
    }

    public function actualizarDetalle(EditarPlanillaDetalleRequest $request, Planilla $planilla, PlanillaDetalle $detalle)
    {
        if ($detalle->planilla_id !== $planilla->id) {
            throw ValidationException::withMessages([
                'detalle' => 'Este detalle no pertenece a la planilla indicada.',
            ]);
        }

        $data = $request->validated();

        if (array_key_exists('dias_laborados', $data)) {
            $detalle->dias_laborados = (int) $data['dias_laborados'];
            $detalle->salario_devengado = $detalle->dias_laborados === 15
                ? $detalle->sueldo_quincenal
                : round((float) $detalle->sueldo_diario * $detalle->dias_laborados, 2);
        }

        if (array_key_exists('horas_extras_cantidad', $data)) {
            $detalle->horas_extras_cantidad = $data['horas_extras_cantidad'];
        }

        if (array_key_exists('valor_hora_extra', $data)) {
            $detalle->valor_hora_extra = $data['valor_hora_extra'];
        }

        if (array_key_exists('horas_extras_valor', $data)) {
            $detalle->horas_extras_valor = $data['horas_extras_valor'];
        } elseif (array_key_exists('horas_extras_cantidad', $data) || array_key_exists('valor_hora_extra', $data)) {
            $detalle->horas_extras_valor = round((float) $detalle->horas_extras_cantidad * (float) $detalle->valor_hora_extra, 2);
        }

        if (array_key_exists('bonificaciones', $data)) {
            $detalle->bonificaciones = $data['bonificaciones'];
        }

        $detalle->recalcularTotal();

        return response()->json([
            'detalle' => $detalle->fresh(),
            'tarifa_sugerida' => round((float) $detalle->sueldo_diario / 8 * 1.5, 2),
        ]);
    }
}
