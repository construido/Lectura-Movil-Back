<?php

namespace App\DAL;

use App\Models\GeneracionFactura;
use DB;

class GeneracionFacturaDAL
{
    public static function GetRecDt($GeneracionFactura,  $DataBaseAlias){
        $loGeneracionFactura = GeneracionFactura::on($DataBaseAlias)
                    ->where('GeneracionFactura', '=', $GeneracionFactura)->get();
        
        return $loGeneracionFactura;
    }
}