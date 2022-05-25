<?php

namespace App\DAL;

use App\Models\ParametroLectura;

class ParametroLecturaDAL
{
    public function GetAlldt($ParametroLectura, $DataBaseAlias){

        $loParametroLectura = ParametroLectura::on($DataBaseAlias)
            ->where('ParametroLectura', '=', $ParametroLectura)->get();

        return $loParametroLectura;
    }
}