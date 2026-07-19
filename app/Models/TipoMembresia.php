<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoMembresia extends Model
{
    protected $table = 'tipo_membresias';
    protected $primaryKey = 'id_tipo_membresia';

    protected $fillable = [
        'nombre_membresia', 'duracion_dias', 'precio', 'estado'
    ];

    public function membresias(): HasMany {
        return $this->hasMany(Membresia::class, 'id_tipo_membresia');
    }
}