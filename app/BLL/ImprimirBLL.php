<?php

namespace App\BLL;

use App\Models\ParametroLectura;
use App\PlanillasPDF\CosepW;
use App\PlanillasPDF\Cosphul;

class ImprimirBLL
{
    public function GetFactura($Cliente, $CodigoUbicacion, $Lectura_Actual, $Lectura_Anterior, $Consumo, $Cobro, $GeneracionFactura, $DataBaseAlias, $EmpresaNombre){
        $parametroLectura = ParametroLectura::on($DataBaseAlias)->get();
        $tipoAviso = $parametroLectura[0]->TipoAviso;
        $pdfCosepW = new CosepW;
        $pdfCosphul = new Cosphul;
        $pdf;

        switch ($tipoAviso) {
            case 0:
                $pdf = $pdfCosepW->GetFacturaCosepW($Cliente, $CodigoUbicacion, $Lectura_Actual, $Lectura_Anterior,
                        $Consumo, $Cobro, $GeneracionFactura, $DataBaseAlias, $EmpresaNombre);
                break;
            case 1:
                $pdf = $pdfCosphul->GetFacturaCosphul($Cliente, $CodigoUbicacion, $Lectura_Actual, $Lectura_Anterior,
                        $Consumo, $Cobro, $GeneracionFactura, $DataBaseAlias, $EmpresaNombre);
                break;
        }

        return $pdf;
    }
}