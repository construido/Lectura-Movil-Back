<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    protected $table        = 'FACTURA';
    protected $primaryKey   = 'Factura';
    protected $fillable     = ['Cliente', 'Cobro', 'CodigoUbicacion', 'MontoTotal', 'MontoFiscal', 'MontoVence', 'LecturaAnterior',
                                'LecturaActual', 'FacturaPago', 'Deuda', 'Corte', 'FechaEmision', 'Estado', 'Mora', 'FechaVence',
                                'FechaLectura', 'MedidorAnormalidad', 'ConsumoMedido', 'NumeroFactura', 'Consumo', 'Mensaje',
                                'FactorCubo', 'Categoria', 'OrdenDosificacion', 'Hora', 'Medidor', 'NumeroCopias', 'Persona',
                                'ConsumoFacturado', 'ConsumoDebito', 'GeneracionFactura', 'Usr', 'UsrHora', 'UsrFecha'];
                               
    public $timestamps      = false;
}