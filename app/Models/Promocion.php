<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promocion extends Model
{
    protected $table = 'promociones';
    protected $primaryKey = 'id_promocion';

    protected $fillable = [
        'nombre_promocion', 'porcentaje_descuento', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado'
    ];

    public function membresias(): HasMany {
        return $this->hasMany(Membresia::class, 'id_promocion');
    }
}