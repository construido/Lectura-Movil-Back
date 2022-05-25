<?php

namespace App\DAL;

use App\Models\Cliente;
use DB;

class ClienteDAL
{
    public static function Get_DatosConMedidor($Cliente, $DataBaseAlias){
        $loCliente = Cliente::on($DataBaseAlias)
            ->select('Medidor', 'Categoria', 'Corte')
            ->where('Cliente', '=', $Cliente)->get();

        return $loCliente;
    }

    public function GetIDBy($Cliente, $DataBaseAlias){
        $loCliente = Cliente::on($DataBaseAlias)
            ->where('Cliente', '=', $Cliente)->get();

        return $loCliente;
    }

    public function EstaCortado($Cliente, $DataBaseAlias){
        $loCliente = Cliente::on($DataBaseAlias)
            ->where('Cliente', '=', $Cliente)->get();
        
        $loCliente = $loCliente[0]->Corte;

        return $loCliente;
    }

    public function InvalidarAutoReconexion($Cliente, $DataBaseAlias){ // TODO : hacer seguimiento
        $loCliente = Cliente::on($DataBaseAlias)->findOrFail($Cliente);
        $loCliente->Corte = 0;
        $loCliente->save();

        return $loCliente;
    }

    // Utilizado para la IMPRESIÓN
    public function GetDatosCliente($Cliente, $DataBaseAlias){
        $loCliente = Cliente::on($DataBaseAlias)
            ->select('CLIENTE.Direccion', 'CLIENTE.Manzana', 'CLIENTE.Uv', 'CLIENTE.Lote', 'CLIENTE.Nombre', // TODO : Nombre se reemplazo por Medidor
                        'CATEGORIA.NombreCategoria', 'ACTIVIDADCLIENTE.NombreActividadCliente')
            ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
            ->join('ACTIVIDADCLIENTE', 'CLIENTE.ActividadCliente', '=', 'ACTIVIDADCLIENTE.ActividadCliente')
            ->where('CLIENTE.Cliente', '=', $Cliente)
            ->get();

        return $loCliente;
    }
}