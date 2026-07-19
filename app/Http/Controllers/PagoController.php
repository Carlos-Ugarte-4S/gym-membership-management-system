<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Transferencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PagoController extends Controller
{
    // Método interno invocado de forma mandatoria por el caso de uso Registrar Membresía
    public function registrarPagoInterno(Request $request)
    {
        // Regla de Negocio RN03: El cliente ya fue validado en el paso anterior de la transacción padre
        
        // Validar estructura base del pago
        if (!$request->id_membresia || !$request->monto || !$request->metodo_pago) {
            return ['status' => 'error', 'message' => 'Datos de facturación base incompletos.'];
        }

        // Bifurcación del flujo de negocio según el método de pago seleccionado
        if ($request->metodo_pago === 'Efectivo') {
            // Flujo Principal: Registro directo de pago en efectivo
            Pago::create([
                'id_membresia' => $request->id_membresia,
                'id_usuario' => auth()->id() ?? 1, // Usuario activo en sesión que recauda
                'monto' => $request->monto,
                'fecha_pago' => now()->toDateString(),
                'metodo_pago' => 'Efectivo',
            ]);

            return ['status' => 'success', 'message' => 'Pago en efectivo registrado.'];

        } elseif ($request->metodo_pago === 'Transferencia' || $request->metodo_pago === 'QR') {
            // Flujo Alternativo A1: Pago mediante QR / Transferencia
            
            // Flujo Alternativo A3: Validar archivo de imagen estrictamente (Formatos y tamaños permitidos)
            $validadorImagen = Validator::make($request->all(), [
                'comprobante_foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // Max 2MB
                'banco_origen' => ['required', 'string'],
                'banco_destino' => ['required', 'string'],
                'cuenta_destino' => ['required', 'string'],
                'codigo_transaccion' => ['required', 'string'],
            ], [
                'comprobante_foto.required' => 'Debe adjuntar la imagen del comprobante de pago.',
                'comprobante_foto.image' => 'El comprobante debe ser una imagen válida.',
                'comprobante_foto.mimes' => 'El comprobante debe ser una imagen en formato JPG o PNG.',
                'comprobante_foto.max' => 'El archivo supera el tamaño permitido de 2MB.',
            ]);

            if ($validadorImagen->fails()) {
                return ['status' => 'error', 'message' => $validadorImagen->errors()->first()];
            }

            // Guardar físicamente el archivo del comprobante en el disco seguro del servidor
            $rutaFoto = $request->file('comprobante_foto')->store('comprobantes', 'public');

            // Registrar la entidad de Pago general
            $pago = Pago::create([
                'id_membresia' => $request->id_membresia,
                'id_usuario' => auth()->id() ?? 1,
                'monto' => $request->monto,
                'fecha_pago' => now()->toDateString(),
                'metodo_pago' => 'Transferencia',
            ]);

            // Registrar la extensión de la transacción bancaria mapeada al diagrama corregido
            Transferencia::create([
                'id_pago' => $pago->id_pago,
                'banco_origen' => $request->banco_origen,
                'banco_destino' => $request->banco_destino,
                'cuenta_destino' => $request->cuenta_destino,
                'fecha_transferencia' => now()->toDateString(),
                'codigo_transaccion' => $request->codigo_transaccion,
                'comprobante_foto' => $rutaFoto,
                'estado' => true
            ]);

            return ['status' => 'success', 'message' => 'Pago por transferencia validado e insertado.'];
        }

        return ['status' => 'error', 'message' => 'Método de pago no soportado por el sistema.'];
    }
}