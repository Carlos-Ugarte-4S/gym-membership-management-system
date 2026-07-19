<?php

use App\Models\Usuario;
use App\Models\Persona;
use App\Models\Turno;

beforeEach(function () {
    // Crear el turno y persona mínimos necesarios para los usuarios
    $this->turno = Turno::create([
        'nombre_turno' => 'Mañana',
        'hora_inicio' => '06:00:00',
        'hora_fin' => '12:00:00',
    ]);
});

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/');
});

test('administrador can visit the dashboard', function () {
    $persona = Persona::create([
        'ci' => '1111111',
        'nombre' => 'Admin',
        'apellido' => 'Test',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $admin = Usuario::create([
        'id_persona' => $persona->id_persona,
        'id_turno' => $this->turno->id_turno,
        'login' => 'admin_test',
        'email' => 'admin@test.com',
        'contrasenia' => Hash::make('password'),
        'rol' => 'Administrador',
        'fecha_contrato' => now(),
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertStatus(200);
});

test('recepcionista can visit the dashboard', function () {
    $persona = Persona::create([
        'ci' => '2222222',
        'nombre' => 'Recep',
        'apellido' => 'Test',
        'sexo' => 'F',
        'fecha_registro' => now(),
    ]);

    $recepcionista = Usuario::create([
        'id_persona' => $persona->id_persona,
        'id_turno' => $this->turno->id_turno,
        'login' => 'recep_test',
        'email' => 'recep@test.com',
        'contrasenia' => Hash::make('password'),
        'rol' => 'Recepcionista',
        'fecha_contrato' => now(),
        'estado' => true,
    ]);

    $response = $this->actingAs($recepcionista)->get('/dashboard');
    $response->assertStatus(200);
});

test('both roles can access clientes module', function () {
    $personaAdmin = Persona::create([
        'ci' => '1111111',
        'nombre' => 'Admin',
        'apellido' => 'Test',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $admin = Usuario::create([
        'id_persona' => $personaAdmin->id_persona,
        'id_turno' => $this->turno->id_turno,
        'login' => 'admin_test',
        'email' => 'admin@test.com',
        'contrasenia' => Hash::make('password'),
        'rol' => 'Administrador',
        'fecha_contrato' => now(),
        'estado' => true,
    ]);

    $personaRecep = Persona::create([
        'ci' => '2222222',
        'nombre' => 'Recep',
        'apellido' => 'Test',
        'sexo' => 'F',
        'fecha_registro' => now(),
    ]);

    $recepcionista = Usuario::create([
        'id_persona' => $personaRecep->id_persona,
        'id_turno' => $this->turno->id_turno,
        'login' => 'recep_test',
        'email' => 'recep@test.com',
        'contrasenia' => Hash::make('password'),
        'rol' => 'Recepcionista',
        'fecha_contrato' => now(),
        'estado' => true,
    ]);

    // Verificar Administrador
    $this->actingAs($admin)->get('/clientes')->assertStatus(200);
    $this->actingAs($admin)->get('/membresias')->assertStatus(200);

    // Verificar Recepcionista
    $this->actingAs($recepcionista)->get('/clientes')->assertStatus(200);
    $this->actingAs($recepcionista)->get('/membresias')->assertStatus(200);
});

test('only administrador can access admin routes', function () {
    $personaAdmin = Persona::create([
        'ci' => '1111111',
        'nombre' => 'Admin',
        'apellido' => 'Test',
        'sexo' => 'M',
        'fecha_registro' => now(),
    ]);

    $admin = Usuario::create([
        'id_persona' => $personaAdmin->id_persona,
        'id_turno' => $this->turno->id_turno,
        'login' => 'admin_test',
        'email' => 'admin@test.com',
        'contrasenia' => Hash::make('password'),
        'rol' => 'Administrador',
        'fecha_contrato' => now(),
        'estado' => true,
    ]);

    $personaRecep = Persona::create([
        'ci' => '2222222',
        'nombre' => 'Recep',
        'apellido' => 'Test',
        'sexo' => 'F',
        'fecha_registro' => now(),
    ]);

    $recepcionista = Usuario::create([
        'id_persona' => $personaRecep->id_persona,
        'id_turno' => $this->turno->id_turno,
        'login' => 'recep_test',
        'email' => 'recep@test.com',
        'contrasenia' => Hash::make('password'),
        'rol' => 'Recepcionista',
        'fecha_contrato' => now(),
        'estado' => true,
    ]);

    // Administrador accede a rutas de administración
    $this->actingAs($admin)->get('/admin/usuarios')->assertStatus(200);

    // Recepcionista tiene denegado el acceso a rutas de administración
    $this->actingAs($recepcionista)->get('/admin/usuarios')->assertStatus(403);
});