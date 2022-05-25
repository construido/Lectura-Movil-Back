<?php

namespace App\DAL;

use App\Models\MarcaMedidor;
use DB;

class MarcaMedidorDAL
{
    public function GetRecDt($MarcaMedidor, $DataBaseAlias){
        $loMarcaMedidor = MarcaMedidor::on($DataBaseAlias)
                    ->where('MarcaMedidor', '=', $MarcaMedidor)->get();
        
        return $loMarcaMedidor;
    }
}