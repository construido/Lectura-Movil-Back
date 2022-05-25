<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParaQR extends Model
{
    use HasFactory;

    protected $table        = 'PARAQR';
    protected $primaryKey   = 'ParaQR';
    protected $fillable     = ['Url', 'ComerceID', 'EtiquetaQR'];
    public $timestamps      = false;
}
