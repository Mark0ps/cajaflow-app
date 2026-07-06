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
use Illuminate\Support\Facades\Hash;
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

        if ($this->manejarValeAplicado($request, $vale)) {
            return response()->noContent();
        }

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

        if ($this->manejarValeAplicado($request, $vale)) {
            return response()->noContent();
        }

        if ($vale->comprobante_path) {
            Storage::disk('public')->delete($vale->comprobante_path);
        }

        $vale->delete();

        return response()->noContent();
    }

    /**
     * Un vale ya `aplicado_en_planilla` está bloqueado por defecto (ver
     * asegurarValeNoAplicado/asegurarValeNoAplicadoLibre), pero Admin puede
     * forzar la eliminación si la planilla del detalle sigue en `borrador` y
     * ese detalle todavía no tiene ningún pago aplicado — mismo criterio
     * protector de siempre (nunca se toca algo ya pagado), solo que ahora se
     * permite corregir el vale puntual sin el rodeo de quitar al empleado de
     * la planilla. Requiere contraseña de Admin, igual que cerrar/eliminar
     * planilla. Devuelve true si ya manejó la eliminación (forzada), false si
     * el vale no está aplicado y el caller debe seguir su flujo normal.
     */
    private function manejarValeAplicado(Request $request, Vale $vale): bool
    {
        if (! $vale->aplicado_en_planilla) {
            return false;
        }

        $detalle = $vale->planillaDetalle;
        $puedeForzar = $request->user()->isAdmin()
            && $detalle
            && $detalle->planilla->estado === 'borrador'
            && ! $detalle->pagosAplicados()->exists();

        if (! $puedeForzar) {
            throw ValidationException::withMessages([
                'vale' => 'Este vale ya fue aplicado en una planilla. Quita al empleado de la planilla en borrador para liberarlo antes de modificarlo.',
            ]);
        }

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Contraseña incorrecta.',
            ]);
        }

        $cierre = $vale->cierreCaja;

        if ($vale->comprobante_path) {
            Storage::disk('public')->delete($vale->comprobante_path);
        }

        $vale->delete();
        $detalle->recalcularTodo();

        if ($cierre) {
            $cierre->recalcularTotales();
            $cierre->save();
        }

        return true;
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
