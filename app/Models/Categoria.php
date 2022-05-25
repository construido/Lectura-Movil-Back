<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table        = 'CATEGORIA';
    protected $primaryKey   = 'Categoria';
    protected $fillable     = ['NombreCategoria', 'GrupoCategoria', 'Moneda', 'PrecioConMedidor', 'ComoAplicaMinimo', 'PrecioConMedidorAlcantarillado', 
                               'PrecioSinMedidor', 'PrecioSinMedidorAlcantarillado', 'ConsumoMinimo', 'MonedaAASS', 'MontoReconexion', 'SubCuenta', 
                               'TotalCategoria', 'CargoAAPP', 'CargoAASS', 'MontoCargoAAPP', 'MontoCargoAASS', 'ItemAAPP', 'ItemAASS', 'Usr', 'UsrHora', 'UsrFecha'];
                               
    public $timestamps      = false;
}
