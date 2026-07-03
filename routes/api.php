<?php

/**
 * Agrega esto a routes/api.php, dentro del grupo con middleware(['auth:sanctum']).
 * Ajusta el prefijo/nombre si ya tienes convenciones propias de AutoSys.
 */

use App\Http\Controllers\CierreCajaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ValeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PagoPlanillaController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\PrestamoController;


Route::middleware(['auth:sanctum'])->group(function () {

    // Proveedores
    Route::get('proveedores', [ProveedorController::class, 'index']);
    Route::post('proveedores', [ProveedorController::class, 'store']);
    Route::put('proveedores/{proveedor}', [ProveedorController::class, 'update']);
    Route::delete('proveedores/{proveedor}', [ProveedorController::class, 'destroy']);

    // Cierres de caja
    Route::get('cierres-caja', [CierreCajaController::class, 'index']);
    Route::post('cierres-caja', [CierreCajaController::class, 'store']);
    Route::get('cierres-caja/{cierre}', [CierreCajaController::class, 'show']);
    Route::patch('cierres-caja/{cierre}/ingresos', [CierreCajaController::class, 'actualizarIngresos']);
    Route::post('cierres-caja/{cierre}/empleados', [CierreCajaController::class, 'agregarEmpleado']);
    Route::delete('cierres-caja/{cierre}/empleados/{empleado}', [CierreCajaController::class, 'quitarEmpleado']);
    Route::post('cierres-caja/{cierre}/cerrar', [CierreCajaController::class, 'cerrar']);
    Route::post('cierres-caja/{cierre}/revisar', [CierreCajaController::class, 'revisar']);

    // Gastos (anidados bajo un cierre) + gastos externos + facturas pendientes
    Route::post('cierres-caja/{cierre}/gastos', [GastoController::class, 'store']);
    Route::delete('cierres-caja/{cierre}/gastos/{gasto}', [GastoController::class, 'destroy']);
    Route::get('gastos', [GastoController::class, 'index']);
    Route::post('gastos/externos', [GastoController::class, 'storeExterno']);
    Route::patch('gastos/{gasto}/factura', [GastoController::class, 'actualizarFactura']);

    // Vales (anidados bajo un cierre)
    Route::post('cierres-caja/{cierre}/vales', [ValeController::class, 'store']);
    Route::delete('cierres-caja/{cierre}/vales/{vale}', [ValeController::class, 'destroy']);
    Route::get('empleados/{empleado}/vales', [ValeController::class, 'porEmpleado']);

    // Planillas
    Route::get('planillas', [PlanillaController::class, 'index']);
    Route::post('planillas', [PlanillaController::class, 'store']);
    Route::get('planillas/{planilla}', [PlanillaController::class, 'show']);
    Route::post('planillas/{planilla}/cerrar', [PlanillaController::class, 'cerrar']);
    Route::patch('planillas/{planilla}/detalles/{detalle}', [PlanillaController::class, 'actualizarDetalle']);

    // Préstamos (anidados bajo empleado para listar, sueltos para crear/ver)
    Route::get('empleados/{empleado}/prestamos', [PrestamoController::class, 'index']);
    Route::post('prestamos', [PrestamoController::class, 'store']);
    Route::get('prestamos/{prestamo}', [PrestamoController::class, 'show']);

    // Estado de cuenta y pagos (el flujo de "pagos atrasados")
    Route::get('empleados/{empleado}/estado-cuenta', [PagoPlanillaController::class, 'estadoCuenta']);
    Route::post('empleados/{empleado}/pagos', [PagoPlanillaController::class, 'store']);
    Route::get('pagos/{pago}', [PagoPlanillaController::class, 'show']);
});
