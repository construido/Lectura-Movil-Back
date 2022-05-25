<?php

namespace App\DAL;

use App\Models\MedidorAnormalidad;
use DB;

class MedidorAnormalidadDAL
{
    public $Aplicar_ConsumoNormal    = 0;
    public $Aplicar_LecturaPendiente = 1;
    public $Aplicar_LecturaActual    = 2;
    public $Aplicar_FinDeCiclo       = 3;
    public $Aplicar_ConsumoPromedio  = 4;
    public $Aplicar_MedidorVolcado   = 5;
    public $Aplicar_ConsumoAsignado  = 6;
    public $Aplicar_AjusteLectura    = 7;
    public $Aplicar_InstalacionNueva = 8;

    public $cTipoConsumo;
    public $cReglaValidacion;

    // public function GetAnormalidad($MedidorAnormalidad){

    //     $laMedidorAnormalidad = MedidorAnormalidad::on('mysql_LMCoopaguas')
    //         ->select('MedidorAnormalidad', 'NombreAnormalidad', 'TipoConsumo', 'Regla', 'MedidorGrupo')
    //         ->where('MedidorAnormalidad', '=', $MedidorAnormalidad)
    //         ->get();

    //     return $laMedidorAnormalidad;
    // }

    public function Get_Regla($MedidorAnormalidad, $DataBaseAlias){
        $regla = MedidorAnormalidad::on($DataBaseAlias)
        ->where('MedidorAnormalidad', '=', $MedidorAnormalidad)
        ->get();

        return $regla;
    }

    public function Get_TipoReglaAAplicar($MedidorAnormalidad, $DataBaseAlias){
        $result = 0;
        if ($MedidorAnormalidad <= 0) {
            return $result;
        }

        $regla = $this->Get_Regla($MedidorAnormalidad, $DataBaseAlias);
        if (($regla[0]->Regla != null) && (count($regla) > 0)) {
            $result = $regla[0]->Regla;
        }

        return $result;
    }

    public function GetRecDt($MedidorAnormalidad, $DataBaseAlias){
        $loMedidorAnormalidad = MedidorAnormalidad::on($DataBaseAlias)
            ->where('MedidorAnormalidad', '=', $MedidorAnormalidad)->get();
        
        return $loMedidorAnormalidad;
    }
}