<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneracionLectura extends Model
{
    use HasFactory;

    /**
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       1-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      tabla GENERACIONLECTURA
     */
    protected $table        = 'GENERACIONLECTURA';
    protected $primaryKey   = 'GeneracionFactura';
    protected $fillable     = [
                                'Cliente',
                                'Orden',
                                'CodigoUbicacion',
                                'LecturaAnterior',
                                'LecturaActual',
                                'Consumo',
                                'MedidorAnormalidad',
                                'MediaAnterior',
                                'Cobro',
                                'Media',
                                'ConsumoFacturado',
                                'ConsumoDebito'
                            ];
    public $timestamps      = false;
}
