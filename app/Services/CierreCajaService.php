<?php

namespace App\Services;

use App\Models\CierreCaja;
use App\Models\Empleado;
use App\Models\Gasto;
use App\Models\User;
use App\Models\Vale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CierreCajaService
{
    /**
     * Abre un turno de caja. Un cajero solo puede tener un cierre abierto
     * a la vez para (fecha, turno) — lo impide el unique de la migración,
     * pero validamos antes para dar un mensaje claro en vez de un 500.
     */
    public function abrirTurno(User $cajero, array $data): CierreCaja
    {
        $existe = CierreCaja::where('fecha', $data['fecha'])
            ->where('turno', $data['turno'])
            ->where('user_id', $cajero->id)
            ->exists();

        if ($existe) {
            throw ValidationException::withMessages([
                'turno' => 'Ya existe un cierre para este cajero, fecha y turno.',
            ]);
        }

        return CierreCaja::create([
            'fecha' => $data['fecha'],
            'turno' => $data['turno'],
            'user_id' => $cajero->id,
            'monto_inicial' => $data['monto_inicial'],
            'estado' => 'abierto',
        ]);
    }

    public function agregarEmpleadoTurno(CierreCaja $cierre, Empleado $empleado): void
    {
        $this->asegurarAbierto($cierre);
        $cierre->empleadosTurno()->syncWithoutDetaching([$empleado->id]);
    }

    public function quitarEmpleadoTurno(CierreCaja $cierre, Empleado $empleado): void
    {
        $this->asegurarAbierto($cierre);
        $cierre->empleadosTurno()->detach($empleado->id);
    }

    /**
     * Actualiza los ingresos por método de pago y la venta reportada por el
     * sistema A2 Food. Se puede llamar varias veces mientras el turno esté
     * abierto (el cajero va corrigiendo montos antes de cerrar).
     */
    public function actualizarIngresos(CierreCaja $cierre, array $data): CierreCaja
    {
        $this->asegurarAbierto($cierre);

        $cierre->fill([
            'efectivo' => $data['efectivo'] ?? $cierre->efectivo,
            'tarjeta_credito' => $data['tarjeta_credito'] ?? $cierre->tarjeta_credito,
            'transferencia' => $data['transferencia'] ?? $cierre->transferencia,
            'venta_sistema_a2' => $data['venta_sistema_a2'] ?? $cierre->venta_sistema_a2,
        ]);

        $cierre->recalcularTotales();
        $cierre->save();

        return $cierre;
    }

    public function agregarGasto(CierreCaja $cierre, User $agregadoPor, array $data): Gasto
    {
        $this->asegurarAbierto($cierre);

        $gasto = $cierre->gastos()->create([
            'proveedor_id' => $data['proveedor_id'] ?? null,
            'proveedor_nombre_libre' => $data['proveedor_nombre_libre'] ?? null,
            'descripcion' => $data['descripcion'],
            'numero_factura' => $data['numero_factura'] ?? null,
            'factura_pendiente' => empty($data['numero_factura']),
            'tipo_pago' => $data['tipo_pago'],
            'valor' => $data['valor'],
            'es_externo' => false,
            'agregado_por' => $agregadoPor->id,
        ]);

        $cierre->recalcularTotales();
        $cierre->save();

        return $gasto;
    }

    public function eliminarGasto(CierreCaja $cierre, Gasto $gasto): void
    {
        $this->asegurarAbierto($cierre);
        $gasto->delete();
        $cierre->recalcularTotales();
        $cierre->save();
    }

    public function agregarVale(CierreCaja $cierre, array $data): Vale
    {
        $this->asegurarAbierto($cierre);

        $vale = $cierre->vales()->create([
            'empleado_id' => $data['empleado_id'],
            'monto' => $data['monto'],
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        $cierre->recalcularTotales();
        $cierre->save();

        return $vale;
    }

    public function eliminarVale(CierreCaja $cierre, Vale $vale): void
    {
        $this->asegurarAbierto($cierre);
        $vale->delete();
        $cierre->recalcularTotales();
        $cierre->save();
    }

    /**
     * Cierra el turno: recalcula todo por última vez y bloquea edición.
     * A partir de aquí, gastos/vales/ingresos de este cierre son inmutables
     * (si algo quedó mal, la Secretaria/Admin corrige aparte, no reabriendo).
     */
    public function cerrar(CierreCaja $cierre): CierreCaja
    {
        $this->asegurarAbierto($cierre);

        return DB::transaction(function () use ($cierre) {
            $cierre->recalcularTotales();
            $cierre->estado = 'cerrado';
            $cierre->save();

            return $cierre->fresh(['gastos', 'vales', 'empleadosTurno']);
        });
    }

    /** Secretaria marca que ya revisó el cierre (factura pendiente, gastos, etc). */
    public function marcarRevisado(CierreCaja $cierre, User $secretaria): CierreCaja
    {
        if ($cierre->estado !== 'cerrado') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se pueden revisar cierres ya cerrados por el cajero.',
            ]);
        }

        $cierre->update([
            'estado' => 'revisado_secretaria',
            'revisado_por' => $secretaria->id,
            'revisado_en' => now(),
        ]);

        return $cierre;
    }

    private function asegurarAbierto(CierreCaja $cierre): void
    {
        if ($cierre->estado !== 'abierto') {
            throw ValidationException::withMessages([
                'estado' => 'Este cierre ya no está abierto y no se puede modificar.',
            ]);
        }
    }
}
