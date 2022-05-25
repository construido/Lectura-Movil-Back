<?php

namespace App\DAL;

use App\Models\Factura;
use App\DAL\ParametrosGeneralesDAL;
use Tymon\JWTAuth\Facades\JWTAuth;

class FacturaDAL
{
    public function GetFacturaGenerada($Cliente, $DataBaseAlias){
        $loFactura = Factura::on($DataBaseAlias)
            ->select('Factura', 'Consumo')
            ->where('Cliente', '=', $Cliente)->get();

        return $loFactura;
    }

    public function GetIDBy($Cliente, $DataBaseAlias){
        $loFactura = Factura::on($DataBaseAlias)
            ->select('Factura')
            ->where('Cliente', '=', $Cliente)
            ->OrderBy('Cobro', 'DESC')
            ->limit(1)
            ->get();

        $loFactura = $loFactura[0]->Factura;

        return $loFactura;
    }

    public function ActualizarFactura($Factura, $GeneracionFactura, $Cliente, $Plomero, $LecturaActual, $Consumo, $MedidorAnormalidad,
        $MontoTotal, $MontoFiscal, $ConsumoFacturado, $ConsumoDebito, $DataBaseAlias){
            $loFactura = Factura::on($DataBaseAlias)
                ->where('Factura', '=', $Factura)
                ->where('Cliente', '=', $Cliente)
                ->update([
                    "MontoTotal"         => $MontoTotal,
                    "MontoFiscal"        => $MontoFiscal,
                    "MontoVence"         => $MontoTotal,
                    "LecturaActual"      => $LecturaActual,
                    "Consumo"            => $Consumo,
                    "FechaLectura"       => date("Y-m-d"),
                    "Hora"               => date("H:i:s"),
                    "NumeroFactura"      => 0,
                    "FactorCubo"         => 0,
                    "Mora"               => 0,
                    "OrdenDosificacion"  => 0,
                    "NumeroCopias"       => 0,
                    "MedidorAnormalidad" => $MedidorAnormalidad,
                    "GeneracionFactura"  => $GeneracionFactura,
                    'Usr'                => JWTAuth::user()->Usuario,
                    "ConsumoFacturado"   => $ConsumoFacturado,
                    "ConsumoDebito"      => $ConsumoDebito
                ]);
        return 0;
    }

    public function GetDatosFacturaSocio($GeneracionFactura, $Cliente, $DataBaseAlias){
        $loFactura = Factura::on($DataBaseAlias)
            ->where('GeneracionFactura', '=', $GeneracionFactura)
            ->where('Cliente', '=', $Cliente)
            ->OrderBy('Cobro', 'DESC')
            ->limit(1)
            ->get();

        return $loFactura;
    }
    
    public function GetFechaCorte($FechaEmision, $Corte, $DataBaseAlias){
        $laParametrosGenerales = new ParametrosGeneralesDAL;
        $laParametrosGenerales = $laParametrosGenerales->GetFechaCorteParaGene($DataBaseAlias);

        $CorteMes  = $laParametrosGenerales[0]->CorteMes;
        $CorteDias = $laParametrosGenerales[0]->CorteDias;

        if($Corte + 1 >= $CorteMes){
            $Fecha = date("d/m/Y ",strtotime($FechaEmision."+ ".$CorteDias." days")); // days = dias : months = meses : year = año
            return $Fecha;
        }else{
            return "";
        }
    }
}