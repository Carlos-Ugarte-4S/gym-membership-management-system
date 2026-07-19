<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pago extends Model
{
    protected $table = 'pagos';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'id_usuario', 'id_membresia', 'monto', 'fecha_pago', 'metodo_pago'
    ];

    public function usuario(): BelongsTo {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function membresia(): BelongsTo {
        return $this->belongsTo(Membresia::class, 'id_membresia');
    }

    public function transferencia(): HasOne {
        return $this->hasOne(Transferencia::class, 'id_pago');
    }
}