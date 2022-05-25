<?php

namespace App\DAL;

use App\Models\HistoricoFactura;

use DB;

class HistoricoFacturaDAL
{
    // Utilizado para la IMPRESIÓN
    public function TraerHistorial($Cliente, $DataBaseAlias){
        $loHistoricoFactura = HistoricoFactura::on($DataBaseAlias)
            ->select('Cobro as Mes', 'NumeroFactura as Factura', 'Consumo as M3', 'Monto as MontoFactura', 'FechaLectura', 'MedidorAnormalidad',
                    DB::raw('CASE WHEN (FacturaPago = "00/00/000") THEN "  /  /    " ELSE FacturaPago END as FechaPago'),
                    DB::raw('CASE WHEN (Estado = 1) THEN "Impaga" ELSE "Pagado" END as Estado'))
            ->where('Cliente', '=', $Cliente)
            ->orderBy('Cobro', 'DESC')
            ->limit(11)
            ->get();
    
        return $loHistoricoFactura;
    }

    public function GetFechaLecturaAnterior($Cliente, $DataBaseAlias){
        $loHistoricoFactura = HistoricoFactura::on($DataBaseAlias)
        ->select('FechaLectura')
        ->where('Cliente', '=', $Cliente)
        ->orderBy('Factura', 'DESC')
        ->limit(1)
        ->get();

    return $loHistoricoFactura;
    }
}

// select FechaLectura
// FROM syscoopc_LecturaMovil.HISTORICOFACTURA
// where Cliente = 4780
// order by Factura desc
// limit 1;


// SELECT Cobro as Mes, NumeroFactura as Factura, Consumo as M3, Monto as MontoFactura, FechaLectura, MedidorAnormalidad,
// 	CASE WHEN (FacturaPago = '00/00/000') THEN '  /  /    ' ELSE FacturaPago END as FechaPago,
//     CASE WHEN (Estado = 1) THEN 'Impaga' ELSE 'Pagado' END as Estado
// FROM syscoopc_LecturaMovil.HISTORICOFACTURA
// WHERE Cliente = 4780;
