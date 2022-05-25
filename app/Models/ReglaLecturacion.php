<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReglaLecturacion extends Model
{
    use HasFactory;

    protected $table        = 'REGLALECTURACION';
    protected $primaryKey   = 'ReglaLecturacion';
    protected $fillable     = ['Nombre', 'Estado'];
    public $timestamps      = false;
}
