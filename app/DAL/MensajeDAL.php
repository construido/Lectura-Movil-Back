<?php

namespace App\DAL;

use App\Models\Mensaje;

class MensajeDAL
{
    // Utilizado para la IMPRESIÓN
    public function GetMensajes($Cobro, $DataBaseAlias){
        $loMensaje = Mensaje::on($DataBaseAlias)
                ->where('Cobro', '=', $Cobro)->get();
    
        return $loMensaje;
    }
}