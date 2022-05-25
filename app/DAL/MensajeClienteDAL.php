<?php

namespace App\DAL;

use App\Models\MensajeCliente;

class MensajeClienteDAL
{
    // Utilizado para la IMPRESIÓN
    public function GetMensajes($Cliente, $Cobro, $DataBaseAlias){
        $loMensajeCliente = MensajeCliente::on($DataBaseAlias)
            ->where('Cliente', '=', $Cliente)
            ->where('Cobro', '=', $Cobro)->get();
    
        return $loMensajeCliente;
    }
}