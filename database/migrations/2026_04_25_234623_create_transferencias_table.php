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
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id('id_transferencia');
            // Relación con el pago general
            $table->foreignId('id_pago')->constrained('pagos', 'id_pago')->onDelete('cascade');
            
            $table->string('banco_origen');
            $table->string('banco_destino');
            $table->string('cuenta_destino');
            $table->dateTime('fecha_transferencia');
            $table->string('codigo_transaccion');
            $table->string('comprobante_foto')->nullable();
            $table->string('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};
