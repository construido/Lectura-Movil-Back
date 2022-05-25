<?php

namespace App\DAL;

use App\Models\Categoria;
use DB;

class CategoriaDAL
{
    public function GetConsumoMinimo($Categoria, $DataBaseAlias){
        $loCategoria = Categoria::on($DataBaseAlias)
                    ->select('ConsumoMinimo')
                    ->where('Categoria', '=', $Categoria)->get();
        
        return $loCategoria;
    }

    public function GetRecDt($Categoria, $DataBaseAlias){
        $loCategoria = Categoria::on($DataBaseAlias)
            ->where('Categoria', '=', $Categoria)->get();
        
        return $loCategoria;
    }

    public function SetFilter($Categoria, $DataBaseAlias){
        $loCategoria = Categoria::on($DataBaseAlias)
            ->where('Categoria', '=', $Categoria)->get();
        
        return $loCategoria;
    }
}