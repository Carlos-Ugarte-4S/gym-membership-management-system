<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('Turnos/Index', [
            'turnos' => Turno::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_turno' => ['required', 'string', 'max:50'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
        ]);

        Turno::create($request->all());

        return redirect()->route('turnos.index')->with('success', 'Turno configurado con éxito.');
    }

    public function update(Request $request, int $id)
    {
        $turno = Turno::findOrFail($id);

        $request->validate([
            'nombre_turno' => ['required', 'string', 'max:50'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
        ]);

        $turno->update($request->only(['nombre_turno', 'hora_inicio', 'hora_fin']));

        return redirect()->route('turnos.index')->with('success', 'Turno actualizado con éxito.');
    }

      public function cambiarEstado($id)
    {
        $turno = Turno::findOrFail($id);
        $turno->update(['estado' => !$turno->estado]);
          
        return redirect()->route('turnos.index')->with('success', 'Estado del turno actualizado.');
    }
}