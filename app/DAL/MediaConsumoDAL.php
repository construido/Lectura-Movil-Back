<?php

namespace App\DAL;

use App\Models\MediaConsumo;
use Exception;

class MediaConsumoDAL
{
    public $MenorIgualQue = "MEI";
    public $MayorQue = "MAQ";

    public function SeValida($tcMedia, $Consumo, $lnValorRef){
        $loMediaConsumo = MediaConsumo::on('mysql_LMCoopaguas')->get();

        $lcLog = "";
        $llValidar = true;
        $llConsumo = 0;
        $llMedia = 0;
        $lnMedia_Ref = 15;
        $contador = count($loMediaConsumo);
        $indice = 0;
        $lnConsu_Ref = 0;

        try {
            if (count($loMediaConsumo) > 0) {
                while ($contador > 0) {
                    if ($loMediaConsumo[$indice]->Estado == 1) {
                        $lnConsu_Ref = $lnValorRef > 0 ? $lnValorRef : $loMediaConsumo[$indice]->ConsumoReferencia;
                        $llConsumo   = $this->EvaluarExpresion($loMediaConsumo[$indice]->Consumo_Net, "CON", $Consumo, $lnConsu_Ref);
                        $lnMedia_Ref = $lnValorRef > 0 ? $lnValorRef : $loMediaConsumo[$indice]->MediaReferencia;
                        $llMedia     = $this->EvaluarExpresion($loMediaConsumo[$indice]->Media_Net, "MED", $tcMedia, $lnMedia_Ref);

                        if ($llConsumo && $llMedia) {
                            $llValidar = $loMediaConsumo[$indice]->Validar; // == 1 ? true : false;
                            break;
                        }
                    }
                    $indice = $indice + 1;
                    $contador = $contador - 1;
                }
            }else {
                $lcLog = "Error, Sin Registros";
            }
        } catch (Exception $th) {
            $lcLog = "Error";
        }
        return $llValidar;
    }

    public function EvaluarExpresion($tcExpresion, $tcToken, $tnValor, $tnValorReferencia){
        $llValidar = true;

        if (strtoupper($tcExpresion) == $this->MenorIgualQue) {
            $llValidar = $tnValor <= $tnValorReferencia;
        }elseif (strtoupper($tcExpresion) == $this->MayorQue) {
            $llValidar = $tnValor > $tnValorReferencia;
        }

        return $llValidar;
    }
}