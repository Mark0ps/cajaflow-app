<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarPagoRequest;
use App\Models\Empleado;
use App\Models\PagoPlanilla;
use App\Services\PagoPlanillaService;
use Illuminate\Support\Facades\Storage;

class PagoPlanillaController extends Controller
{
    public function __construct(private PagoPlanillaService $service)
    {
    }

    /**
     * Estado de cuenta de un empleado: quincenas pendientes/parciales,
     * ordenadas de la más antigua a la más reciente, con el total adeudado.
     * Esto alimenta la pantalla de checkboxes para seleccionar qué pagar.
     */
    public function estadoCuenta(Empleado $empleado)
    {
        $this->authorize('viewAny', PagoPlanilla::class);

        return response()->json($this->service->estadoCuenta($empleado));
    }

    public function store(RegistrarPagoRequest $request, Empleado $empleado)
    {
        $comprobantePath = null;

        if ($request->hasFile('comprobante')) {
            // Requiere `php artisan storage:link` para que sea accesible públicamente.
            $comprobantePath = $request->file('comprobante')->store('comprobantes-pago', 'public');
        }

        $pago = $this->service->registrarPago(
            empleado: $empleado,
            registradoPor: $request->user(),
            montoTotal: (float) $request->validated('monto_total'),
            fechaPago: $request->validated('fecha_pago'),
            metodo: $request->validated('metodo'),
            planillaDetalleIds: $request->validated('planilla_detalle_ids'),
            montosManuales: $request->validated('montos'),
            comprobantePath: $comprobantePath,
            notas: $request->validated('notas'),
        );

        return response()->json($pago, 201);
    }

    public function show(PagoPlanilla $pago)
    {
        $this->authorize('view', $pago);

        return $pago->load(['empleado:id,nombre,apellido', 'planillaDetalles', 'registradoPor:id,name']);
    }
}
