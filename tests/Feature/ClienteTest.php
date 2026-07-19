<?php

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\Turno;
use App\Models\Cliente;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->turno = Turno::create([
        'nombre_turno' => 'Mañana',
        'hora_inicio' => '06:00:00',
        'hora_fin' => '12:00:00',
    ]);

    $this->persona = Persona::create([
        'ci' => '1111111',
        'nombre' => 'Admin',
        'apellido' => 'Test',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $this->admin = Usuario::create([
        'id_persona' => $this->persona->id_persona,
        'id_turno' => $this->turno->id_turno,
        'login' => 'admin_test',
        'email' => 'admin@test.com',
        'contrasenia' => Hash::make('password'),
        'rol' => 'Administrador',
        'fecha_contrato' => now(),
        'estado' => true,
    ]);
});

test('administrator can register a client', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/clientes', [
        'ci' => '9999999',
        'nombre' => 'John',
        'apellido' => 'Doe',
        'telefono' => '77777777',
        'direccion' => 'Calle Falsa 123',
        'sexo' => 'M',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/clientes');

    // Verificar en Base de Datos
    $persona = Persona::where('ci', '9999999')->first();
    expect($persona)->not->toBeNull();
    expect($persona->nombre)->toBe('John');

    $cliente = Cliente::where('id_persona', $persona->id_persona)->first();
    expect($cliente)->not->toBeNull();
});
