<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaConsumo extends Model
{
    use HasFactory;

    protected $table        = 'CATEGORIACONSUMO';
    protected $primaryKey   = 'Categoria';
    protected $fillable     = ['Inicio', 'Fin', 'Variacion'];
                               
    public $timestamps      = false;
}
