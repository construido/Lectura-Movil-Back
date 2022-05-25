<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReglaLecturacionDetalle extends Model
{
    use HasFactory;
    
    protected $table        = 'REGLALECTURACIONDETALLE';
    protected $primaryKey   = 'ReglaLecturacion';
    protected $fillable     = ['Normal', 'Bajo', 'Alto', 
                               'Cero', 'Negativo', 'SinLectura', 'Asignado', 'Irreal'];
    public $timestamps      = false;
}
