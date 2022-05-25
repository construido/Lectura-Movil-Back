<?php

namespace App\DAL;

use App\Models\ReglaLecturacionDetalle;
use DB;

class ReglaLecturacionDetalleDAL
{
    public $ReglaLec;
    public $Normal;
    public $Bajo;
    public $Alto;
    public $Cero;
    public $Negativo;
    public $SinLectura;
    public $Asignado;

    public function __construct(){
        $this->ReglaLec   = "ReglaLecturacionDetalle";
        $this->Normal     = "Normal";
        $this->Bajo       = "Bajo";
        $this->Alto       = "Alto";
        $this->Cero       = "Cero";
        $this->Negativo   = "Negativo";
        $this->SinLectura = "SinLectura";
        $this->Asignado   = "Asignado";
    }

    public function ReglaAplicable($tnTipoConsumo)
    {
        $llResult = false;
            $lcTipoConsumo = $this->GetFieldName($tnTipoConsumo);
            
            if ($lcTipoConsumo != "null")
            {
                $llResult = $lcTipoConsumo != "null" ? true : false;
            }

        return $llResult;
    }

    public function GetFieldName($tnTipoConsumo)
    {
        $lcResult = "";
        switch ($tnTipoConsumo)
        {
            case 1:
                $lcResult = "Normal";
                break;
            case 2:
                $lcResult = "Bajo";
                break;
            case 3:
                $lcResult = "Alto";
                break;
            case 4:
                $lcResult = "Cero";
                break;
            case 5:
                $lcResult = "Negativo";
                break;
            case 6:
                $lcResult = "SinLectura";
                break;
            case 7:
                $lcResult = "Asignado";
                break;
            default:
                $lcResult = "null";
                break;
        }
        return $lcResult;
    }

    public function GetIDBy($Regla){

        $laReglaLecturacionDetalle = ReglaLecturacionDetalle::on('mysql_LMCoopaguas')
            ->where('ReglaLecturacion', '=', $Regla)
            ->get();

        return $laReglaLecturacionDetalle;
    }


}