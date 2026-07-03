<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->input('login'))
            ->orWhere('username', $request->input('login'))
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password) || ! $user->activo) {
            throw ValidationException::withMessages([
                'login' => 'Las credenciales no son válidas.',
            ]);
        }

        $token = $user->createToken('cajaflow')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('empleado'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }

    /**
     * Confirmación de contraseña del admin autenticado, mismo patrón que
     * AutoSys: se usa antes de acciones sensibles (cerrar/eliminar planilla).
     */
    public function verificarPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }

        return response()->json(['message' => 'Contraseña verificada.'], 200);
    }
}
