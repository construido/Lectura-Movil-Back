<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicePrecioConsumidor extends Model
{
    use HasFactory;

    protected $table        = 'INDICEPRECIOCONSUMIDOR';
    protected $primaryKey   = 'IndicePrecioConsumidor';
    protected $fillable     = ['Cobro', 'Fecha', 'Indice', 'Usr', 'UsrHora', 'UsrFecha'];
                               
    public $timestamps      = false;
}
