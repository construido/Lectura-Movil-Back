<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credito extends Model
{
    use HasFactory;

    protected $table        = 'CREDITO';
    protected $primaryKey   = 'Credito';
    protected $fillable     = ['Cliente', 'FechaCredito', 'Servicio', 'SobreServicio', 'PorCostoServicio', 'MontoAnterior', 'NumeroCuotas',
                                'PagoCuotas', 'MontoPagado', 'MesInicio', 'MontoCredito', 'MontoMes', 'Estado', 'Moneda', 'Interes', 'Saldo', 'Nuevo', 
                                'CuotaInicial', 'MontoFactura', 'Nota', 'MonedaMes', 'PorcentajeIva', 'MontoNulo', 'Usr', 'UsrHora', 'UsrFecha'];
                               
    public $timestamps      = false;
}
