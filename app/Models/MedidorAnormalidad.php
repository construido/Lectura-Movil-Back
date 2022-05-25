<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedidorAnormalidad extends Model
{
    use HasFactory;

    /**
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       1-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      tabla GENERACIONFACTURA
     */
    protected $table        = 'MEDIDORANORMALIDAD';
    protected $primaryKey   = 'MedidorAnormalidad';
    protected $fillable     = [
                                'NombreAnormalidad',
                                'Descripcion',
                                'Regla',
                                'Promedio',
                                'Sigla',
                                'Frecuencia',
                                'MedidorGrupo',
                                'TipoConsumo',
                                'Inspeccion',
                                'Informativo',
                                'TipoAa',
                                'Usr',
                                'UsrHora',
                                'UsrFecha'
                            ];
    public $timestamps      = false;
}
