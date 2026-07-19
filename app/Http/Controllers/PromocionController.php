<?php

namespace App\Http\Controllers;

use App\Models\Promocion;
use Illuminate\Http\Request;

class PromocionController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('Promociones/Index', [
            'promociones' => Promocion::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_promocion' => ['required', 'string', 'max:100'],
            'porcentaje_descuento' => ['required', 'numeric', 'min:1', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        Promocion::create([
            'nombre_promocion' => $request->nombre_promocion,
            'porcentaje_descuento' => $request->porcentaje_descuento,
            'descripcion' => $request->descripcion,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estado' => true
        ]);

        return redirect()->route('promociones.index')->with('success', 'Promoción guardada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $promocion = Promocion::findOrFail($id);

        $request->validate([
            'nombre_promocion' => ['required', 'string', 'max:100'],
            'porcentaje_descuento' => ['required', 'numeric', 'min:1', 'max:100'], // RN-10
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'], // RN-11
        ]);

        $promocion->update($request->only([
            'nombre_promocion', 'porcentaje_descuento', 'descripcion', 'fecha_inicio', 'fecha_fin'
        ]));

        return redirect()->route('promociones.index')->with('success', 'Promoción modificada correctamente.');
    }

    public function cambiarEstado($id)
    {
        $promocion = Promocion::findOrFail($id);
        $promocion->update(['estado' => !$promocion->estado]);
          
        return redirect()->route('promociones.index')->with('success', 'Estado de la promoción actualizado.');
    }
}