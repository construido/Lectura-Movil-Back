<?php

namespace App\DAL;

use App\Models\CategoriaConsumo;
use DB;

class CategoriaConsumoDAL
{
    public static function Get_LimitesConsumo($Categoria, $Media, $DataBaseAlias){
        $loCategoriaConsumo = CategoriaConsumo::on($DataBaseAlias)
                    ->where('Categoria', '=', $Categoria)
                    ->where('Inicio', '<=', $Media)
                    ->where('Fin', '>=', $Media)->get();
        
        $datos = $loCategoriaConsumo[0]->Variacion;

        $Variante = $Media * $datos;
        $Minimo = $Media - $Variante;
        $Maximo = $Media + $Variante;

        $loCategoriaConsumo = ['Maximo' => $Maximo, 'Minimo' => $Minimo];

        return $loCategoriaConsumo;
    }
}