<?php

namespace App\DAL;

use App\Models\FacturaDetalle;
use App\Models\Factura;
use DB;

class FacturaDetalleDAL
{
    public function GetRecDt($Factura, $Servicio, $DataBaseAlias){
        $loFacturaDetalle = FacturaDetalle::on($DataBaseAlias)
            ->where('Servicio', '=', $Servicio)
            ->where('Factura', '=', $Factura)->get();

        return $loFacturaDetalle;
    }

    public function Seek($Factura, $DataBaseAlias){
        $loFacturaDetalle = FacturaDetalle::on($DataBaseAlias)
            ->where('SobreServicio', '>', 0)
            ->where('Factura', '=', $Factura)->get();

        return $loFacturaDetalle;
    }

    public function GetMontos($Factura, $Sirese, $DataBaseAlias){
        if ($Sirese == 0) {
            $loFacturaDetalle = FacturaDetalle::on($DataBaseAlias)
                ->select((DB::raw('SUM(MontoPago) as MontoTotal')),
                            DB::raw('SUM(CASE WHEN (PorcentajeIva = 1) THEN MontoPago ELSE 00000 END) as MontoFiscal'))
                ->where('Factura', '=', $Factura)->get();
        }else{
            $loFacturaDetalle = FacturaDetalle::on($DataBaseAlias)
                ->select((DB::raw('SUM(MontoPago) as MontoTotal')),
                            DB::raw('SUM(CASE WHEN (PorcentajeIva = 1) THEN MontoPago ELSE 00000 END) as MontoFiscal'))
                ->where('Factura', '=', $Factura)
                ->where('Servicio', '<>', $Sirese)->get();
        }
        return $loFacturaDetalle;
    }

    public function ActualizarItem($Factura, $Cliente, $MontoPago, $TipoTabla, $Servicio, $DataBaseAlias){
        if ($TipoTabla > -1) {
            $loFacturaDetalle = FacturaDetalle::on($DataBaseAlias)
                ->where('Factura', '=', $Factura)
                ->where('Servicio', '=', $Servicio)
                ->update([
                    "MontoPago"   => $MontoPago,
                    "MontoPagado" => $MontoPago
                ]);
        }else {
            $loFacturaDetalle = FacturaDetalle::on($DataBaseAlias)
                ->where('Factura', '=', $Factura)
                ->where('Servicio', '=', $Servicio)
                ->where('Tipo', '=', $TipoTabla)
                ->update([
                    "MontoPago"   => $MontoPago,
                    "MontoPagado" => $MontoPago
                ]);
        }
        return $loFacturaDetalle;
    }

    public function ActualizarItem2($Factura, $Servicio, $MontoPago, $DataBaseAlias){
        $loFacturaDetalle = FacturaDetalle::on($DataBaseAlias)
            ->where('Factura', '=', $Factura)
            ->where('Servicio', '=', $Servicio)
            ->update([
                "MontoPago"   => $MontoPago
            ]);
    }

    public function GetId_Factura($Cliente, $DataBaseAlias){
        $loFactura = Factura::on($DataBaseAlias)
            ->select('Factura')
            ->where('Cliente', '=', $Cliente)
            ->OrderBy('Factura', 'DESC')
            ->limit(1)
            ->get();

        $loFactura = $loFactura[0]->Factura;

        return $loFactura;
    }

    public function GetDetalleFactura($Cliente, $DataBaseAlias){
        $loFactura = $this->GetId_Factura($Cliente, $DataBaseAlias);

        $loFacturaDetalle = FacturaDetalle::on($DataBaseAlias)
            ->join('SERVICIO', 'FACTURADETALLE.Servicio', '=', 'SERVICIO.Servicio')
            ->select('FACTURADETALLE.Servicio', 'SERVICIO.NombreServicio', 'FACTURADETALLE.MontoPago')
            ->where('FACTURADETALLE.Factura', '=', $loFactura)
            ->get();

        return $loFacturaDetalle;
    }
}
