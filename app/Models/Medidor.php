<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medidor extends Model
{
    use HasFactory;

    protected $table        = 'MEDIDOR';
    protected $primaryKey   = 'Medidor';
    protected $fillable     = ['NumeroSerie', 'Numero', 'MarcaMedidor', 'FinMedidor', 'DiametroMedidor'];
    public $timestamps      = false;
}
