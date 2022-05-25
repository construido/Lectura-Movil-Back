<?php

namespace App\DAL;

use App\Models\CategoriaDetalle;
use DB;

class CategoriaDetalleDAL
{
    public function GetAlldt($DataBaseAlias){
        $loCategoriaDetalle = CategoriaDetalle::on($DataBaseAlias)->get();
        
        return $loCategoriaDetalle;
    }

    public function Seek($Categoria, $DataBaseAlias){
        $loCategoriaDetalle = CategoriaDetalle::on($DataBaseAlias)
            ->where('Categoria', '=', $Categoria)->get();
        
        return $loCategoriaDetalle;
    }
}