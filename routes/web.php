<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Importación de todos tus controladores implementados
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\TipoMembresiaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\BackupController;

/*
|--------------------------------------------------------------------------
| 1. RUTAS PÚBLICAS (Autenticación)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Vista del formulario de Login cargada con Vue a través de Inertia
    Route::get('/', function () {
        return Inertia::render('auth/Login'); // IMPORTANTE: 'auth' en minúsculas para coincidir con la carpeta
    })->name('login');

    // Procesar el intento de inicio de sesión (CU-01) en la misma ruta raíz
    Route::post('/', [AuthController::class, 'login'])->name('login.post');
});

// Cierre de sesión disponible para cualquier usuario autenticado
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| 2. RUTAS PROTEGIDAS - OPERATIVAS (Recepcionista y Administrador)
|--------------------------------------------------------------------------
| Ambos roles tienen permitido ejecutar las tareas de atención al cliente.
*/
Route::middleware(['auth'])->group(function () {
    
    // --- DASHBOARD ---
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/dashboard/buscar-cliente', [ClienteController::class, 'buscarClienteMembresia'])->name('dashboard.buscar-cliente');
});

Route::middleware(['auth', 'role:Recepcionista,Administrador'])->group(function () {
    // --- MÓDULO DE CLIENTES (CU-02 y CU-03) ---
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');          // Listar y Buscar
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');         // Registrar nuevo
    Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');     // Modificar datos
    Route::get('/dashboard/clientes', [ClienteController::class, 'clientesDashboard'])->name('dashboard.clientes');

    // --- MÓDULO DE MEMBRESÍAS Y PAGOS (CU-04 y CU-05) ---
    Route::get('/membresias', [MembresiaController::class, 'index'])->name('membresias.index');      // Formulario de registro
    Route::post('/membresias', [MembresiaController::class, 'store'])->name('membresias.store');     // Asignar plan e incluir pago automático

    // --- MÓDULO DE REPORTES ESTADÍSTICOS (CU-06) ---
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/financiero', [ReporteController::class, 'generarReporteFinanciero'])->name('reportes.financiero');
    Route::get('/reportes/historial-cliente/{id}',[ReporteController::class,'historialCliente']);
    });

/*
|--------------------------------------------------------------------------
| 3. RUTAS CRÍTICAS - EXCLUSIVAS DEL ADMINISTRADOR
|--------------------------------------------------------------------------
| Aplicamos un middleware personalizado o restricción de rol para funciones críticas.
*/
Route::middleware(['auth', 'role:Administrador'])->prefix('admin')->group(function () {
    
    // --- MÓDULO DE USUARIOS / EMPLEADOS (CU-07) ---
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])->name('usuarios.update');

    // --- MÓDULO DE CATÁLOGO: TIPOS DE MEMBRESÍA (CU-08) ---
    Route::get('/tipos-membresia', [TipoMembresiaController::class, 'index'])->name('tipos-membresia.index');
    Route::post('/tipos-membresia', [TipoMembresiaController::class, 'store'])->name('tipos-membresia.store');
    Route::put('/tipos-membresia/{id}', [TipoMembresiaController::class, 'update'])->name('tipos-membresia.update');
    Route::patch('/tipos-membresia/{id}/estado', [TipoMembresiaController::class, 'cambiarEstado'])->name('tipos-membresia.cambiar-estado'); // Activar/Desactivar

    // --- MÓDULO DE TURNOS (CU-09) ---
    Route::get('/turnos', [TurnoController::class, 'index'])->name('turnos.index');
    Route::post('/turnos', [TurnoController::class, 'store'])->name('turnos.store');
    Route::put('/turnos/{id}', [TurnoController::class, 'update'])->name('turnos.update');
    Route::patch('/turnos/{id}/estado', [TurnoController::class, 'cambiarEstado'])->name('turnos.cambiar-estado');

    // --- MÓDULO DE PROMOCIONES (CU-10) ---
    Route::get('/promociones', [PromocionController::class, 'index'])->name('promociones.index');
    Route::post('/promociones', [PromocionController::class, 'store'])->name('promociones.store');
    Route::put('/promociones/{id}', [PromocionController::class, 'update'])->name('promociones.update');
    Route::patch('/promociones/{id}/estado', [PromocionController::class, 'cambiarEstado'])->name('promociones.cambiar-estado');


    // --- MÓDULO DE SEGURIDAD: RESPALDOS (CU-11) ---
    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup/crear', [BackupController::class, 'crearBackup'])->name('backup.crear');
    Route::post('/backup/restaurar', [BackupController::class, 'restaurarBackup'])->name('backup.restaurar');
});
