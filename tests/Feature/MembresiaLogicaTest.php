<?php

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\Turno;
use App\Models\Cliente;
use App\Models\TipoMembresia;
use App\Models\Membresia;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->turno = Turno::create([
        'nombre_turno' => 'Mañana',
        'hora_inicio' => '06:00:00',
        'hora_fin' => '12:00:00',
    ]);

    $this->personaAdmin = Persona::create([
        'ci' => '1111111',
        'nombre' => 'Admin',
        'apellido' => 'Test',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $this->admin = Usuario::create([
        'id_persona' => $this->personaAdmin->id_persona,
        'id_turno' => $this->turno->id_turno,
        'login' => 'admin_test',
        'email' => 'admin@test.com',
        'contrasenia' => Hash::make('password'),
        'rol' => 'Administrador',
        'fecha_contrato' => now(),
        'estado' => true,
    ]);

    // Crear un tipo de membresía de prueba (30 días, Bs. 150)
    $this->tipoMembresia = TipoMembresia::create([
        'nombre_membresia' => 'Mensual Test',
        'duracion_dias' => 30,
        'precio' => 150.00,
        'estado' => true,
    ]);

    // Crear un cliente de prueba
    $this->personaCliente = Persona::create([
        'ci' => '1234567',
        'nombre' => 'Juan',
        'apellido' => 'Perez',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $this->cliente = Cliente::create([
        'id_persona' => $this->personaCliente->id_persona,
        'fecha_limite' => now()->addDays(5)->toDateString(), // Cuenta con membresía activa que vence en 5 días
        'estado' => true,
    ]);

    // Crear la membresía correspondiente en la base de datos para que sea detectada como activa
    $this->membresiaExistente = Membresia::create([
        'id_cliente' => $this->cliente->id_cliente,
        'id_tipo_membresia' => $this->tipoMembresia->id_tipo_membresia,
        'fecha_inicio' => now()->subDays(25)->toDateString(),
        'fecha_vencimiento' => $this->cliente->fecha_limite,
    ]);
});

test('cannot register overlapping membership for a client with active membership without checking advance payment', function () {
    $this->actingAs($this->admin);

    $response = $this->postJson('/membresias', [
        'id_cliente' => $this->cliente->id_cliente,
        'id_tipo_membresia' => $this->tipoMembresia->id_tipo_membresia,
        'fecha_inicio' => now()->toDateString(),
        'metodo_pago' => 'Efectivo',
        'pago_adelantado' => false,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['id_cliente']);
});

test('can register advance payment membership which starts the day after active membership expires', function () {
    $this->actingAs($this->admin);

    $originalFechaLimite = $this->cliente->fecha_limite;
    $expectedFechaInicio = \Carbon\Carbon::parse($originalFechaLimite)->addDay()->toDateString();
    $expectedFechaVencimiento = \Carbon\Carbon::parse($expectedFechaInicio)->addDays($this->tipoMembresia->duracion_dias)->toDateString();

    $response = $this->postJson('/membresias', [
        'id_cliente' => $this->cliente->id_cliente,
        'id_tipo_membresia' => $this->tipoMembresia->id_tipo_membresia,
        'fecha_inicio' => now()->toDateString(),
        'metodo_pago' => 'Efectivo',
        'pago_adelantado' => true,
    ]);

    $response->assertRedirect('/membresias');

    // Verificar que la membresía fue creada con las fechas correspondientes encadenadas
    $membresia = Membresia::where('id_cliente', $this->cliente->id_cliente)->orderBy('id_membresia', 'desc')->first();
    expect($membresia)->not->toBeNull();
    expect($membresia->fecha_inicio)->toBe($expectedFechaInicio);
    expect($membresia->fecha_vencimiento)->toBe($expectedFechaVencimiento);

    // Verificar que la fecha_limite del cliente fue actualizada a la nueva fecha de vencimiento
    $this->cliente->refresh();
    expect($this->cliente->fecha_limite)->toBe($expectedFechaVencimiento);
});

test('newly registered client with default today limit but no memberships can register standard membership without error', function () {
    $this->actingAs($this->admin);

    $personaNuevo = Persona::create([
        'ci' => '8888888',
        'nombre' => 'Carlos',
        'apellido' => 'Gomez',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $clienteNuevo = Cliente::create([
        'id_persona' => $personaNuevo->id_persona,
        'fecha_limite' => now()->toDateString(), // Valor por defecto hoy
        'estado' => true,
    ]);

    // Intentamos registrar su primera membresía
    $response = $this->postJson('/membresias', [
        'id_cliente' => $clienteNuevo->id_cliente,
        'id_tipo_membresia' => $this->tipoMembresia->id_tipo_membresia,
        'fecha_inicio' => now()->toDateString(),
        'metodo_pago' => 'Efectivo',
        'pago_adelantado' => false,
    ]);

    $response->assertRedirect('/membresias');

    // Verificar que inició hoy
    $membresia = Membresia::where('id_cliente', $clienteNuevo->id_cliente)->orderBy('id_membresia', 'desc')->first();
    expect($membresia)->not->toBeNull();
    expect($membresia->fecha_inicio)->toBe(now()->toDateString());
});

test('can search client and membership status from dashboard endpoint', function () {
    $this->actingAs($this->admin);

    $response = $this->getJson('/dashboard/buscar-cliente?buscar=Perez');

    $response->assertStatus(200);
    $response->assertJsonCount(1);
    $response->assertJsonFragment([
        'nombre' => 'Juan Perez',
        'ci' => '1234567',
        'es_activa' => true,
        'fecha_limite' => $this->cliente->fecha_limite,
    ]);
});

test('can register membership with multiple periods and calculates duration and total price correctly', function () {
    $this->actingAs($this->admin);

    $personaNuevo = Persona::create([
        'ci' => '7777777',
        'nombre' => 'Pedro',
        'apellido' => 'Suarez',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $clienteNuevo = Cliente::create([
        'id_persona' => $personaNuevo->id_persona,
        'fecha_limite' => now()->toDateString(),
        'estado' => true,
    ]);

    // Registrar 3 meses del plan (Bs. 150 * 3 = Bs. 450, 30 días * 3 = 90 días)
    $response = $this->postJson('/membresias', [
        'id_cliente' => $clienteNuevo->id_cliente,
        'id_tipo_membresia' => $this->tipoMembresia->id_tipo_membresia,
        'fecha_inicio' => now()->toDateString(),
        'metodo_pago' => 'Efectivo',
        'cantidad_periodos' => 3,
        'pago_adelantado' => false,
    ]);

    $response->assertRedirect('/membresias');

    // Verificar que la membresía fue creada con una duración de 90 días
    $membresia = Membresia::where('id_cliente', $clienteNuevo->id_cliente)->orderBy('id_membresia', 'desc')->first();
    expect($membresia)->not->toBeNull();
    
    $expectedFechaVencimiento = \Carbon\Carbon::parse(now()->toDateString())->addDays(90)->toDateString();
    expect($membresia->fecha_inicio)->toBe(now()->toDateString());
    expect($membresia->fecha_vencimiento)->toBe($expectedFechaVencimiento);

    // Verificar que el pago se registró con el monto multiplicado (Bs. 150 * 3 = 450)
    $pago = $membresia->pagos()->first();
    expect($pago)->not->toBeNull();
    expect((float)$pago->monto)->toBe(450.00);

    // Verificar que la fecha límite del cliente es igual a la fecha de vencimiento
    $clienteNuevo->refresh();
    expect($clienteNuevo->fecha_limite)->toBe($expectedFechaVencimiento);
});

test('can register membership applying active promotion discount', function () {
    $this->actingAs($this->admin);

    $personaNuevo = Persona::create([
        'ci' => '6666666',
        'nombre' => 'Ana Maria',
        'apellido' => 'Casas',
        'sexo' => 'F',
        'fecha_registro' => now(),
    ]);

    $clienteNuevo = Cliente::create([
        'id_persona' => $personaNuevo->id_persona,
        'fecha_limite' => now()->toDateString(),
        'estado' => true,
    ]);

    // Crear una promoción activa (20% de descuento)
    $promocion = \App\Models\Promocion::create([
        'nombre_promocion' => 'Navideña Test',
        'porcentaje_descuento' => 20,
        'descripcion' => 'Descuento navideño del 20%',
        'fecha_inicio' => now()->subDays(5)->toDateString(),
        'fecha_fin' => now()->addDays(5)->toDateString(),
        'estado' => true,
    ]);

    // Registrar 1 mes (Bs. 150 - 20% = Bs. 120)
    $response = $this->postJson('/membresias', [
        'id_cliente' => $clienteNuevo->id_cliente,
        'id_tipo_membresia' => $this->tipoMembresia->id_tipo_membresia,
        'fecha_inicio' => now()->toDateString(),
        'metodo_pago' => 'Efectivo',
        'cantidad_periodos' => 1,
        'id_promocion' => $promocion->id_promocion,
        'pago_adelantado' => false,
    ]);

    $response->assertRedirect('/membresias');

    // Verificar que la membresía fue creada con la promoción
    $membresia = Membresia::where('id_cliente', $clienteNuevo->id_cliente)->orderBy('id_membresia', 'desc')->first();
    expect($membresia)->not->toBeNull();
    expect($membresia->id_promocion)->toBe($promocion->id_promocion);

    // Verificar que el pago se registró con el descuento aplicado (Bs. 150 - 20% = 120)
    $pago = $membresia->pagos()->first();
    expect($pago)->not->toBeNull();
    expect((float)$pago->monto)->toBe(120.00);
});

test('can register membership paying via transfer with uploaded QR image', function () {
    $this->actingAs($this->admin);

    $personaNuevo = Persona::create([
        'ci' => '5555555',
        'nombre' => 'Luis Alberto',
        'apellido' => 'Sosa',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $clienteNuevo = Cliente::create([
        'id_persona' => $personaNuevo->id_persona,
        'fecha_limite' => now()->toDateString(),
        'estado' => true,
    ]);

    // Crear un archivo falso de comprobante
    $comprobante = \Illuminate\Http\UploadedFile::fake()->create('comprobante_qr.png', 500, 'image/png');

    $response = $this->postJson('/membresias', [
        'id_cliente' => $clienteNuevo->id_cliente,
        'id_tipo_membresia' => $this->tipoMembresia->id_tipo_membresia,
        'fecha_inicio' => now()->toDateString(),
        'metodo_pago' => 'Transferencia',
        'banco_origen' => 'Banco BNB',
        'banco_destino' => 'Banco Union',
        'cuenta_destino' => '123456789',
        'codigo_transaccion' => 'TX-999123',
        'comprobante_foto' => $comprobante,
        'pago_adelantado' => false,
    ]);

    $response->assertRedirect('/membresias');

    // Verificar que la membresía fue creada
    $membresia = Membresia::where('id_cliente', $clienteNuevo->id_cliente)->orderBy('id_membresia', 'desc')->first();
    expect($membresia)->not->toBeNull();

    // Verificar que el pago se guardó con transferencia y la foto existe
    $pago = $membresia->pagos()->first();
    expect($pago)->not->toBeNull();
    expect($pago->metodo_pago)->toBe('Transferencia');

    $transferencia = $pago->transferencia;
    expect($transferencia)->not->toBeNull();
    expect($transferencia->banco_origen)->toBe('Banco BNB');
    expect($transferencia->comprobante_foto)->toStartWith('comprobantes/');
});
