<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Datos del dashboard tipo calendario. Solo Admin/Secretaria.
 * Todos los montos salen de cierres_caja: `total_ingreso` es el neto en caja
 * (efectivo + tarjeta + transferencia) y `diferencia` es contra A2 Food.
 * Los gastos externos NO entran aquí (tienen su propia pantalla).
 */
class DashboardController extends Controller
{
    /** Un registro por día del mes, para pintar el calendario. */
    public function resumenMensual(Request $request)
    {
        $this->autorizar($request);

        $data = $request->validate([
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $filas = CierreCaja::query()
            ->selectRaw('DATE(fecha) as dia')
            ->selectRaw('SUM(total_ingreso) as total_ingreso')
            ->selectRaw('SUM(total_gastos) as total_gastos')
            ->selectRaw('SUM(diferencia) as diferencia')
            ->selectRaw('COUNT(*) as cierres')
            ->whereYear('fecha', $data['anio'])
            ->whereMonth('fecha', $data['mes'])
            ->groupBy('dia')
            ->get()
            ->keyBy('dia');

        $diasDelMes = Carbon::create($data['anio'], $data['mes'], 1)->daysInMonth;
        $dias = [];

        for ($dia = 1; $dia <= $diasDelMes; $dia++) {
            $fecha = sprintf('%04d-%02d-%02d', $data['anio'], $data['mes'], $dia);
            $fila = $filas->get($fecha);

            $totalIngreso = round((float) ($fila->total_ingreso ?? 0), 2);
            $totalGastos = round((float) ($fila->total_gastos ?? 0), 2);

            $dias[] = [
                'fecha' => $fecha,
                'total_ingreso' => $totalIngreso,
                'total_gastos' => $totalGastos,
                'total_venta' => round($totalIngreso + $totalGastos, 2),
                'diferencia' => round((float) ($fila->diferencia ?? 0), 2),
                'tiene_cierres' => (bool) ($fila->cierres ?? 0),
            ];
        }

        return response()->json($dias);
    }

    /** Todos los cierres de un día con su desglose completo (detalle del calendario). */
    public function dia(Request $request)
    {
        $this->autorizar($request);

        $data = $request->validate(['fecha' => ['required', 'date']]);

        $cierres = CierreCaja::query()
            ->delDia($data['fecha'])
            ->with(['cajero:id,name', 'empleadosTurno:id,nombre,apellido', 'gastos.proveedor', 'vales.empleado'])
            ->orderBy('turno')
            ->get();

        $cierres->each(function (CierreCaja $cierre) {
            $cierre->total_venta = round((float) $cierre->total_ingreso + (float) $cierre->total_gastos, 2);
        });

        return response()->json([
            'resumen' => [
                'total_venta' => round((float) $cierres->sum('total_venta'), 2),
                'total_efectivo' => round((float) $cierres->sum('efectivo'), 2),
                
            ],
            'cierres' => $cierres,
        ]);
    }

    private function autorizar(Request $request): void
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isSecretaria(), 403);
    }
}
