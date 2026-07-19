<?php

namespace App\Http\Controllers;

//recibir los datos enviados desde Vue.
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Procesa la autenticación del usuario
    public function login(Request $request)
    {
        // Regla de Negocio / Flujo Alternativo A3: Campos vacíos
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'El campo usuario es obligatorio.',
            'password.required' => 'El campo contraseña es obligatorio.',
        ]);

        // Intentar autenticar con las credenciales personalizadas de tu diagrama (login)
        // Nota: Laravel maneja internamente la columna 'password' mapeada a tu 'contrasenia' en el modelo
        if (!Auth::attempt(['login' => $credentials['login'], 'password' => $credentials['password']])) {
            // Flujo Alternativo A1: Credenciales incorrectas
            throw ValidationException::withMessages([
                'login' => ['Usuario o contraseña incorrectos.'],
            ]);
        }

        // Obtener el usuario autenticado para validar su estado en la base de datos
        $user = Auth::user();

        // Regla de Negocio RN-11 / Flujo Alternativo A2: Usuario inactivo
        if (!$user->estado) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => ['Usuario inactivo, contacte al administrador.'],
            ]);
        }

        // Postcondición: Se inicia una sesión activa y se regenera el token
        $request->session()->regenerate();

        // Redirigir al dashboard general (el menú lateral filtrará los módulos por rol)
        return redirect()->intended(route('dashboard'));
    }

    // Cierra la sesión activa
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}