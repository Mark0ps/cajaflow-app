<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\ResetPasswordUsuarioRequest;
use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return User::with('empleado:id,nombre,apellido')
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'email', 'role', 'empleado_id', 'activo', 'created_at']);
    }

    public function store(UsuarioRequest $request)
    {
        $usuario = User::create($request->validated());

        return response()->json($usuario->fresh()->load('empleado:id,nombre,apellido'), 201);
    }

    /**
     * Editar: solo rol, activo, username, email (decisión registrada). El
     * nombre y el empleado vinculado no se tocan aquí. Nunca se elimina un
     * usuario de verdad (está referenciado en historial/cierres/planillas/
     * pagos) — desactivar (`activo = false`) ya bloquea el login.
     */
    public function update(ActualizarUsuarioRequest $request, User $usuario)
    {
        abort_unless($request->user()->isAdmin(), 403);

        // Anti auto-bloqueo: un admin no puede desactivarse ni quitarse el
        // rol a sí mismo — quedaría fuera del sistema (o sin módulo de
        // Usuarios) en el mismo request. Otro admin sí puede hacerlo.
        if ($usuario->id === $request->user()->id) {
            $data = $request->validated();

            if (array_key_exists('activo', $data) && ! $data['activo']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'activo' => 'No puedes desactivar tu propio usuario.',
                ]);
            }

            if (array_key_exists('role', $data) && $data['role'] !== 'admin') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'role' => 'No puedes quitarte el rol de administrador a ti mismo.',
                ]);
            }
        }

        $usuario->update($request->validated());

        return response()->json($usuario->fresh()->load('empleado:id,nombre,apellido'));
    }

    /** Reset de contraseña como acción separada de la edición normal. */
    public function resetPassword(ResetPasswordUsuarioRequest $request, User $usuario)
    {
        $usuario->update(['password' => $request->validated('password')]);

        return response()->json(['message' => 'Contraseña restablecida correctamente.']);
    }
}
