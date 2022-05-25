<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    use HasFactory;

    protected $table        = 'MENSAJE';
    protected $primaryKey   = 'Mensaje';
    protected $fillable     = ['Cobro', 'Linea1', 'Linea2', 'Linea3', 'Linea4', 'Linea5'];
    public $timestamps      = false;
}
