<?php

namespace App\DAL;

use App\Models\Fecha;
use DB;

class FechaDAL
{
    public function GetRegistroDiaActual($DataBaseAlias){
        $loMax = Fecha::on($DataBaseAlias)
            ->select(DB::raw('MAX(Dia) as Dia'))
            ->get();

        $loFecha = Fecha::on($DataBaseAlias)
            ->wherein('Dia', [$loMax[0]->Dia])
            ->get();
        
        return $loFecha;
    }
}
