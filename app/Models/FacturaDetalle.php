<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaDetalle extends Model
{
    use HasFactory;

    protected $table        = 'FACTURADETALLE';
    protected $primaryKey   = 'Factura';
    protected $fillable     = ['Serial', 'Servicio', 'MontoPago', 'PorcentajeIva', 'MontoPagado', 'Tipo', 
                                'Id_Tipo', 'NombreServicio', 'Sigla', 'SobreServicio', 'PorcentajeServicio'];
                               
    public $timestamps      = false;
}
