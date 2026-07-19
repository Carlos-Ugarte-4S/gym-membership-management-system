<?php

namespace App\Http\Controllers;

use App\Models\TipoMembresia;
use App\Models\Membresia;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TipoMembresiaController extends Controller
{
    // Desplegar la lista completa del catálogo de planes - CU-08
    public function index()
    {
        return \Inertia\Inertia::render('TiposMembresia/Index', [
            'tiposMembresia' => TipoMembresia::all()
        ]);
    }

    // Registrar un nuevo tipo de membresía con nombre único - CU-08
    public function store(Request $request)
    {
        // Regla de Negocio RN-08: Nombre único
        $request->validate([
            'nombre_membresia' => ['required', 'string', 'unique:tipo_membresias,nombre_membresia', 'max:100'],
            'duracion_dias' => ['required', 'integer', 'min:1'],
            'precio' => ['required', 'numeric', 'min:0'],
        ], [
            'nombre_membresia.unique' => 'El nombre del tipo de membresía debe ser único.',
        ]);

        $tipo = TipoMembresia::create([
            'nombre_membresia' => $request->nombre_membresia,
            'duracion_dias' => $request->duracion_dias,
            'precio' => $request->precio,
            'estado' => true, // Campo corregido por indicación del Ingeniero
        ]);

        return redirect()->route('tipos-membresia.index')->with('success', 'Tipo de membresía registrado con éxito.');
    }

    // Modificar un plan existente (Flujo Alternativo A1)
    public function update(Request $request, $id)
    {
        $tipo = TipoMembresia::findOrFail($id);

        $request->validate([
            'nombre_membresia' => ['required', 'string', "unique:tipo_membresias,nombre_membresia,{$id},id_tipo_membresia"],
            'duracion_dias' => ['required', 'integer'],
            'precio' => ['required', 'numeric'],
        ]);

        $tipo->update($request->only(['nombre_membresia', 'duracion_dias', 'precio']));

        return redirect()->route('tipos-membresia.index')->with('success', 'Modificación realizada con éxito.');
    }

    // Activar / Desactivar tipo de membresía (Flujo Alternativo A2)
    public function cambiarEstado($id)
    {
        $tipo = TipoMembresia::findOrFail($id);

        // Si se intenta desactivar, verificar si existen membresías en curso asociadas a este plan
        if ($tipo->estado) {
            $tieneAsociacionesActivas = Membresia::where('id_tipo_membresia', $id)
                ->where('fecha_vencimiento', '>=', Carbon::now()->toDateString())
                ->exists();

            if ($tieneAsociacionesActivas) {
                return back()->withErrors(['error' => 'No se puede desactivar el plan porque tiene clientes con membresías activas vigentes.']);
            }
        }

        // Si no existen restricciones, se invierte el estado lógico del campo
        $tipo->update(['estado' => !$tipo->estado]);

        return redirect()->route('tipos-membresia.index')->with('success', 'Estado del tipo de membresía actualizado correctamente.');
    }
}