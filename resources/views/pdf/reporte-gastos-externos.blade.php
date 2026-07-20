<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 24px 28px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }

    .header { text-align: center; margin-bottom: 14px; border-bottom: 1.5px solid #1e293b; padding-bottom: 10px; }
    .header .negocio { font-size: 16px; font-weight: bold; margin: 0 0 2px; }
    .header .titulo { font-size: 12px; font-weight: bold; margin: 0 0 2px; color: #334155; }
    .header .periodo { font-size: 10px; margin: 0; color: #64748b; }

    table.tabla { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.tabla th, table.tabla td { border: 0.5px solid #cbd5e1; padding: 4px 6px; }
    table.tabla th { background-color: #1e293b; color: #ffffff; text-align: left; font-size: 9px; text-transform: uppercase; }
    table.tabla td { font-size: 9.5px; }
    table.tabla td.num, table.tabla th.num { text-align: right; }
    table.tabla tr:nth-child(even) td { background-color: #f8fafc; }

    tr.totales td { font-weight: bold; background-color: #e2e8f0 !important; border-top: 1.5px solid #1e293b; }

    .sin-datos { text-align: center; color: #94a3b8; padding: 16px; }

    .resumen { width: 60%; margin: 0 auto; border: 1px solid #1e293b; border-radius: 4px; padding: 10px 14px; }
    .resumen .titulo-resumen { font-size: 11px; font-weight: bold; margin: 0 0 8px; text-align: center; text-transform: uppercase; }
    .resumen table { width: 100%; border-collapse: collapse; }
    .resumen table td { padding: 3px 0; font-size: 10px; }
    .resumen table td.valor { text-align: right; }
    .resumen tr.total-general td { border-top: 1px solid #1e293b; font-weight: bold; padding-top: 6px; }

    .footer { margin-top: 16px; font-size: 8px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>

    <div class="header">
        <p class="negocio">{{ $negocio }}</p>
        <p class="titulo">Reporte de Gastos Externos</p>
        <p class="periodo">Período: {{ $periodo }}</p>
    </div>

    @if ($filas->isEmpty())
        <p class="sin-datos">No hay gastos externos que coincidan con los filtros seleccionados en este período.</p>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>N&deg; Factura</th>
                    <th>Categor&iacute;a</th>
                    <th class="num">Efectivo</th>
                    <th class="num">Tarjeta</th>
                    <th class="num">Transferencia</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        <td>{{ $fila['fecha'] }}</td>
                        <td>{{ $fila['proveedor'] }}</td>
                        <td>{{ $fila['numero_factura'] }}</td>
                        <td>{{ $fila['categoria'] }}</td>
                        <td class="num">{{ $fila['efectivo'] > 0 ? number_format($fila['efectivo'], 2) : '—' }}</td>
                        <td class="num">{{ $fila['tarjeta'] > 0 ? number_format($fila['tarjeta'], 2) : '—' }}</td>
                        <td class="num">{{ $fila['transferencia'] > 0 ? number_format($fila['transferencia'], 2) : '—' }}</td>
                        <td class="num">{{ number_format($fila['total'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="totales">
                    <td colspan="4">TOTALES</td>
                    <td class="num">L. {{ number_format($totales['efectivo'], 2) }}</td>
                    <td class="num">L. {{ number_format($totales['tarjeta'], 2) }}</td>
                    <td class="num">L. {{ number_format($totales['transferencia'], 2) }}</td>
                    <td class="num">L. {{ number_format($totales['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="resumen">
            <p class="titulo-resumen">Totales por categor&iacute;a</p>
            <table>
                <tr>
                    <td>Gasto operativo</td>
                    <td class="valor">L. {{ number_format($totalesPorCategoria['gasto_operativo'], 2) }}</td>
                </tr>
                <tr>
                    <td>Servicios p&uacute;blicos / Gastos fijos</td>
                    <td class="valor">L. {{ number_format($totalesPorCategoria['servicios_publicos'], 2) }}</td>
                </tr>
                <tr>
                    <td>Pago de tarjeta de cr&eacute;dito</td>
                    <td class="valor">L. {{ number_format($totalesPorCategoria['pago_tarjeta_credito'], 2) }}</td>
                </tr>
                <tr class="total-general">
                    <td>Total general</td>
                    <td class="valor">L. {{ number_format($totalGeneral, 2) }}</td>
                </tr>
            </table>
        </div>
    @endif

    <p class="footer">Generado el {{ now()->format('d/m/Y H:i') }} &mdash; CajaFlow</p>

</body>
</html>
