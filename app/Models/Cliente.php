<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';

    protected $fillable = [
        'id_persona',
        'fecha_limite',
        'estado'
    ];


    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }


    public function membresias(): HasMany
    {
        return $this->hasMany(Membresia::class, 'id_cliente');
    }


    /**
     * Obtener membresía vigente actualmente
     */
    public function getMembresiaActualAttribute()
    {
        $hoy = now()->toDateString();
        // Primero buscar una activa
        $activa = $this->membresias()
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_vencimiento', '>=', $hoy)
            ->orderByDesc('fecha_vencimiento')
            ->first();


        if ($activa) {
            return $activa;
        }
        // Si no existe activa devolver la última vencida
        return $this->membresias()
            ->orderByDesc('fecha_vencimiento')
            ->first();
    }



    /**
     * Saber si tiene membresía activa
     */
    public function getTieneMembresiaActivaAttribute()
    {
        $hoy = now()->toDateString();


        return $this->membresias()
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_vencimiento', '>=', $hoy)
            ->exists();
    }
}