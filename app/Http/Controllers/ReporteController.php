<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Gasto;
use App\Models\Vale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reportes agregados sobre cierres_caja. Solo Admin/Secretaria.
 * `venta_efectivo` se reconstruye como efectivo + gastos + vales (el campo
 * `efectivo` del cierre es el neto que quedó en la gaveta), igual que en
 * el resumen del cierre del frontend.
 */
class ReporteController extends Controller
{
    /** Ingresos/gastos/diferencia agregados por día, semana ISO o mes. */
    public function periodo(Request $request)
    {
        $this->autorizar($request);
        $data = $this->validarRango($request, [
            'agrupacion' => ['nullable', Rule::in(['diario', 'semanal', 'mensual'])],
        ]);

        $expresiones = [
            'diario' => 'DATE(fecha)',
            'semanal' => 'YEARWEEK(fecha, 3)',
            'mensual' => "DATE_FORMAT(fecha, '%Y-%m')",
        ];
        $agrupacion = $data['agrupacion'] ?? 'diario';
        $expresion = $expresiones[$agrupacion];

        $filas = CierreCaja::query()
            ->selectRaw("$expresion as periodo")
            ->selectRaw('SUM(total_ingreso) as total_ingreso')
            ->selectRaw('SUM(total_gastos) as total_gastos')
            ->selectRaw('SUM(total_vales) as total_vales')
            ->selectRaw('SUM(diferencia) as diferencia')
            ->selectRaw('COUNT(*) as cierres')
            ->whereBetween('fecha', [$data['desde'], $data['hasta']])
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->map(function ($fila) use ($agrupacion) {
                // YEARWEEK devuelve p. ej. 202627 — se presenta como 2026-W27.
                $periodo = $agrupacion === 'semanal'
                    ? preg_replace('/^(\d{4})(\d{2})$/', '$1-W$2', (string) $fila->periodo)
                    : (string) $fila->periodo;

                return [
                    'periodo' => $periodo,
                    'total_ingreso' => round((float) $fila->total_ingreso, 2),
                    'total_gastos' => round((float) $fila->total_gastos, 2),
                    'total_vales' => round((float) $fila->total_vales, 2),
                    'diferencia' => round((float) $fila->diferencia, 2),
                    'cierres' => (int) $fila->cierres,
                ];
            });

        return response()->json($filas);
    }

    /** Desglose de la venta por método de pago. */
    public function metodoPago(Request $request)
    {
        $this->autorizar($request);
        $data = $this->validarRango($request);

        $fila = CierreCaja::query()
            ->selectRaw('SUM(efectivo + total_gastos + total_vales) as venta_efectivo')
            ->selectRaw('SUM(tarjeta_credito) as tarjeta')
            ->selectRaw('SUM(transferencia) as transferencia')
            ->whereBetween('fecha', [$data['desde'], $data['hasta']])
            ->first();

        return response()->json([
            'efectivo' => round((float) ($fila->venta_efectivo ?? 0), 2),
            'tarjeta' => round((float) ($fila->tarjeta ?? 0), 2),
            'transferencia' => round((float) ($fila->transferencia ?? 0), 2),
        ]);
    }

    /**
     * Totales de ingresos contra gastos (de caja y externos) del período.
     * Filtro opcional `factura_nominal` (con/sin) sobre proveedor.factura_nominal:
     * "sin" incluye tanto proveedores informales del catálogo como gastos con
     * proveedor de texto libre (nunca tienen ese dato en el catálogo).
     * Filtro opcional `categoria` sobre gastos.categoria.
     */
    public function gastosVsIngresos(Request $request)
    {
        $this->autorizar($request);
        $data = $this->validarRango($request, [
            'factura_nominal' => ['nullable', Rule::in(['con', 'sin'])],
            'categoria' => ['nullable', Rule::in(['gasto_operativo', 'pago_tarjeta_credito', 'servicios_publicos'])],
        ]);

        $filtro = $data['factura_nominal'] ?? null;
        $filtrarProveedor = function ($query) use ($filtro) {
            if ($filtro === 'con') {
                $query->whereHas('proveedor', fn ($q) => $q->where('factura_nominal', true));
            } elseif ($filtro === 'sin') {
                $query->where(function ($q) {
                    $q->whereNull('proveedor_id')->orWhereHas('proveedor', fn ($p) => $p->where('factura_nominal', false));
                });
            }
        };

        $filtroCategoria = $data['categoria'] ?? null;
        $filtrarCategoria = function ($query) use ($filtroCategoria) {
            if ($filtroCategoria) {
                $query->where('categoria', $filtroCategoria);
            }
        };

        $totalIngresos = round((float) CierreCaja::query()
            ->whereBetween('fecha', [$data['desde'], $data['hasta']])
            ->sum('total_ingreso'), 2);

        $totalGastosCaja = round((float) Gasto::query()
            ->where('es_externo', false)
            ->whereHas('cierreCaja', fn ($q) => $q->whereBetween('fecha', [$data['desde'], $data['hasta']]))
            ->tap($filtrarProveedor)
            ->tap($filtrarCategoria)
            ->sum('valor'), 2);

        // Los gastos externos no tienen fecha propia: se usa su fecha de registro.
        $totalGastosExternos = round((float) Gasto::query()
            ->where('es_externo', true)
            ->whereBetween('created_at', [$data['desde'].' 00:00:00', $data['hasta'].' 23:59:59'])
            ->tap($filtrarProveedor)
            ->tap($filtrarCategoria)
            ->sum('valor'), 2);

        // Independiente del filtro de categoría seleccionado: siempre muestra
        // cuánto se reembolsó por tarjetas personales de admin en el período.
        $totalPagoTarjetaCredito = round((float) Gasto::query()
            ->where('es_externo', true)
            ->where('categoria', 'pago_tarjeta_credito')
            ->whereBetween('created_at', [$data['desde'].' 00:00:00', $data['hasta'].' 23:59:59'])
            ->sum('valor'), 2);

        return response()->json([
            'total_ingresos' => $totalIngresos,
            'total_gastos_caja' => $totalGastosCaja,
            'total_gastos_externos' => $totalGastosExternos,
            'total_gastos' => round($totalGastosCaja + $totalGastosExternos, 2),
            'balance' => round($totalIngresos - $totalGastosCaja - $totalGastosExternos, 2),
            'total_pago_tarjeta_credito' => $totalPagoTarjetaCredito,
        ]);
    }

    /**
     * PDF del reporte de gastos externos, con checkboxes de filtro (categoría
     * y factura nominal, ambos multi-selección). Corrige un problema real del
     * Excel "COMPRAS DEL MES" del negocio: ahí la columna de pagos a tarjeta
     * de crédito nunca se sumaba al final — aquí la fila de TOTALES suma las
     * 4 columnas numéricas sin excepción, y se agrega una caja de "Totales
     * por categoría" debajo de la tabla.
     */
    public function exportarGastosExternosPdf(Request $request)
    {
        $this->autorizar($request);
        $data = $this->validarRango($request, [
            'categoria' => ['nullable', 'array'],
            'categoria.*' => [Rule::in(['gasto_operativo', 'pago_tarjeta_credito', 'servicios_publicos'])],
            'factura_nominal' => ['nullable', 'array'],
            'factura_nominal.*' => [Rule::in(['con', 'sin'])],
        ]);

        $categorias = $data['categoria'] ?? [];
        $facturaNominal = $data['factura_nominal'] ?? [];

        $gastos = Gasto::query()
            ->where('es_externo', true)
            ->whereBetween('fecha_emision', [$data['desde'], $data['hasta']])
            ->when(count($categorias) > 0, fn ($q) => $q->whereIn('categoria', $categorias))
            // Si ambas opciones (con/sin) están marcadas, o ninguna, no filtra.
            ->when(count($facturaNominal) === 1, function ($q) use ($facturaNominal) {
                if ($facturaNominal[0] === 'con') {
                    $q->whereHas('proveedor', fn ($p) => $p->where('factura_nominal', true));
                } else {
                    $q->where(fn ($sub) => $sub->whereNull('proveedor_id')
                        ->orWhereHas('proveedor', fn ($p) => $p->where('factura_nominal', false)));
                }
            })
            ->with('proveedor')
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();

        $etiquetasCategoria = [
            'gasto_operativo' => 'Gasto operativo',
            'pago_tarjeta_credito' => 'Pago de tarjeta de crédito',
            'servicios_publicos' => 'Servicios públicos / Gastos fijos',
        ];

        $filas = $gastos->map(function (Gasto $gasto) use ($etiquetasCategoria) {
            $valor = (float) $gasto->valor;
            $efectivo = $gasto->tipo_pago === 'efectivo' ? $valor : 0.0;
            $tarjeta = $gasto->tipo_pago === 'tarjeta' ? $valor : 0.0;
            // Los cheques son, igual que las transferencias, pagos bancarios
            // (no efectivo/tarjeta) — el formato aprobado no tiene columna
            // propia para cheque, así que cae aquí junto a transferencia.
            $transferencia = in_array($gasto->tipo_pago, ['transferencia', 'cheque'], true) ? $valor : 0.0;

            return [
                'fecha' => $gasto->fecha_emision?->format('d/m/Y'),
                'proveedor' => $gasto->nombreProveedor(),
                'numero_factura' => $gasto->numero_factura ?: ($gasto->factura_pendiente ? 'Pendiente' : 'N/A'),
                'categoria' => $etiquetasCategoria[$gasto->categoria] ?? $gasto->categoria,
                'efectivo' => $efectivo,
                'tarjeta' => $tarjeta,
                'transferencia' => $transferencia,
                'total' => $valor,
            ];
        });

        $totales = [
            'efectivo' => round((float) $filas->sum('efectivo'), 2),
            'tarjeta' => round((float) $filas->sum('tarjeta'), 2),
            'transferencia' => round((float) $filas->sum('transferencia'), 2),
            'total' => round((float) $filas->sum('total'), 2),
        ];

        $totalesPorCategoria = [
            'gasto_operativo' => round((float) $gastos->where('categoria', 'gasto_operativo')->sum('valor'), 2),
            'servicios_publicos' => round((float) $gastos->where('categoria', 'servicios_publicos')->sum('valor'), 2),
            'pago_tarjeta_credito' => round((float) $gastos->where('categoria', 'pago_tarjeta_credito')->sum('valor'), 2),
        ];
        $totalGeneral = round(array_sum($totalesPorCategoria), 2);

        $pdf = Pdf::loadView('pdf.reporte-gastos-externos', [
            'negocio' => 'Inversiones PG Store S. de R.L.',
            'periodo' => \Carbon\Carbon::parse($data['desde'])->format('d/m/Y').' — '.\Carbon\Carbon::parse($data['hasta'])->format('d/m/Y'),
            'filas' => $filas,
            'totales' => $totales,
            'totalesPorCategoria' => $totalesPorCategoria,
            'totalGeneral' => $totalGeneral,
        ])->setPaper('letter', 'landscape');

        return $pdf->download("reporte-gastos-externos-{$data['desde']}-a-{$data['hasta']}.pdf");
    }

    /** Total de vales por empleado en el período (fecha del cierre al que pertenecen). */
    public function valesPorEmpleado(Request $request)
    {
        $this->autorizar($request);
        $data = $this->validarRango($request);

        $filas = Vale::query()
            ->join('cierres_caja', 'cierres_caja.id', '=', 'vales.cierre_caja_id')
            ->join('empleados', 'empleados.id', '=', 'vales.empleado_id')
            ->selectRaw('vales.empleado_id')
            ->selectRaw("CONCAT(empleados.nombre, ' ', empleados.apellido) as empleado")
            ->selectRaw('SUM(vales.monto) as total_vales')
            ->selectRaw('COUNT(*) as cantidad')
            ->whereBetween('cierres_caja.fecha', [$data['desde'], $data['hasta']])
            ->groupBy('vales.empleado_id', 'empleados.nombre', 'empleados.apellido')
            ->orderByDesc('total_vales')
            ->get()
            ->map(fn ($fila) => [
                'empleado_id' => (int) $fila->empleado_id,
                'empleado' => $fila->empleado,
                'total_vales' => round((float) $fila->total_vales, 2),
                'cantidad' => (int) $fila->cantidad,
            ]);

        return response()->json($filas);
    }

    private function validarRango(Request $request, array $extra = []): array
    {
        return $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            ...$extra,
        ]);
    }

    private function autorizar(Request $request): void
    {
        abort_unless($request->user()->isAdmin() || $request->user()->isSecretaria(), 403);
    }
}
