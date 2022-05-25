<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fecha extends Model
{
    use HasFactory;

    protected $table        = 'FECHA';
    protected $primaryKey   = 'Fecha';
    protected $fillable     = [
                                'Dia',
                                'CambioCompra',
                                'CambioVenta',
                                'Cerrado',
                                'Nota',
                                'CambioOficial',
                                'Posteo',
                                'Usr',
                                'UsrHora',
                                'UsrFecha'
                            ];
    public $timestamps      = false;
}
