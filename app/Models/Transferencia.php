<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transferencia extends Model
{
    protected $table = 'transferencias';
    protected $primaryKey = 'id_transferencia';

    protected $fillable = [
        'id_pago', 'banco_origen', 'banco_destino', 'cuenta_destino', 
        'fecha_transferencia', 'codigo_transaccion', 'comprobante_foto', 'estado'
    ];

    public function pago(): BelongsTo {
        return $this->belongsTo(Pago::class, 'id_pago');
    }
}