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
        
        if(count($loCliente) == 0){ // Si el resultado sale vacío, se hace una verificación del campo 'ActividadCliente' y la existencia del Cliente
            $loCliente = $this->InsertarActividadCliente($Cliente, $DataBaseAlias);
        }

        return $loCliente;
    }

    public function InsertarActividadCliente($Cliente, $DataBaseAlias){
        $loCliente = Cliente::on($DataBaseAlias)->findOrFail($Cliente);

        if($loCliente){ // Se actualiza el campo 'ActividadCliente' con valor '1' por defecto
            $loCliente->ActividadCliente = 1;
            $loCliente->save();

            $loCliente = Cliente::on($DataBaseAlias)
                ->select('CLIENTE.Direccion', 'CLIENTE.Manzana', 'CLIENTE.Uv', 'CLIENTE.Lote', 'CLIENTE.Nombre',
                            'CATEGORIA.NombreCategoria', 'ACTIVIDADCLIENTE.NombreActividadCliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->join('ACTIVIDADCLIENTE', 'CLIENTE.ActividadCliente', '=', 'ACTIVIDADCLIENTE.ActividadCliente')
                ->where('CLIENTE.Cliente', '=', $Cliente)
                ->get();
        }else {
            $loCliente = []; // El Cliente no existe
        }        

        return $loCliente;
    }
}