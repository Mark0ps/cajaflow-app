<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarValeLibreRequest;
use App\Http\Requests\ActualizarValeRequest;
use App\Http\Requests\ValeLibreRequest;
use App\Http\Requests\ValeRequest;
use App\Models\CierreCaja;
use App\Models\Vale;
use App\Services\CierreCajaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ValeController extends Controller
{
    public function __construct(private CierreCajaService $service)
    {
    }

    /**
     * Mismo chequeo de pertenencia que en los recursos anidados de planillas:
     * sin esto, la autorización se evalúa contra el cierre de la URL pero la
     * mutación cae sobre un vale de OTRO cierre (y recalcula los totales del
     * cierre equivocado, dejando los del cierre real obsoletos).
     */
    private function verificarPertenencia(CierreCaja $cierre, Vale $vale): void
    {
        if ($vale->cierre_caja_id !== $cierre->id) {
            throw ValidationException::withMessages([
                'vale' => 'Este vale no pertenece al cierre indicado.',
            ]);
        }
    }

    public function store(ValeRequest $request, CierreCaja $cierre)
    {
        $vale = $this->service->agregarVale($cierre, $request->validated(), $request->user(), $request->input('motivo'));

        return response()->json($vale->load('empleado:id,nombre,apellido'), 201);
    }

    public function update(ActualizarValeRequest $request, CierreCaja $cierre, Vale $vale)
    {
        $this->authorize('update', $cierre);
        $this->verificarPertenencia($cierre, $vale);

        $vale = $this->service->actualizarVale(
            $cierre,
            $vale,
            $request->validated(),
            $request->user(),
            $request->input('motivo'),
        );

        return response()->json($vale->fresh('empleado:id,nombre,apellido'));
    }

    public function destroy(Request $request, CierreCaja $cierre, Vale $vale)
    {
        $this->authorize('update', $cierre);
        $this->verificarPertenencia($cierre, $vale);

        $this->service->eliminarVale($cierre, $vale, $request->user(), $request->input('motivo'));

        return response()->noContent();
    }

    /**
     * Vale libre: no depende de un turno de caja. Solo Admin, para poder
     * adelantarle dinero a un empleado sin necesidad de un cierre abierto.
     */
    public function storeLibre(ValeLibreRequest $request)
    {
        $comprobantePath = $request->hasFile('comprobante')
            ? $request->file('comprobante')->store('comprobantes-vales', 'public')
            : null;

        $vale = Vale::create([
            'empleado_id' => $request->validated('empleado_id'),
            'monto' => $request->validated('monto'),
            'descripcion' => $request->validated('descripcion'),
            'fecha_emision' => $request->validated('fecha_emision'),
            'comprobante_path' => $comprobantePath,
            'registrado_por' => $request->user()->id,
        ]);

        return response()->json($vale->load('empleado:id,nombre,apellido'), 201);
    }

    public function updateLibre(ActualizarValeLibreRequest $request, Vale $vale)
    {
        $this->verificarLibre($vale);
        $this->asegurarValeNoAplicadoLibre($vale);

        $data = $request->safe()->except('comprobante');

        if ($request->hasFile('comprobante')) {
            if ($vale->comprobante_path) {
                Storage::disk('public')->delete($vale->comprobante_path);
            }
            $data['comprobante_path'] = $request->file('comprobante')->store('comprobantes-vales', 'public');
        }

        $vale->update($data);

        return response()->json($vale->fresh('empleado:id,nombre,apellido'));
    }

    public function destroyLibre(Request $request, Vale $vale)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $this->verificarLibre($vale);
        $this->asegurarValeNoAplicadoLibre($vale);

        if ($vale->comprobante_path) {
            Storage::disk('public')->delete($vale->comprobante_path);
        }

        $vale->delete();

        return response()->noContent();
    }

    private function verificarLibre(Vale $vale): void
    {
        if ($vale->cierre_caja_id !== null) {
            throw ValidationException::withMessages([
                'vale' => 'Este vale pertenece a un turno de caja; edítalo desde el cierre correspondiente.',
            ]);
        }
    }

    private function asegurarValeNoAplicadoLibre(Vale $vale): void
    {
        if ($vale->aplicado_en_planilla) {
            throw ValidationException::withMessages([
                'vale' => 'Este vale ya fue aplicado en una planilla. Quita al empleado de la planilla en borrador para liberarlo antes de modificarlo.',
            ]);
        }
    }

    /** Vales de un empleado en un rango — insumo para el reporte "vales por empleado". */
    public function porEmpleado(Request $request, int $empleadoId)
    {
        // Datos de nómina de terceros: solo Admin/Secretaria (mismo criterio
        // que los reportes) — un cajero no debe poder listar vales ajenos.
        abort_unless($request->user()->isAdmin() || $request->user()->isSecretaria(), 403);

        $vales = Vale::where('empleado_id', $empleadoId)
            ->with('cierreCaja:id,fecha,turno')
            ->latest()
            ->paginate(20);

        return response()->json($vales);
    }
}
