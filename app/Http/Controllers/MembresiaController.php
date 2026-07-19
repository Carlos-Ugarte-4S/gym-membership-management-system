<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\TipoMembresia;
use App\Models\Promocion;
use App\Models\Membresia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MembresiaController extends Controller
{
    // Mostrar el formulario de registro de membresías y pagos
    public function index()
    {
        $clientes = Cliente::with('persona')->withCount('membresias')->get();

        $tiposMembresia = TipoMembresia::where('estado', true)->get();

        $promociones = Promocion::where('estado', true)
            ->where('fecha_inicio', '<=', now()->toDateString())
            ->where('fecha_fin', '>=', now()->toDateString())
            ->get();

        return \Inertia\Inertia::render('Membresias/Index', [
            'clientes' => $clientes,
            'tiposMembresia' => $tiposMembresia,
            'promociones' => $promociones
        ]);
    }

    // CU-04: Registrar membresía
    public function store(Request $request)
    {
        $request->validate([
            'id_cliente' => ['required', 'integer'],
            'id_tipo_membresia' => ['required', 'integer'],
            'id_promocion' => ['nullable', 'integer'],
            'fecha_inicio' => ['required', 'date'],
            'metodo_pago' => ['required', 'string'],
            'cantidad_periodos' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $cliente = Cliente::find($request->id_cliente);

        if (!$cliente) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_cliente' => 'Cliente inexistente.'
            ]);
        }

        $pagoAdelantado = filter_var($request->input('pago_adelantado'), FILTER_VALIDATE_BOOLEAN);
        $fechaInicio = Carbon::parse($request->fecha_inicio);

        // 🔥 Membresía activa (SIN fecha_limite)
        $membresiaActiva = Membresia::where('id_cliente', $cliente->id_cliente)
            ->where('fecha_vencimiento', '>=', now()->toDateString())
            ->orderBy('fecha_vencimiento', 'desc')
            ->first();

        if ($membresiaActiva) {
            if (!$pagoAdelantado) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_cliente' => 'El cliente ya tiene una membresía activa que vence el ' .
                        Carbon::parse($membresiaActiva->fecha_vencimiento)->format('Y-m-d')
                ]);
            }

            // siguiente inicio automático
            $fechaInicio = Carbon::parse($membresiaActiva->fecha_vencimiento)->addDay();
        }

        $tipoMembresia = TipoMembresia::find($request->id_tipo_membresia);

        if (!$tipoMembresia || !$tipoMembresia->estado) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_tipo_membresia' => 'Tipo de membresía inválido o inactivo.'
            ]);
        }

        $cantidadPeriodos = max(1, (int) $request->input('cantidad_periodos', 1));

        $montoFinal = $tipoMembresia->precio * $cantidadPeriodos;

        // Promoción
        if ($request->id_promocion) {
            $promocion = Promocion::find($request->id_promocion);

            if ($promocion && $promocion->estado &&
                Carbon::now()->between($promocion->fecha_inicio, $promocion->fecha_fin)) {

                $descuento = ($montoFinal * $promocion->porcentaje_descuento) / 100;
                $montoFinal -= $descuento;

            } else {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_promocion' => 'Promoción no válida o expirada.'
                ]);
            }
        }

        $fechaVencimiento = $fechaInicio
            ->copy()
            ->addDays($tipoMembresia->duracion_dias * $cantidadPeriodos);

        DB::beginTransaction();

        try {
            // Crear membresía
            $membresia = Membresia::create([
                'id_cliente' => $cliente->id_cliente,
                'id_tipo_membresia' => $tipoMembresia->id_tipo_membresia,
                'id_promocion' => $request->id_promocion,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_vencimiento' => $fechaVencimiento->toDateString(),
            ]);

            // Pago interno
            $pagoRequest = new Request([
                'id_membresia' => $membresia->id_membresia,
                'monto' => $montoFinal,
                'metodo_pago' => $request->metodo_pago,
                'banco_origen' => $request->banco_origen,
                'banco_destino' => $request->banco_destino,
                'cuenta_destino' => $request->cuenta_destino,
                'codigo_transaccion' => $request->codigo_transaccion,
            ]);

            if ($request->hasFile('comprobante_foto')) {
                $pagoRequest->files->set(
                    'comprobante_foto',
                    $request->file('comprobante_foto')
                );
            }

            $pagoController = new \App\Http\Controllers\PagoController();
            $pagoResponse = $pagoController->registrarPagoInterno($pagoRequest);

            if ($pagoResponse['status'] !== 'success') {
                throw new \Exception($pagoResponse['message']);
            }

            // ❌ ELIMINADO: fecha_limite (YA NO EXISTE EN EL DISEÑO)

            DB::commit();

            return redirect()
                ->route('membresias.index')
                ->with('success', 'Membresía registrada con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();

            throw \Illuminate\Validation\ValidationException::withMessages([
                'id_cliente' => 'Transacción cancelada: ' . $e->getMessage()
            ]);
        }
    }
}