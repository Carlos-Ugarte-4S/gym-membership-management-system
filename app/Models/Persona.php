<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    protected $table = 'personas';
    protected $primaryKey = 'id_persona';
    
    protected $fillable = [
        'ci', 'nombre', 'apellido', 'telefono', 'direccion', 'sexo', 'fecha_registro'
    ];

    public function usuario(): HasOne {
        return $this->hasOne(Usuario::class, 'id_persona');
    }

    public function cliente(): HasOne {
        return $this->hasOne(Cliente::class, 'id_persona');
    }
}