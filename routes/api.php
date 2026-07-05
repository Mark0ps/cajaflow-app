<?php

/**
 * Agrega esto a routes/api.php, dentro del grupo con middleware(['auth:sanctum']).
 * Ajusta el prefijo/nombre si ya tienes convenciones propias de AutoSys.
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CierreCajaController;
use App\Http\Controllers\CierreFotoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\MovimientoEfectivoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ValeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PagoPlanillaController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\PlanillaDetalleComprasController;
use App\Http\Controllers\PlanillaDetalleLlegadasController;
use App\Http\Controllers\PlanillaDetallePrestamoController;
use App\Http\Controllers\PrestamoController;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', fn (Request $request) => $request->user()->load('empleado'));
    Route::post('verificar-admin', [AuthController::class, 'verificarPassword'])->middleware('throttle:5,1');

    // Empleados
    Route::get('empleados', [EmpleadoController::class, 'index']);
    Route::get('empleados/{empleado}', [EmpleadoController::class, 'show']);

    // Proveedores
    Route::get('proveedores', [ProveedorController::class, 'index']);
    Route::post('proveedores', [ProveedorController::class, 'store']);
    Route::put('proveedores/{proveedor}', [ProveedorController::class, 'update']);
    Route::delete('proveedores/{proveedor}', [ProveedorController::class, 'destroy']);

    // Cierres de caja
    Route::get('cierres-caja', [CierreCajaController::class, 'index']);
    Route::post('cierres-caja', [CierreCajaController::class, 'store']);
    Route::get('cajeros', [CierreCajaController::class, 'cajeros']);
    Route::get('cierres-caja/{cierre}', [CierreCajaController::class, 'show']);
    Route::patch('cierres-caja/{cierre}/ingresos', [CierreCajaController::class, 'actualizarIngresos']);
    Route::post('cierres-caja/{cierre}/empleados', [CierreCajaController::class, 'agregarEmpleado']);
    Route::delete('cierres-caja/{cierre}/empleados/{empleado}', [CierreCajaController::class, 'quitarEmpleado']);
    Route::post('cierres-caja/{cierre}/cerrar', [CierreCajaController::class, 'cerrar']);
    Route::post('cierres-caja/{cierre}/revisar', [CierreCajaController::class, 'revisar']);
    Route::delete('cierres-caja/{cierre}', [CierreCajaController::class, 'destroy']);

    // Gastos (anidados bajo un cierre) + gastos externos + facturas pendientes
    Route::post('cierres-caja/{cierre}/gastos', [GastoController::class, 'store']);
    Route::patch('cierres-caja/{cierre}/gastos/{gasto}', [GastoController::class, 'update']);
    Route::delete('cierres-caja/{cierre}/gastos/{gasto}', [GastoController::class, 'destroy']);
    Route::get('gastos', [GastoController::class, 'index']);
    Route::post('gastos/externos', [GastoController::class, 'storeExterno']);
    Route::patch('gastos/{gasto}/factura', [GastoController::class, 'actualizarFactura']);

    // Vales (anidados bajo un cierre)
    Route::post('cierres-caja/{cierre}/vales', [ValeController::class, 'store']);
    Route::patch('cierres-caja/{cierre}/vales/{vale}', [ValeController::class, 'update']);
    Route::delete('cierres-caja/{cierre}/vales/{vale}', [ValeController::class, 'destroy']);
    Route::get('empleados/{empleado}/vales', [ValeController::class, 'porEmpleado']);

    // Movimientos de efectivo (entradas/salidas durante el turno, anidados bajo un cierre)
    Route::post('cierres-caja/{cierre}/movimientos', [MovimientoEfectivoController::class, 'store']);
    Route::delete('cierres-caja/{cierre}/movimientos/{movimiento}', [MovimientoEfectivoController::class, 'destroy']);

    // Fotos del turno (anidadas bajo un cierre)
    Route::post('cierres-caja/{cierre}/fotos', [CierreFotoController::class, 'store']);
    Route::delete('cierres-caja/{cierre}/fotos/{foto}', [CierreFotoController::class, 'destroy']);

    // Planillas
    Route::get('planillas', [PlanillaController::class, 'index']);
    Route::post('planillas', [PlanillaController::class, 'store']);
    Route::get('planillas/{planilla}', [PlanillaController::class, 'show']);
    Route::patch('planillas/{planilla}', [PlanillaController::class, 'update']);
    Route::delete('planillas/{planilla}', [PlanillaController::class, 'destroy']);
    Route::post('planillas/{planilla}/cerrar', [PlanillaController::class, 'cerrar']);
    Route::patch('planillas/{planilla}/detalles/{detalle}', [PlanillaController::class, 'actualizarDetalle']);

    // Consumo interno (compras_tienda), anidado bajo el detalle de planilla
    Route::post('planillas/{planilla}/detalles/{detalle}/compras-tienda', [PlanillaDetalleComprasController::class, 'store']);
    Route::patch('planillas/{planilla}/detalles/{detalle}/compras-tienda/{compra}', [PlanillaDetalleComprasController::class, 'update']);
    Route::delete('planillas/{planilla}/detalles/{detalle}/compras-tienda/{compra}', [PlanillaDetalleComprasController::class, 'destroy']);

    // Llegadas tarde, anidado bajo el detalle de planilla
    Route::post('planillas/{planilla}/detalles/{detalle}/llegadas-tarde', [PlanillaDetalleLlegadasController::class, 'store']);
    Route::patch('planillas/{planilla}/detalles/{detalle}/llegadas-tarde/{llegada}', [PlanillaDetalleLlegadasController::class, 'update']);
    Route::delete('planillas/{planilla}/detalles/{detalle}/llegadas-tarde/{llegada}', [PlanillaDetalleLlegadasController::class, 'destroy']);

    // Abono de préstamo del detalle de planilla (edición del abono ya aplicado)
    Route::patch('planillas/{planilla}/detalles/{detalle}/abono-prestamo', [PlanillaDetallePrestamoController::class, 'update']);

    // Préstamos (anidados bajo empleado para listar, sueltos para crear/ver)
    Route::get('empleados/{empleado}/prestamos', [PrestamoController::class, 'index']);
    Route::post('prestamos', [PrestamoController::class, 'store']);
    Route::get('prestamos/{prestamo}', [PrestamoController::class, 'show']);

    // Dashboard y reportes (solo admin/secretaria, validado en los controllers)
    Route::get('dashboard/resumen-mensual', [DashboardController::class, 'resumenMensual']);
    Route::get('dashboard/dia', [DashboardController::class, 'dia']);
    Route::get('reportes/periodo', [ReporteController::class, 'periodo']);
    Route::get('reportes/metodo-pago', [ReporteController::class, 'metodoPago']);
    Route::get('reportes/gastos-vs-ingresos', [ReporteController::class, 'gastosVsIngresos']);
    Route::get('reportes/vales-por-empleado', [ReporteController::class, 'valesPorEmpleado']);

    // Estado de cuenta y pagos (el flujo de "pagos atrasados")
    Route::get('empleados/{empleado}/estado-cuenta', [PagoPlanillaController::class, 'estadoCuenta']);
    Route::post('empleados/{empleado}/pagos', [PagoPlanillaController::class, 'store']);
    Route::get('pagos/{pago}', [PagoPlanillaController::class, 'show']);
});
