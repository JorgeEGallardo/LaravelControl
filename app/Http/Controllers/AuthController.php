<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        // Obtener las credenciales del request
        $usuario = $request->input('usuario');
        $password = md5($request->input('password'));

        // Buscar el usuario en la base de datos
        $user = DB::table('usuarios')
                    ->where('usuario', $usuario)
                    ->where('password', $password)
                    ->first();

        // Si el usuario existe
        if ($user) {
            // Generar un token de autenticación (ej. JWT, Laravel Passport)
            // Para este ejemplo, simplemente devolveremos el ID del usuario
            // pero en una aplicación real, deberías devolver un token JWT
            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'message' => 'Login exitoso',
                'user_type' => $user->permisos
            ]);
        }

        // Si no se encuentra el usuario o la contraseña no coincide
        return response()->json([
            'success' => false,
            'message' => 'Credenciales incorrectas',
        ], 401);
    }
}
