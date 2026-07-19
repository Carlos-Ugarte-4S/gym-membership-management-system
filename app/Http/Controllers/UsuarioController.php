<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    // Listar todos los usuarios con sus datos de persona y turno
    public function index()
    {
        $usuarios = Usuario::with(['persona', 'turno'])->get(); //actualiza los usuarios registrados y carg los datos de persona y turno 
        $turnos = \App\Models\Turno::where('estado', true)->get(); //actualiza los turnos disponibles 
        
        return \Inertia\Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
            'turnos' => $turnos
        ]);
    }

    // Registrar un nuevo usuario (Administrador / Recepcionista)
    public function store(Request $request) 
    {
        // Validaciones según requerimientos de campos obligatorios
        $request->validate([
            'ci' => ['required', 'string', 'unique:personas,ci'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'sexo' => ['required', 'string', 'max:1'],
            'email' => ['required', 'email', 'unique:usuarios,email'],
            'login' => ['required', 'string', 'unique:usuarios,login', 'max:50'],
            'contrasenia' => ['required', 'string', 'min:6'], 
            'rol' => ['required', 'string'], // 'Administrador' o 'Recepcionista'
            'id_turno' => ['required', 'integer'],
            'fecha_contrato' => ['required', 'date'],
        ]);

        DB::beginTransaction(); //inicia la transaccion 
        try {
            // 1. Crear la base de la Persona
            $persona = Persona::create([
                'ci' => $request->ci,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'sexo' => $request->sexo,
                'fecha_registro' => now()->toDateString(),
            ]);

            // 2. Crear el Usuario vinculando la persona y el turno asignado
            $usuario = Usuario::create([
                'id_persona' => $persona->id_persona,
                'id_turno' => $request->id_turno,
                'email' => $request->email,
                'contrasenia' => Hash::make($request->contrasenia), // Encriptación obligatoria
                'login' => $request->login,
                'rol' => $request->rol,
                'fecha_contrato' => $request->fecha_contrato,
                'estado' => true, // Activo por defecto
            ]);

            DB::commit(); 
            return redirect()->route('usuarios.index')->with('success', 'Usuario registrado con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al registrar el usuario: ' . $e->getMessage()]);
        }
    }

    // Modificar datos de un usuario (Flujo Alternativo)
    public function update(Request $request, $id) 
    {
        $usuario = Usuario::findOrFail($id); 
        $persona = Persona::findOrFail($usuario->id_persona); 

        $request->validate([
            'email' => ['required', 'email', "unique:usuarios,email,{$id},id_usuario"],
            'login' => ['required', 'string', "unique:usuarios,login,{$id},id_usuario"],
            'rol' => ['required', 'string'],
            'id_turno' => ['required', 'integer'],
        ]);

        DB::beginTransaction();
        try {
            $persona->update($request->only(['nombre', 'apellido', 'telefono', 'direccion']));
            
            $datosUsuario = $request->only(['email', 'login', 'rol', 'id_turno', 'estado']);
            // Si el administrador envió una nueva contraseña, se encripta y se actualiza
            if ($request->filled('contrasenia')) {
                $datosUsuario['contrasenia'] = Hash::make($request->contrasenia);
            }

            $usuario->update($datosUsuario);

            DB::commit();
            return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado con éxito.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar.']);
        }
    }
}