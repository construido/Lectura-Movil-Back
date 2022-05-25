<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiametroAcometida extends Model
{
    use HasFactory;

    protected $table        = 'DIAMETROACOMETIDA';
    protected $primaryKey   = 'DiametroAcometida';
    protected $fillable     = ['NombreDiametroAcometida', 'DiametroMilimetro', 'CantidadNeutral', 'CantidadMinima', 'CantidadMaxima', 
                                'CantidadTotal', 'CantidadMaximaDia', 'CantidadMaximaMes', 'Usr', 'UsrHora', 'UsrFecha'];
    public $timestamps      = false;
}
