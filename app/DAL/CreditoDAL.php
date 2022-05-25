<?php

namespace App\DAL;

use App\Models\Credito;
use DB;

class CreditoDAL
{
    public function GetCurCreditosCliente($Cliente, $MesCobro, $id_tipoCli, $DataBaseAlias){
        $loCliente = Credito::on($DataBaseAlias)
            ->wherein('Cliente', [$Cliente])
            ->where('Credito', '=', $id_tipoCli)
            ->where('Saldo', '>', 0)
            ->where('Estado', '=', 0)
            ->where('MesInicio', '<=', $MesCobro)->get();

        return $loCliente;
    }
}