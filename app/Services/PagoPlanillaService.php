<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\PagoPlanilla;
use App\Models\PlanillaDetalle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoPlanillaService
{
    /**
     * Registra un evento de pago y lo reparte sobre una o varias quincenas
     * pendientes seleccionadas por el admin (el "checkbox" del estado de
     * cuenta). Si no se especifica cuánto aplicar a cada una, se reparte
     * automáticamente en orden (más antigua primero) hasta agotar el monto.
     *
     * @param  array<int>  $planillaDetalleIds  IDs seleccionados, en el orden que se deben cubrir
     * @param  array<int,float>|null  $montosManuales  [planilla_detalle_id => monto] si el reparto es manual
     */
    public function registrarPago(
        Empleado $empleado,
        User $registradoPor,
        float $montoTotal,
        string $fechaPago,
        string $metodo,
        array $planillaDetalleIds,
        ?array $montosManuales = null,
        ?string $comprobantePath = null,
        ?string $notas = null,
    ): PagoPlanilla {
        return DB::transaction(function () use (
            $empleado, $registradoPor, $montoTotal, $fechaPago, $metodo,
            $planillaDetalleIds, $montosManuales, $comprobantePath, $notas
        ) {
            $detalles = PlanillaDetalle::whereIn('id', $planillaDetalleIds)
                ->where('empleado_id', $empleado->id)
                ->where('estado_pago', '!=', 'pagado')
                ->orderBy('created_at')
                ->get()
                ->keyBy('id');

            if ($detalles->isEmpty()) {
                throw ValidationException::withMessages([
                    'planilla_detalle_ids' => 'No hay quincenas pendientes válidas seleccionadas para este empleado.',
                ]);
            }

            $reparto = $montosManuales
                ? $this->validarRepartoManual($detalles, $montosManuales, $montoTotal)
                : $this->repartirAutomatico($detalles, $planillaDetalleIds, $montoTotal);

            $pago = PagoPlanilla::create([
                'empleado_id' => $empleado->id,
                'fecha_pago' => $fechaPago,
                'monto_total' => $montoTotal,
                'metodo' => $metodo,
                'comprobante_path' => $comprobantePath,
                'registrado_por' => $registradoPor->id,
                'notas' => $notas,
            ]);

            foreach ($reparto as $planillaDetalleId => $monto) {
                if ($monto <= 0) {
                    continue;
                }

                $pago->planillaDetalles()->attach($planillaDetalleId, ['monto_aplicado' => $monto]);
                $detalles[$planillaDetalleId]->aplicarPago($monto);
            }

            return $pago->load('planillaDetalles');
        });
    }

    /** @return array<int,float> [planilla_detalle_id => monto] */
    private function repartirAutomatico($detalles, array $ordenIds, float $montoTotal): array
    {
        $restante = $montoTotal;
        $reparto = [];

        foreach ($ordenIds as $id) {
            if ($restante <= 0 || ! isset($detalles[$id])) {
                continue;
            }

            $saldo = (float) $detalles[$id]->saldo_pendiente;
            $aplicar = min($saldo, $restante);
            $reparto[$id] = round($aplicar, 2);
            $restante -= $aplicar;
        }

        if ($restante > 0.009) {
            throw ValidationException::withMessages([
                'monto_total' => 'El monto pagado excede el saldo pendiente total de las quincenas seleccionadas. Sobran L. ' . number_format($restante, 2),
            ]);
        }

        return $reparto;
    }

    /** @return array<int,float> */
    private function validarRepartoManual($detalles, array $montosManuales, float $montoTotal): array
    {
        $sumaManual = array_sum($montosManuales);

        if (abs($sumaManual - $montoTotal) > 0.01) {
            throw ValidationException::withMessages([
                'montos' => 'La suma de los montos por quincena (L. ' . number_format($sumaManual, 2) . ') no coincide con el monto total del pago (L. ' . number_format($montoTotal, 2) . ').',
            ]);
        }

        foreach ($montosManuales as $id => $monto) {
            if (! isset($detalles[$id])) {
                throw ValidationException::withMessages(['montos' => "La quincena {$id} no es válida para este pago."]);
            }
            if ($monto > (float) $detalles[$id]->saldo_pendiente + 0.01) {
                throw ValidationException::withMessages(['montos' => "El monto asignado a la quincena {$id} supera su saldo pendiente."]);
            }
        }

        return $montosManuales;
    }

    /**
     * Estado de cuenta: todas las quincenas no pagadas de un empleado,
     * ordenadas de la más antigua a la más reciente, con el acumulado total.
     */
    public function estadoCuenta(Empleado $empleado): array
    {
        $pendientes = PlanillaDetalle::with('planilla:id,anio,mes,quincena,periodo_inicio,periodo_fin')
            ->where('empleado_id', $empleado->id)
            ->where('estado_pago', '!=', 'pagado')
            ->orderBy('created_at')
            ->get();

        return [
            'empleado' => $empleado->only(['id', 'nombre', 'apellido']),
            'quincenas_pendientes' => $pendientes,
            'total_adeudado' => (float) $pendientes->sum('saldo_pendiente'),
            'cantidad_quincenas_atrasadas' => $pendientes->count(),
        ];
    }
}
