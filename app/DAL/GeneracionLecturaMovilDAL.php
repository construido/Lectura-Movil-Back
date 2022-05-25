<?php

namespace App\DAL;

use App\Models\GeneracionLecturaMovil;
use App\Models\MedidorAnormalidad;
use DB;

class GeneracionLecturaMovilDAL
{
    public function Existe($GeneracionFactura, $Cliente, $DataBaseAlias){

        $loGeneracionLecturaMovil = GeneracionLecturaMovil::on($DataBaseAlias)
            ->where('GeneracionFactura', '=', $GeneracionFactura)
            ->where('Cliente', '=', $Cliente)
            ->get();

        return $loGeneracionLecturaMovil;
    }

    public function Update($TipoReglaAplicar, $GeneracionFactura, $Cliente, $Categoria, $Medidor, 
        $TipoConsumo, $AplicarPromedio, $ConsumoFacturado, $AjusteConsumo, $AjusteMonto, $DesviacionSignificativa, 
        $InspeccionRequerido, $Facturado, $ValidoLectura, $MedidorAnormalidad2, $DataBaseAlias){

        $loGeneracionLecturaMovil = GeneracionLecturaMovil::on($DataBaseAlias)
            ->where('Cliente', '=', $Cliente)
            ->where('GeneracionFactura', '=', $GeneracionFactura)
            ->update([  "Fecha"                   => date("Y-m-d"),
                        "Hora"                    => date("H:i:s"),
                        "Categoria"               => $Categoria, 
                        "Medidor"                 => $Medidor,
                        "TipoConsumo"             => $TipoConsumo,
                        "MedidorAnormalidad2"     => $MedidorAnormalidad2,
                        "AplicacionConMedidor"    => $AplicarPromedio ? 1 : 0,
                        "ConsumoFactura"          => $ConsumoFacturado,
                        "AjusteConsumo"           => $AjusteConsumo,
                        "AjusteMonto"             => $AjusteMonto,
                        "DesviacionSignificativa" => $DesviacionSignificativa ? 1 : 0,
                        "InspeccionRequerido"     => $InspeccionRequerido ? 1 : 0,
                        "Facturado"               => $Facturado ? 1 : 0,
                        "ValidoLectura"           => $ValidoLectura ? 1 : 0
                    ]);
    }

    public function Insert($TipoReglaAplicar, $GeneracionFactura, $Cliente,$Categoria, $Medidor, 
        $TipoConsumo, $AplicarPromedio, $ConsumoFacturado, $AjusteConsumo, $AjusteMonto, $DesviacionSignificativa, 
        $InspeccionRequerido, $Facturado, $ValidoLectura, $MedidorAnormalidad2, $DataBaseAlias){
        
        $loGeneracionLecturaMovil = GeneracionLecturaMovil::on($DataBaseAlias)->create([
            'GeneracionFactura'       => $GeneracionFactura,
            'Cliente'                 => $Cliente,
            'Fecha'                   => date("Y-m-d"),
            'Hora'                    => date("H:i:s"),
            "Categoria"               => $Categoria, 
            "Medidor"                 => $Medidor,
            "TipoConsumo"             => $TipoConsumo,
            "MedidorAnormalidad2"     => $MedidorAnormalidad2,
            "AplicacionConMedidor"    => $AplicarPromedio ? 1 : 0,
            "ConsumoFactura"          => $ConsumoFacturado,
            "AjusteConsumo"           => $AjusteConsumo,
            "AjusteMonto"             => $AjusteMonto,
            "DesviacionSignificativa" => $DesviacionSignificativa ? 1 : 0,
            "InspeccionRequerido"     => $InspeccionRequerido ? 1 : 0,
            "Facturado"               => $Facturado ? 1 : 0,
            "ValidoLectura"           => $ValidoLectura ? 1 : 0
        ]);
    }

    public static function GetRecDt($tcGeneracionLectura, $tcCliente, $DataBaseAlias){
        $loGeneracionLecturaMovil = GeneracionLecturaMovil::on($DataBaseAlias)
                    ->where('GeneracionFactura', '=', $tcGeneracionLectura)
                    ->where('Cliente', '=', $tcCliente)->get();
        
        return $loGeneracionLecturaMovil;
    }
}