<?php

namespace App\Services;

use App\Models\CompraTienda;
use App\Models\Empleado;
use App\Models\LlegadaTarde;
use App\Models\Planilla;
use App\Models\PlanillaDetalle;
use App\Models\PrestamoAbono;
use App\Models\User;
use App\Models\Vale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanillaService
{
    /**
     * Genera la planilla de una quincena para los empleados seleccionados a
     * mano por el admin (sin default automático), sumando automáticamente
     * sus deducciones pendientes (vales, compras en tienda, llegadas tarde,
     * abono de préstamo).
     *
     * IMPORTANTE: esto solo calcula lo que se debe. NO paga a nadie — el
     * pago real ocurre después, vía PagoPlanillaService, y puede tardar
     * (ver estado_pago en PlanillaDetalle).
     *
     * @param  array<int>  $empleadoIds
     */
    public function generar(int $anio, int $mes, int $quincena, array $empleadoIds, User $generadaPor): Planilla
    {
        if (Planilla::where('anio', $anio)->where('mes', $mes)->where('quincena', $quincena)->exists()) {
            throw ValidationException::withMessages([
                'quincena' => 'Ya existe una planilla generada para este período.',
            ]);
        }

        [$periodoInicio, $periodoFin] = $this->calcularPeriodo($anio, $mes, $quincena);

        return DB::transaction(function () use ($anio, $mes, $quincena, $empleadoIds, $periodoInicio, $periodoFin, $generadaPor) {
            $planilla = Planilla::create([
                'anio' => $anio,
                'mes' => $mes,
                'quincena' => $quincena,
                'periodo_inicio' => $periodoInicio,
                'periodo_fin' => $periodoFin,
                'estado' => 'borrador',
                'generada_por' => $generadaPor->id,
            ]);

            Empleado::whereIn('id', $empleadoIds)->get()->each(
                fn (Empleado $empleado) => $this->generarDetalleEmpleado($planilla, $empleado, $periodoInicio, $periodoFin, $quincena)
            );

            return $planilla->load('detalles.empleado');
        });
    }

    private function generarDetalleEmpleado(Planilla $planilla, Empleado $empleado, Carbon $periodoInicio, Carbon $periodoFin, int $quincena): PlanillaDetalle
    {
        $sueldoDiario = round($empleado->sueldo_quincenal / 15, 2);
        $diasLaborados = 15; // TODO: ajustar manualmente si el frontend permite editar antes de cerrar
        $salarioDevengado = $diasLaborados === 15
            ? $empleado->sueldo_quincenal
            : round($sueldoDiario * $diasLaborados, 2);

        $detalle = PlanillaDetalle::create([
            'planilla_id' => $planilla->id,
            'empleado_id' => $empleado->id,
            'sueldo_quincenal' => $empleado->sueldo_quincenal,
            'sueldo_diario' => $sueldoDiario,
            'dias_laborados' => $diasLaborados,
            'horas_extras_valor' => 0,
            'salario_devengado' => $salarioDevengado,
            'total_a_pagar' => $salarioDevengado, // se recalcula abajo
        ]);

        $totalVales = $this->aplicarVales($empleado, $detalle, $periodoInicio, $periodoFin);
        $totalCompras = $this->aplicarComprasTienda($empleado, $detalle, $periodoInicio, $periodoFin);
        $totalLlegadasTarde = $this->aplicarLlegadasTarde($empleado, $detalle, $periodoInicio, $periodoFin);
        $totalAbonoPrestamo = $this->aplicarAbonoPrestamo($empleado, $detalle, $quincena);

        $totalDeducciones = $totalVales + $totalCompras + $totalLlegadasTarde + $totalAbonoPrestamo;
        $totalAPagar = max(0, $salarioDevengado - $totalDeducciones);

        $detalle->update([
            'total_vales' => $totalVales,
            'total_compras_tienda' => $totalCompras,
            'total_llegadas_tarde' => $totalLlegadasTarde,
            'total_abono_prestamo' => $totalAbonoPrestamo,
            'total_deducciones' => $totalDeducciones,
            'total_a_pagar' => $totalAPagar,
            'saldo_pendiente' => $totalAPagar,
            'estado_pago' => 'pendiente',
        ]);

        return $detalle;
    }

    private function aplicarVales(Empleado $empleado, PlanillaDetalle $detalle, Carbon $inicio, Carbon $fin): float
    {
        $vales = Vale::where('empleado_id', $empleado->id)
            ->where('aplicado_en_planilla', false)
            ->whereHas('cierreCaja', fn ($q) => $q->whereBetween('fecha', [$inicio, $fin]))
            ->get();

        $vales->each(fn (Vale $v) => $v->update(['aplicado_en_planilla' => true, 'planilla_detalle_id' => $detalle->id]));

        return (float) $vales->sum('monto');
    }

    private function aplicarComprasTienda(Empleado $empleado, PlanillaDetalle $detalle, Carbon $inicio, Carbon $fin): float
    {
        $compras = CompraTienda::where('empleado_id', $empleado->id)
            ->whereNull('planilla_detalle_id')
            ->whereBetween('fecha', [$inicio, $fin])
            ->get();

        $compras->each(fn (CompraTienda $c) => $c->update(['planilla_detalle_id' => $detalle->id]));

        return (float) $compras->sum('valor');
    }

    private function aplicarLlegadasTarde(Empleado $empleado, PlanillaDetalle $detalle, Carbon $inicio, Carbon $fin): float
    {
        $llegadas = LlegadaTarde::where('empleado_id', $empleado->id)
            ->whereNull('planilla_detalle_id')
            ->whereBetween('fecha', [$inicio, $fin])
            ->get();

        $llegadas->each(fn (LlegadaTarde $l) => $l->update(['planilla_detalle_id' => $detalle->id]));

        return (float) $llegadas->sum('valor_deduccion');
    }

    /**
     * Aplica la cuota del préstamo activo del empleado (si tiene uno).
     * - metodo_cobro = quincenal → se aplica en ambas quincenas del mes.
     * - metodo_cobro = mensual   → se aplica solo en la segunda quincena.
     */
    private function aplicarAbonoPrestamo(Empleado $empleado, PlanillaDetalle $detalle, int $quincena): float
    {
        $prestamo = $empleado->prestamos()->where('estado', 'activo')->first();

        if (! $prestamo || $prestamo->saldo_pendiente <= 0) {
            return 0;
        }

        if ($prestamo->metodo_cobro === 'mensual' && $quincena !== 2) {
            return 0;
        }

        $monto = min((float) $prestamo->monto_cuota, (float) $prestamo->saldo_pendiente);
        $prestamo->aplicarAbono($monto, $detalle->id);

        return $monto;
    }

    private function calcularPeriodo(int $anio, int $mes, int $quincena): array
    {
        if ($quincena === 1) {
            $inicio = Carbon::create($anio, $mes, 1);
            $fin = Carbon::create($anio, $mes, 15);
        } else {
            $inicio = Carbon::create($anio, $mes, 16);
            $fin = Carbon::create($anio, $mes, 1)->endOfMonth();
        }

        return [$inicio, $fin];
    }

    public function cerrar(Planilla $planilla): Planilla
    {
        if ($planilla->estado === 'cerrada') {
            throw ValidationException::withMessages(['estado' => 'Esta planilla ya está cerrada.']);
        }

        $planilla->update(['estado' => 'cerrada', 'cerrada_en' => now()]);

        return $planilla;
    }

    /**
     * Edita la lista de empleados de una planilla en borrador. A los que se
     * agregan se les genera su planilla_detalle igual que en generar(). A
     * los que se quitan se les revierte todo lo aplicado automáticamente
     * (vales/compras/llegadas tarde, abono de préstamo) antes de borrar su
     * detalle. Se rechaza si algún empleado a quitar ya tiene un pago
     * registrado contra su detalle.
     *
     * @param  array<int>  $empleadoIds
     */
    public function actualizarEmpleados(Planilla $planilla, array $empleadoIds): Planilla
    {
        if ($planilla->estado !== 'borrador') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se puede editar una planilla en borrador.',
            ]);
        }

        return DB::transaction(function () use ($planilla, $empleadoIds) {
            $detallesActuales = $planilla->detalles()->with('empleado')->get()->keyBy('empleado_id');
            $idsActuales = $detallesActuales->keys()->all();

            $idsAQuitar = array_diff($idsActuales, $empleadoIds);
            $idsAAgregar = array_diff($empleadoIds, $idsActuales);

            foreach ($idsAQuitar as $empleadoId) {
                $detalle = $detallesActuales[$empleadoId];

                if ($detalle->pagosAplicados()->exists()) {
                    throw ValidationException::withMessages([
                        'empleado_ids' => "No se puede quitar a {$detalle->empleado->nombreCompleto()} porque ya tiene un pago registrado contra esta planilla.",
                    ]);
                }
            }

            foreach ($idsAQuitar as $empleadoId) {
                $this->revertirDetalle($detallesActuales[$empleadoId]);
            }

            [$periodoInicio, $periodoFin] = $this->calcularPeriodo($planilla->anio, $planilla->mes, $planilla->quincena);

            Empleado::whereIn('id', $idsAAgregar)->get()->each(
                fn (Empleado $empleado) => $this->generarDetalleEmpleado($planilla, $empleado, $periodoInicio, $periodoFin, $planilla->quincena)
            );

            return $planilla->fresh('detalles.empleado');
        });
    }

    /**
     * Elimina una planilla completa en borrador, revirtiendo primero todo lo
     * aplicado automáticamente a cada empleado (mismo mecanismo que
     * actualizarEmpleados). Rechaza la operación si algún detalle ya tiene
     * un pago registrado.
     */
    public function eliminar(Planilla $planilla): void
    {
        if ($planilla->estado !== 'borrador') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se puede eliminar una planilla en borrador.',
            ]);
        }

        DB::transaction(function () use ($planilla) {
            $detalles = $planilla->detalles()->with('empleado')->get();

            $empleadosConPago = $detalles->filter(fn (PlanillaDetalle $d) => $d->pagosAplicados()->exists());

            if ($empleadosConPago->isNotEmpty()) {
                $nombres = $empleadosConPago->map(fn (PlanillaDetalle $d) => $d->empleado->nombreCompleto())->implode(', ');

                throw ValidationException::withMessages([
                    'planilla' => "No se puede eliminar: ya hay pagos registrados para: {$nombres}.",
                ]);
            }

            $detalles->each(fn (PlanillaDetalle $d) => $this->revertirDetalle($d));

            $planilla->delete();
        });
    }

    /**
     * Revierte todo lo que generarDetalleEmpleado() aplicó automáticamente
     * a un detalle (vales, compras en tienda, llegadas tarde, abono de
     * préstamo) y luego lo borra. El llamador es responsable de verificar
     * primero que el detalle no tenga pagos aplicados.
     */
    private function revertirDetalle(PlanillaDetalle $detalle): void
    {
        $detalle->vales()->update(['aplicado_en_planilla' => false, 'planilla_detalle_id' => null]);
        $detalle->comprasTienda()->update(['planilla_detalle_id' => null]);
        $detalle->llegadasTarde()->update(['planilla_detalle_id' => null]);

        $detalle->prestamoAbonos()->with('prestamo')->get()->each(function (PrestamoAbono $abono) {
            $prestamo = $abono->prestamo;

            if ($prestamo) {
                $prestamo->saldo_pendiente = round((float) $prestamo->saldo_pendiente + (float) $abono->monto, 2);
                if ($prestamo->estado === 'pagado' && $prestamo->saldo_pendiente > 0) {
                    $prestamo->estado = 'activo';
                }
                $prestamo->save();
            }

            $abono->delete();
        });

        $detalle->delete();
    }
}
