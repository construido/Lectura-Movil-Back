<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trayectoria extends Model
{
    use HasFactory;

    protected $table        = 'TRAYECTORIA';
    protected $primaryKey   = 'Trayectoria';
    protected $fillable     = ['GeneracionFactura', 'Plomero', 'Cliente', 'Latitud', 'Longitud', 'Fecha', 'Hora', 'Estado'];
    public $timestamps      = false;
}
