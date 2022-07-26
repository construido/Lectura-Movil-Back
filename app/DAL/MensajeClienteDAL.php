<?php

namespace App\DAL;

use App\Models\MensajeCliente;
use App\Models\Mensaje;

class MensajeClienteDAL
{
    // Utilizado para la IMPRESIÓN
    public function GetMensajes($Cliente, $Cobro, $DataBaseAlias){
        $loMensaje = MensajeCliente::on($DataBaseAlias)
            ->where('Cliente', '=', $Cliente)
            ->where('Cobro', '=', $Cobro)->get();
        
        if(count($loMensaje) == 0){
            $loMensaje = Mensaje::on($DataBaseAlias)
                ->where('Cobro', '=', $Cobro)->get();
        }
    
        return $loMensaje;
    }
}