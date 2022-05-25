<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoConsumo extends Model
{
    use HasFactory;

    protected $table        = 'TIPOCONSUMO';
    protected $primaryKey   = 'TipoConsumo';
    protected $fillable     = ['Nombre', 'Usr', 'UsrFecha', 'UsrHora'];
    public $timestamps      = false;
}
