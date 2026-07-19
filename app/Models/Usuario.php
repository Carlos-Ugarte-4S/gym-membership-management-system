<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'id_persona', 'id_turno', 'email', 'contrasenia','login','rol', 'fecha_contrato', 'estado'
    ];

    /**
     * El nombre de la columna que almacena la contraseña.
     */
    public function getAuthPassword()
    {
        return $this->contrasenia;
    }

    public function persona(): BelongsTo {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function turno(): BelongsTo {
        return $this->belongsTo(Turno::class, 'id_turno');
    }
}