<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarcaMedidor extends Model
{
    use HasFactory;

    protected $table        = 'MARCAMEDIDOR';
    protected $primaryKey   = 'MarcaMedidor';
    protected $fillable     = ['NombreMarcaMedidor', 'Usr', 'UsrHora', 'UsrFecha'];
    public $timestamps      = false;
}
