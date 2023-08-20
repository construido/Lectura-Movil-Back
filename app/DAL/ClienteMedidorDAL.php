<?php

namespace App\DAL;

use App\Models\ClienteMedidor;
use DB;

class ClienteMedidorDAL
{
    // lcSQL = " SELECT I.* " +;
    // "   FROM _SOCIMEDI I " +;
    // "  WHERE I.ID_Socio = " + oMySQL.FOX2SQL(tnID_Socio)

    public function getAll($Cliente, $DataBaseAlias){
        $loCliente = ClienteMedidor::on($DataBaseAlias)
            ->where('Cliente', '=', $Cliente)
            ->get();

        return $loCliente;
    }
}