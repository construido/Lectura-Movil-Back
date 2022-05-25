<?php

namespace App\DAL;

use App\Models\Medidor;

class MedidorDAL
{
    public function GetRecDt($Medidor, $DataBaseAlias){
        // $loMedidor = Medidor::on('mysql_LMCoopaguas')
        $loMedidor = Medidor::on($DataBaseAlias)
                ->where('Medidor', '=', $Medidor)->get();
    
        return $loMedidor;
    }
}