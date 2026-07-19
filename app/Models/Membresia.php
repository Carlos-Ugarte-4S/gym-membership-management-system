<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membresia extends Model
{
    protected $table = 'membresias';
    protected $primaryKey = 'id_membresia';

    protected $fillable = [
        'id_cliente', 'id_tipo_membresia', 'id_promocion', 'fecha_inicio', 'fecha_vencimiento'
    ];

    public function cliente(): BelongsTo {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function tipoMembresia(): BelongsTo {
        return $this->belongsTo(TipoMembresia::class, 'id_tipo_membresia');
    }

    public function promocion(): BelongsTo {
        return $this->belongsTo(Promocion::class, 'id_promocion');
    }

    public function pagos(): HasMany {
        return $this->hasMany(Pago::class, 'id_membresia');
    }
}