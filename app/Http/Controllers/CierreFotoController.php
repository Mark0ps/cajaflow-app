<?php

namespace App\Http\Controllers;

use App\Http\Requests\CierreFotoRequest;
use App\Models\CierreCaja;
use App\Models\CierreFoto;
use App\Services\CierreCajaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CierreFotoController extends Controller
{
    public function __construct(private CierreCajaService $service)
    {
    }

    /** Mismo chequeo de pertenencia que en los recursos anidados de planillas. */
    private function verificarPertenencia(CierreCaja $cierre, CierreFoto $foto): void
    {
        if ($foto->cierre_caja_id !== $cierre->id) {
            throw ValidationException::withMessages([
                'foto' => 'Esta foto no pertenece al cierre indicado.',
            ]);
        }
    }

    public function store(CierreFotoRequest $request, CierreCaja $cierre)
    {
        // Se valida el motivo ANTES de escribir el archivo a disco — evita
        // subir un archivo huérfano si la edición termina siendo rechazada.
        $this->service->autorizarEdicion($cierre, $request->user(), $request->input('motivo'));

        $path = $request->file('foto')->store('cierre_fotos', 'public');

        $foto = $this->service->agregarFoto(
            $cierre,
            $request->user(),
            $path,
            $request->input('descripcion'),
            $request->input('motivo'),
        );

        return response()->json($foto->load('subidoPor:id,name'), 201);
    }

    public function destroy(Request $request, CierreCaja $cierre, CierreFoto $foto)
    {
        $this->authorize('update', $cierre);
        $this->verificarPertenencia($cierre, $foto);

        $this->service->eliminarFoto($cierre, $foto, $request->user(), $request->input('motivo'));

        return response()->noContent();
    }
}
