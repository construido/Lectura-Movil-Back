<?php

namespace App\DAL;

use App\Models\IndicePrecioConsumidor;
use DB;

class IndicePrecioConsumidorDAL
{
    public function Seek($Cobro, $DataBaseAlias){
        $loCategoriaDetalle = IndicePrecioConsumidor::on($DataBaseAlias)
            ->where('Cobro', '=', $Cobro)->get();
        
        return $loCategoriaDetalle;
    }
}