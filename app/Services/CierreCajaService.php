<?php

namespace App\Services;

use App\Models\CierreCaja;
use App\Models\CierreFoto;
use App\Models\Empleado;
use App\Models\Gasto;
use App\Models\Historial;
use App\Models\MovimientoEfectivo;
use App\Models\User;
use App\Models\Vale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
     * Actualiza los ingresos por método de pago, la venta reportada por el
     * sistema A2 Food y las observaciones. Se puede llamar varias veces
     * mientras el turno esté abierto (el cajero va corrigiendo montos antes
     * de cerrar). Admin puede seguir editando con el cierre ya cerrado,
     * indicando un motivo obligatorio (ver autorizarEdicion()).
     */
    public function actualizarIngresos(CierreCaja $cierre, array $data, User $user, ?string $motivo = null): CierreCaja
    {
        $this->autorizarEdicion($cierre, $user, $motivo);

        $campos = ['efectivo', 'tarjeta_credito', 'transferencia', 'venta_sistema_a2', 'observaciones'];
        $antes = $cierre->only($campos);

        $cierre->fill([
            'efectivo' => $data['efectivo'] ?? $cierre->efectivo,
            'tarjeta_credito' => $data['tarjeta_credito'] ?? $cierre->tarjeta_credito,
            'transferencia' => $data['transferencia'] ?? $cierre->transferencia,
            'venta_sistema_a2' => $data['venta_sistema_a2'] ?? $cierre->venta_sistema_a2,
            'observaciones' => array_key_exists('observaciones', $data) ? $data['observaciones'] : $cierre->observaciones,
        ]);

        $cierre->recalcularTotales();
        $cierre->save();

        $this->registrarHistorialSiAplica($user, $motivo, 'cierres_caja', $cierre->id, 'editado', $antes, $cierre->only($campos));

        return $cierre;
    }

    public function agregarGasto(CierreCaja $cierre, User $agregadoPor, array $data, ?string $motivo = null): Gasto
    {
        $this->autorizarEdicion($cierre, $agregadoPor, $motivo);

        $proveedorId = $data['proveedor_id'] ?? null;

        $gastoData = Gasto::normalizarFacturaPorProveedor([
            'proveedor_id' => $proveedorId,
            'proveedor_nombre_libre' => $data['proveedor_nombre_libre'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'numero_factura' => $data['numero_factura'] ?? null,
            'factura_pendiente' => empty($data['numero_factura']),
            // Los gastos de un cierre de caja son siempre en efectivo — el
            // usuario no elige tipo_pago aquí (sí puede en GastoExternoRequest).
            'tipo_pago' => 'efectivo',
            'valor' => $data['valor'],
            'es_externo' => false,
            'agregado_por' => $agregadoPor->id,
        ], $proveedorId);

        $gasto = $cierre->gastos()->create($gastoData);

        $cierre->recalcularTotales();
        $cierre->save();

        $this->registrarHistorialSiAplica($agregadoPor, $motivo, 'gastos', $gasto->id, 'creado', [], $gasto->toArray());

        return $gasto;
    }

    public function actualizarGasto(CierreCaja $cierre, Gasto $gasto, array $data, User $user, ?string $motivo = null): Gasto
    {
        $this->autorizarEdicion($cierre, $user, $motivo);

        $antes = $gasto->toArray();

        if (array_key_exists('numero_factura', $data)) {
            $data['factura_pendiente'] = empty($data['numero_factura']);
        }

        $proveedorId = array_key_exists('proveedor_id', $data) ? $data['proveedor_id'] : $gasto->proveedor_id;
        $data = Gasto::normalizarFacturaPorProveedor($data, $proveedorId);

        $gasto->update($data);

        $cierre->recalcularTotales();
        $cierre->save();

        $this->registrarHistorialSiAplica($user, $motivo, 'gastos', $gasto->id, 'editado', $antes, $gasto->fresh()->toArray());

        return $gasto;
    }

    public function eliminarGasto(CierreCaja $cierre, Gasto $gasto, User $user, ?string $motivo = null): void
    {
        $this->autorizarEdicion($cierre, $user, $motivo);

        $antes = $gasto->toArray();
        $gastoId = $gasto->id;

        $gasto->delete();
        $cierre->recalcularTotales();
        $cierre->save();

        $this->registrarHistorialSiAplica($user, $motivo, 'gastos', $gastoId, 'eliminado', $antes, []);
    }

    public function agregarVale(CierreCaja $cierre, array $data, User $user, ?string $motivo = null): Vale
    {
        $this->autorizarEdicion($cierre, $user, $motivo);

        $vale = $cierre->vales()->create([
            'empleado_id' => $data['empleado_id'],
            'monto' => $data['monto'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_emision' => $cierre->fecha,
            'registrado_por' => $user->id,
        ]);

        $cierre->recalcularTotales();
        $cierre->save();

        $this->registrarHistorialSiAplica($user, $motivo, 'vales', $vale->id, 'creado', [], $vale->toArray());

        return $vale;
    }

    public function actualizarVale(CierreCaja $cierre, Vale $vale, array $data, User $user, ?string $motivo = null): Vale
    {
        $this->autorizarEdicion($cierre, $user, $motivo);
        $this->asegurarValeNoAplicado($vale);

        $antes = $vale->toArray();

        $vale->update($data);

        $cierre->recalcularTotales();
        $cierre->save();

        $this->registrarHistorialSiAplica($user, $motivo, 'vales', $vale->id, 'editado', $antes, $vale->fresh()->toArray());

        return $vale;
    }

    public function eliminarVale(CierreCaja $cierre, Vale $vale, User $user, ?string $motivo = null): void
    {
        $this->autorizarEdicion($cierre, $user, $motivo);
        $this->asegurarValeNoAplicado($vale);

        $antes = $vale->toArray();
        $valeId = $vale->id;

        $vale->delete();
        $cierre->recalcularTotales();
        $cierre->save();

        $this->registrarHistorialSiAplica($user, $motivo, 'vales', $valeId, 'eliminado', $antes, []);
    }

    /**
     * Entrada/salida de efectivo durante el turno (retiro de exceso, fondo
     * adicional, ingreso ajeno a la venta de A2 Food, etc). A diferencia de
     * gastos/vales, el motivo es obligatorio siempre (lo exige el Request),
     * no solo cuando el cierre ya no está abierto — pero solo queda rastro
     * en `historial` cuando la edición ocurrió con el cierre ya cerrado,
     * igual que el resto de entidades.
     */
    public function agregarMovimientoEfectivo(CierreCaja $cierre, array $data, User $user, string $motivo): MovimientoEfectivo
    {
        $this->autorizarEdicion($cierre, $user, $motivo);

        $movimiento = $cierre->movimientosEfectivo()->create([
            'tipo' => $data['tipo'],
            'monto' => $data['monto'],
            'motivo' => $motivo,
            'registrado_por' => $user->id,
        ]);

        $cierre->recalcularTotales();
        $cierre->save();

        $motivoHistorial = $cierre->estado !== 'abierto' ? $motivo : null;
        $this->registrarHistorialSiAplica($user, $motivoHistorial, 'movimientos_efectivo', $movimiento->id, 'creado', [], $movimiento->toArray());

        return $movimiento;
    }

    /**
     * Edita un movimiento existente (tipo, monto y/o su motivo). El motivo
     * del movimiento es una columna del registro; la justificación de la
     * edición ($motivoEdicion) es aparte, y es obligatoria solo cuando el
     * cierre ya no está abierto — mismo patrón que gastos/vales.
     */
    public function actualizarMovimientoEfectivo(CierreCaja $cierre, MovimientoEfectivo $movimiento, array $data, User $user, ?string $motivoEdicion = null): MovimientoEfectivo
    {
        $this->autorizarEdicion($cierre, $user, $motivoEdicion);

        $antes = $movimiento->toArray();

        $movimiento->update(array_intersect_key($data, array_flip(['tipo', 'monto', 'motivo'])));

        $cierre->recalcularTotales();
        $cierre->save();

        $motivoHistorial = $cierre->estado !== 'abierto' ? $motivoEdicion : null;
        $this->registrarHistorialSiAplica($user, $motivoHistorial, 'movimientos_efectivo', $movimiento->id, 'editado', $antes, $movimiento->fresh()->toArray());

        return $movimiento;
    }

    public function eliminarMovimientoEfectivo(CierreCaja $cierre, MovimientoEfectivo $movimiento, User $user, string $motivo): void
    {
        $this->autorizarEdicion($cierre, $user, $motivo);

        $antes = $movimiento->toArray();
        $movimientoId = $movimiento->id;

        $movimiento->delete();
        $cierre->recalcularTotales();
        $cierre->save();

        $motivoHistorial = $cierre->estado !== 'abierto' ? $motivo : null;
        $this->registrarHistorialSiAplica($user, $motivoHistorial, 'movimientos_efectivo', $movimientoId, 'eliminado', $antes, []);
    }

    /**
     * Adjunta una foto ya subida a disco (el path lo resuelve el controller
     * antes de llamar aquí, para poder validar el motivo ANTES de escribir el
     * archivo — ver CierreFotoController::store()). No afecta recalcularTotales.
     */
    public function agregarFoto(CierreCaja $cierre, User $user, string $fotoPath, ?string $descripcion, ?string $motivo = null): CierreFoto
    {
        $this->autorizarEdicion($cierre, $user, $motivo);

        $foto = $cierre->fotos()->create([
            'foto_path' => $fotoPath,
            'descripcion' => $descripcion,
            'subido_por' => $user->id,
        ]);

        $this->registrarHistorialSiAplica($user, $motivo, 'cierre_fotos', $foto->id, 'creado', [], $foto->toArray());

        return $foto;
    }

    public function eliminarFoto(CierreCaja $cierre, CierreFoto $foto, User $user, ?string $motivo = null): void
    {
        $this->autorizarEdicion($cierre, $user, $motivo);

        $antes = $foto->toArray();
        $fotoId = $foto->id;
        $path = $foto->foto_path;

        $foto->delete();
        Storage::disk('public')->delete($path);

        $this->registrarHistorialSiAplica($user, $motivo, 'cierre_fotos', $fotoId, 'eliminado', $antes, []);
    }

    /**
     * Cierra el turno: recalcula todo por última vez y bloquea edición.
     * A partir de aquí, gastos/vales/ingresos de este cierre son inmutables
     * para el cajero (Admin puede seguir corrigiendo con motivo obligatorio).
     */
    public function cerrar(CierreCaja $cierre): CierreCaja
    {
        $this->asegurarAbierto($cierre);

        return DB::transaction(function () use ($cierre) {
            $cierre->recalcularTotales();
            $cierre->estado = 'cerrado';
            $cierre->save();

            return $cierre->fresh(['gastos', 'vales', 'movimientosEfectivo', 'empleadosTurno']);
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

    /**
     * Elimina el cierre completo — solo Admin (autorizado en la Policy),
     * bloqueado si algún vale ya fue absorbido por una planilla generada.
     * Los gastos del cierre no tienen equivalente a aplicado_en_planilla
     * (solo compras_tienda, una tabla aparte, lo tiene), así que el único
     * bloqueo real posible es por vales.
     *
     * `gastos.cierre_caja_id` usa nullOnDelete (no cascadeOnDelete) porque la
     * misma tabla también sirve para gastos externos sin cierre asociado —
     * por eso los gastos del cierre se borran a mano aquí en vez de confiar
     * en la FK, que solo los dejaría huérfanos con cierre_caja_id = null.
     *
     * `cierre_fotos.cierre_caja_id` sí usa cascadeOnDelete, así que las filas
     * se borran solas — pero los ARCHIVOS en `storage/app/public` no, por eso
     * se borran a mano aquí antes de que la fila (y por ende `foto_path`)
     * desaparezca.
     */
    public function eliminar(CierreCaja $cierre): void
    {
        $tieneValesAplicados = $cierre->vales()->where('aplicado_en_planilla', true)->exists();

        if ($tieneValesAplicados) {
            throw ValidationException::withMessages([
                'cierre' => 'Este cierre tiene vales ya aplicados en una planilla generada y no se puede eliminar.',
            ]);
        }

        DB::transaction(function () use ($cierre) {
            foreach ($cierre->fotos as $foto) {
                Storage::disk('public')->delete($foto->foto_path);
            }

            $cierre->gastos()->delete();
            $cierre->delete(); // vales y fotos cascadean por FK
        });
    }

    /**
     * Cualquier edición mientras el cierre sigue 'abierto' se permite sin
     * más (comportamiento normal del cajero trabajando su turno). Una vez
     * que deja de estar abierto, solo Admin puede seguir editando, y debe
     * indicar un motivo — el resto (incluida Secretaria, que no llega a
     * estos métodos porque su UI no expone edición de gastos/vales/ingresos)
     * queda bloqueado.
     *
     * Pública (no privada) porque CierreFotoController::store() necesita
     * invocarla ANTES de escribir el archivo a disco — si el motivo falta,
     * así se evita subir un archivo huérfano que luego habría que limpiar.
     */
    public function autorizarEdicion(CierreCaja $cierre, User $user, ?string $motivo): void
    {
        if ($cierre->estado === 'abierto') {
            return;
        }

        if (! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'estado' => 'Este cierre ya no está abierto y no se puede modificar.',
            ]);
        }

        if (blank($motivo)) {
            throw ValidationException::withMessages([
                'motivo' => 'Debes indicar un motivo para editar un cierre que ya no está abierto.',
            ]);
        }
    }

    /**
     * Un vale ya absorbido por una planilla generada no se puede editar ni
     * eliminar desde el cierre — cambiarlo dejaría el total_vales del detalle
     * de planilla desincronizado en silencio. Mismo criterio protector que el
     * bloqueo de eliminar() por vales aplicados: para modificarlo, primero hay
     * que quitar al empleado de la planilla en borrador (eso libera el vale).
     */
    private function asegurarValeNoAplicado(Vale $vale): void
    {
        if ($vale->aplicado_en_planilla) {
            throw ValidationException::withMessages([
                'vale' => 'Este vale ya fue aplicado en una planilla. Quita al empleado de la planilla en borrador para liberarlo antes de modificarlo.',
            ]);
        }
    }

    private function asegurarAbierto(CierreCaja $cierre): void
    {
        if ($cierre->estado !== 'abierto') {
            throw ValidationException::withMessages([
                'estado' => 'Este cierre ya no está abierto y no se puede modificar.',
            ]);
        }
    }

    /** Solo se deja rastro en `historial` cuando la edición requirió motivo (corrección de Admin post-cierre). */
    private function registrarHistorialSiAplica(User $user, ?string $motivo, string $tabla, int $registroId, string $accion, array $antes, array $despues): void
    {
        if (blank($motivo)) {
            return;
        }

        Historial::create([
            'tabla' => $tabla,
            'registro_id' => $registroId,
            'accion' => $accion,
            'user_id' => $user->id,
            'motivo' => $motivo,
            'datos_antes' => $antes,
            'datos_despues' => $despues,
        ]);
    }
}
