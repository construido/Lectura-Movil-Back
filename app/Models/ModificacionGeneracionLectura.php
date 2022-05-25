<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModificacionGeneracionLectura extends Model
{
    use HasFactory;

    /**
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       1-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      tabla MODIFICACIONGENERACIONLECTURA
     */
    protected $table        = 'MODIFICACIONGENERACIONLECTURA';
    protected $primaryKey   = 'GeneracionFactura';
    protected $fillable     = [
                                'GeneracionFactura',
                                'Cliente',
                                'Usr',
                                'Glosa',
                                'Fecha',
                                'Hora',
                                'CodigoUbicacion',
                                'LecturaAnterior',
                                'LecturaActual',
                                'Consumo',
                                'MedidorAnormalidad',
                                'MediaAnterior',
                                'Cobro',
                                'Media',
                                'ConsumoFacturado',
                                'ConsumoDebito',
                                'FechaAnterior',
                                'HoraAnterior',
                                'Categoria',
                                'Medidor',
                                'TipoConsumo',
                                'MedidorAnormalidad2',
                                'AplicoMedia',
                                'ConsuFactu',
                                'AjusteConsumo',
                                'AjusteMonto',
                                'DesviacionSignificativa',
                                'InspeccionRequerida',
                                'Facturado',
                                'ValidoLectura'
                            ];
    public $timestamps      = false;
}
