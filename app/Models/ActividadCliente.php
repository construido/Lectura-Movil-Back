<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActividadCliente extends Model
{
    use HasFactory;

    protected $table        = 'ACTIVIDADCLIENTE';
    protected $primaryKey   = 'ActividadCliente';
    protected $fillable     = ['NombreActividadCliente'];
                               
    public $timestamps      = false;
}
