<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstalacionMedidor extends Model
{
    use HasFactory;
    
    protected $table        = 'INSTALACIONMEDIDOR';
    protected $primaryKey   = 'InstalacionMedidor';
    protected $fillable     = ['Cliente', 'CodigoUbicacion', 'TipoInstalacion', 'NuevaInstalacion', 'FechaInstalacion', 'FechaTrabajo',
                                'FechaActivacion', 'FechaFacturacion', 'Plomero', 'Medidor', 'LecturaInicial', 'TipoCaneria', 'DiametroAcometida',
                                'DistanciaAcometida', 'GrupoPersonas', 'Nota', 'Usr', 'UsrHora', 'UsrFecha', 'Estado'];
    public $timestamps      = false;
}
