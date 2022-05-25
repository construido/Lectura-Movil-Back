<?php

namespace App\Modelos;

use App\Modelos\TipoComportamiento;

class MedidorInfo{

    public $MedidorTipoConsumo                 = 0;
    public $MedidorLecturaAnterior             = 0;
    public $MedidorLecturaActual               = 0;
    public $MedidorConsumo                     = 0;
    public $MedidorCantidadMaximaMes           = 0;
    public $MedidorFinMedidor                  = 0;
    public $MedidorFactorAnterior              = 0;
    public $MedidorFactorActual                = 0;
    public $MedidorDifFactores                 = 0;
    public $MedidorFactorProximidadIzquierda   = 0;
    public $MedidorFactorProximidadDerecha     = 0;
    public $MedidorFactorLimiteConsumoPositivo = 0;
    public $MedidorFactorLimiteConsumoNegativo = 0;
    public $MedidorTipoComportamiento          = 0;

    // public function __construct(TipoComportamiento $TipoComportamiento){
    //     $this->MedidorTipoComportamiento = $TipoComportamiento;
    // }

    // public function __construct(){
    //     $MedidorTipoComportamiento = new TipoComportamiento;
    // }
    
}

?>