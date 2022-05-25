<?php

namespace App\DAL;

use App\Models\ModificacionGeneracionLectura;
use DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ModificacionGeneracionLecturaDAL
{
    public function Insertar($datos){
        $lnDataBaseAlias = $datos['DataBaseAlias'];

        $loModificacionGeneracionLectura = ModificacionGeneracionLectura::on($lnDataBaseAlias)->create([
            // Datos ModificacionGeneracionLectura
            'GeneracionFactura' => $datos['GeneracionFactura'],
            'Cliente'           => $datos['Cliente'],
            'Usr'               => JWTAuth::user()->Usuario,
            'Glosa'             => $datos['Glosa'],
            'Fecha'             => date("Y-m-d"),
            'Hora'              => date("H:i:s"),

            // Datos GeneracionLectura
            'CodigoUbicacion'   => $datos['CodigoUbicacion'],
            'LecturaAnterior'   => $datos['LecturaAnterior'],
            'LecturaActual'     => $datos['LecturaActual'],
            'Consumo'           => $datos['Consumo'],
            'MedidorAnormalidad'=> $datos['MedidorAnormalidad'],
            'MediaAnterior'     => $datos['MediaAnterior'],
            'Cobro'             => $datos['Cobro'],
            'Media'             => $datos['Media'],
            'ConsumoFacturado'  => $datos['ConsumoFacturado'],
            'ConsumoDebito'     => $datos['ConsumoDebito'],

            // Datos GeneracionLecturaMovil
            'FechaAnterior'          => $datos['FechaAnterior'],
            'HoraAnterior'           => $datos['HoraAnterior'],
            'Categoria'              => $datos['Categoria'],
            'Medidor'                => $datos['Medidor'],
            'TipoConsumo'            => $datos['TipoConsumo'],
            'MedidorAnormalidad2'    => $datos['MedidorAnormalidad2'],
            'AplicoMedia'            => $datos['AplicoMedia'],
            'ConsuFactu'             => $datos['ConsuFactu'],
            'AjusteConsumo'          => $datos['AjusteConsumo'],
            'AjusteMonto'            => $datos['AjusteMonto'],
            'DesviacionSignificativa'=> $datos['DesviacionSignificativa'],
            'InspeccionRequerida'    => $datos['InspeccionRequerida'],
            'Facturado'              => $datos['Facturado'],
            'ValidoLectura'          => $datos['ValidoLectura'],
        ]);
        
        return $loModificacionGeneracionLectura;
    }
}