<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteMedidor extends Model
{
    use HasFactory;
    
    protected $table        = 'CLIENTEMEDIDOR';
    protected $primaryKey   = 'ClienteMedidor';
    protected $fillable     = ['Cliente', 'CodigoUbicacion', 'FechaClienteMedidor', 'FechaTrabajo', 'FechaFacturacion', 'Plomero',
                                'Nota', 'LecturaInicial', 'TipoCaneria', 'DiametroAcometida', 'DistanciaAcometida', 'MedidorPedido',
                                'GrupoPersonas', 'Usr', 'UsrHora', 'UsrFecha', 'Estado'];
    public $timestamps      = false;
}
