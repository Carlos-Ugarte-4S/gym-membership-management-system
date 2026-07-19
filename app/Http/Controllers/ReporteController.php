<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Membresia;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    // Renderiza la interfaz de reportes (CU-06)
    public function index()
    {
        return \Inertia\Inertia::render('Reportes/Index');
    }

    // Generar reporte financiero y estadístico por rango de fechas
    public function generarReporteFinanciero(Request $request)
    {
        $request->validate([
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $desde = $request->fecha_desde;
        $hasta = $request->fecha_hasta;

        // 1. Recaudación total y agrupación por método de pago
        $ingresos = Pago::whereBetween('fecha_pago', [$desde, $hasta])
            ->select('metodo_pago', DB::raw('SUM(monto) as total'))
            ->groupBy('metodo_pago')
            ->get();

        $granTotal = $ingresos->sum('total');

        // 2. Cantidad de membresías vendidas agrupadas por el tipo
        $membresiasVendidas = Membresia::whereBetween('fecha_inicio', [$desde, $hasta])
            ->join('tipo_membresias', 'membresias.id_tipo_membresia', '=', 'tipo_membresias.id_tipo_membresia')
            ->select('tipo_membresias.nombre_membresia', DB::raw('COUNT(membresias.id_membresia) as cantidad'))
            ->groupBy('tipo_membresias.nombre_membresia')
            ->get();

        return response()->json([
            'rango' => ['desde' => $desde, 'hasta' => $hasta],
            'total_recaudado' => $granTotal,
            'detalle_ingresos' => $ingresos,
            'membresias_vendidas' => $membresiasVendidas
        ], 200);
    }
    public function historialCliente($id)
{
    $cliente = Cliente::with([
        'persona',
        'membresias.tipoMembresia',
        'membresias.pagos'
    ])->findOrFail($id);

    return response()->json([

        'cliente' => [

            'id_cliente' => $cliente->id_cliente,
            'ci' => $cliente->persona->ci,
            'nombre' => $cliente->persona->nombre . ' ' . $cliente->persona->apellido,
            'telefono' => $cliente->persona->telefono,
            'direccion' => $cliente->persona->direccion,
            'fecha_registro' => $cliente->persona->fecha_registro,

        ],

        'membresias' => $cliente->membresias
            ->sortByDesc('fecha_inicio')
            ->values()
            ->map(function ($membresia) {

                $pago = $membresia->pagos->first();

                return [

                    'id_membresia' => $membresia->id_membresia,

                    'nombre_membresia' =>
                        $membresia->tipoMembresia->nombre_membresia,

                    'fecha_inicio' => $membresia->fecha_inicio,

                    'fecha_vencimiento' => $membresia->fecha_vencimiento,

                    'estado' =>
                        now()->toDateString() <= $membresia->fecha_vencimiento
                        ? 'Activa'
                        : 'Vencida',

                    'metodo_pago' =>
                        $pago?->metodo_pago,

                    'monto_pagado' =>
                        $pago?->monto

                ];

            })

    ]);
} 
}