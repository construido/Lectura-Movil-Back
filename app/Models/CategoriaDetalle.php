<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaDetalle extends Model
{
    use HasFactory;

    protected $table        = 'CATEGORIADETALLE';
    protected $primaryKey   = 'Categoria';
    protected $fillable     = ['Inicio', 'Fin', 'MontoCubo', 'MontoAlcantarillado'];
                               
    public $timestamps      = false;
}
