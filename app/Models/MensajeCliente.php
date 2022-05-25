<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensajeCliente extends Model
{
    use HasFactory;

    protected $table        = 'MENSAJECLIENTE';
    protected $primaryKey   = 'MensajeCliente';
    protected $fillable     = ['Fecha','Cliente','Cobro', 'Estado', 'Linea1', 'Linea2', 'Linea3', 'Linea4', 'Linea5'];
    public $timestamps      = false;
}
