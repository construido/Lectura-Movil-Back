<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneracionLecturaMovil extends Model
{
    use HasFactory;

    /**
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       1-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      tabla GENERACIONLECTURAMOVIL
     */
    protected $table        = 'GENERACIONLECTURAMOVIL';
    protected $primaryKey   = 'GeneracionFactura';
    protected $fillable     = [
                                'GeneracionFactura',
                                'Cliente',
                                'Fecha',
                                'Hora',
                                'Categoria',
                                'Medidor',
                                'TipoConsumo',
                                'MedidorAnormalidad2',
                                'AplicacionConMedidor',
                                'ConsumoFactura',
                                'AjusteConsumo',
                                'AjusteMonto',
                                'DesviacionSignificativa',
                                'InspeccionRequerido',
                                'Facturado',
                                'ValidoLectura'
                            ];
    public $timestamps      = false;
}
