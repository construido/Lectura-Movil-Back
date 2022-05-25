<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaConsumo extends Model
{
    use HasFactory;
    
    protected $table        = 'MEDIACONSUMO';
    protected $primaryKey   = 'MediaConsumo';
    protected $fillable     = ['MediaReferencia', 'ConsumoReferencia', 'Validar', 'Consumo_Vfp',
                                'Media_Vfp', 'Consumo_Net', 'Media_Net', 'Estado'];
    public $timestamps      = false;
}
