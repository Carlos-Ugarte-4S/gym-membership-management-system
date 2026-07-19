<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            // Relación 1:1 con Persona (Herencia)
            $table->foreignId('id_persona')->constrained('personas', 'id_persona')->onDelete('cascade');
            // Relación con Turno
            $table->foreignId('id_turno')->constrained('turnos', 'id_turno');
            
            $table->string('email')->unique();
            $table->string('contrasenia');
            $table->string('login')->unique();
            $table->string('rol');  //(ej: 'admin', 'recepcionista')
            $table->date('fecha_contrato');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
