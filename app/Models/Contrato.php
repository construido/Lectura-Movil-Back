<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    use HasFactory;

    protected $table        = 'CONTRATO';
    protected $primaryKey   = 'Contrato';
    protected $fillable     = ['Empresa', 'Usuario', 'Lecturador', 'FechaContrato', 'FechaLimite', 'Estado'];
                               
    public $timestamps      = false;
}
