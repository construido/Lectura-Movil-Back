<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneracionFactura extends Model
{
    use HasFactory;

    /**
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       31-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      tabla GENERACIONFACTURA
     */
    protected $table        = 'GENERACIONFACTURA';
    protected $primaryKey   = 'GeneracionFactura';
    protected $fillable     = [
                                'Cobro',
                                'Zona',
                                'Ruta',
                                'Generado',
                                'Nota',
                                'FechaGeneracionLectura',
                                'GeneradoGeneracionLectura',
                                'Plomero',
                                'PorcentajeLectura',
                                'FechaGeneracionFactura',
                                'Mensaje',
                                'MontoTotal',
                                'MontoFiscal',
                                'Usr',
                                'UsrHora',
                                'UsrFecha'
                            ];
    public $timestamps      = false;
}
