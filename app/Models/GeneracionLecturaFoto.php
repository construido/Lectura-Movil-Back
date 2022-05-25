<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneracionLecturaFoto extends Model
{
    use HasFactory;

    protected $table        = 'GENERACIONLECTURAFOTO';
    protected $primaryKey   = 'GeneracionFactura';
    protected $fillable     = ['GeneracionFactura', 'Cliente', 'Serial', 'FotoNombre', 'Foto'];
    public $timestamps      = false;
}
