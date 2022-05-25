<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoFactura extends Model
{
    use HasFactory;

    protected $table        = 'HISTORICOFACTURA';
    protected $primaryKey   = 'Factura';
    protected $fillable     = ['Cliente', 'NumeroFactura', 'Cobro', 'FechaEmision', 'Monto', 'Consumo',
                                'FacturaPago', 'Estado', 'FechaLectura', 'MedidorAnormalidad'];
                               
    public $timestamps      = false;
}
