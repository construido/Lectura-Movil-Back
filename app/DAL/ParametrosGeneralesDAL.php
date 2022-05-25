<?php

namespace App\DAL;

use App\Models\ParametrosGenerales;

class ParametrosGeneralesDAL
{
    public function GetAlldt($DataBaseAlias){

        $loParametrosGenerales = ParametrosGenerales::on($DataBaseAlias)->get();
        return $loParametrosGenerales;
    }

    public function GetFechaCorteParaGene($DataBaseAlias){
        $loParametrosGenerales = ParametrosGenerales::on($DataBaseAlias)
            ->select('CorteMes', 'CorteDias')
            ->get();
        
        return $loParametrosGenerales;
    }
}