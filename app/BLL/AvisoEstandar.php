<?php

namespace App\BLL;

use DB;

class AvisoEstandar
{
    // public function GetFactura($Cliente, $CodigoCliente, $NombreCliente, $LecturaActual, $LecturaAnterior, $Consumo, $Cobro, $GeneracionFactura, $DataBaseAlias)
    public function GetFactura($Cliente, $CodigoCliente, /* $lnNombreCliente,*/ $Lectura_Actual, $Lectura_Anterior, $Consumo,  $Cobro, $GeneracionFactura, $DataBaseAlias)
    {
        try
        {
            $laDatosFactura = GetDatosFactura($Cliente, $DataBaseAlias);
            if ($laDatosFactura != null)
                $cTextImprimir = $cTextImprimir + $laDatosFactura;
            else
                return null;

            $laHistoricoFActura = GetHistoricoFactura($Cliente, $Cobro, $Consumo, $LecturaActual, $LecturaAnterior);
            if ($laHistoricoFActura != null)
                $cTextImprimir = $cTextImprimir + $laHistoricoFActura;
            else
                return null;

            $laDatosCliente = GetDatosCliente($Cliente, $CodigoCliente, /*tcNombreSocio,*/ $LecturaActual, $LecturaAnterior, $Consumo, $GeneracionFactura, $Cobro);
            if ($laDatosCliente != null)
                $cTextImprimir = $cTextImprimir + $laDatosCliente;
            else
                return null;

            // if (tlImprimirMensaje == true)
            // {
                $laMensaje = GetMensaje($Cliente, $Cobro);
                if ($laMensaje != null)
                    $cTextImprimir = $cTextImprimir + $laMensaje;
                else
                    return null;
            // }

            $laEmisor = GetEmisor();
            if ($laEmisor != null)
                $cTextImprimir = $cTextImprimir + $laEmisor;
            else
                return null;

            //Fecha:20-09-2013 [Habilitar]IF(GuardarAvisoCobranza) -> Imprimir 
            // ErrorBLL.GuardarAvisoConbranza($cTextImprimir);

            return $cTextImprimir;
        }
        catch (Exception $ex)
        {
            // MessageBox.Show("Error de Impresion", "Syscoop Mobile");
            //return "";
            // ErrorBLL.Guardar(ex, 0, "ImprimirBoleta.getFactura()");
            return null;
        }
    }

    private function GetDatosFactura($Cliente, $DataBaseAlias){
        // try
        // {
        //     string lcText = "";
        //     #region Datos
        //     int lnIndexPosY = 329;
        //     string lcMto = string.Empty;
        //     DataTable dt = G.oFactuDet.GetDetalleFactura(tnId_Socio);
        //     string lcNombreServicio = "";
        //     lnAcuMtoTotal = 0;
        //     //===[DATOS DE LA FACTURA]===
        //     //===[ITEM | DETALLE | IMPORTE Bs.]===
        //     foreach (DataRow dr in dt.Rows)
        //     {
        //         if (( G.oParaGene.consumo == Convert.ToInt32(dr["id_serv"])
        //                 && (Convert.ToInt32(dr["mto_pago"]) == 0 )))
        //         {

        //         }
        //         else
        //         {
        //             lcText += StrToLinePrint(lnIndexPosY + nfactorEjeY, 560 + nfactorEjeX, dr["id_serv"].ToString().PadLeft(6));  //item id
        //             lcNombreServicio = dr["Nomb_Serv"].ToString();
        //             if (lcNombreServicio.Length > 19)
        //                 lcText += StrToLinePrint(lnIndexPosY + nfactorEjeY, 660 + nfactorEjeX, lcNombreServicio.PadLeft(6).Substring(0, 18));  //Concepto
        //             else
        //                 lcText += StrToLinePrint(lnIndexPosY + nfactorEjeY, 660 + nfactorEjeX, lcNombreServicio.PadLeft(6));  //Concepto
        //             lcMto = Syscoop.Soporte.LibTab.CompletarCeros(dr["mto_pago"].ToString().Trim(), 2);
        //             int lnFactorLenMto = 0;
        //             if (lcMto.ToString().Length >= 7)
        //                 lnFactorLenMto = -12;
        //             lcText += StrToLinePrint(lnIndexPosY + nfactorEjeY, 940 + nfactorEjeX + lnFactorLenMto, lcMto.PadLeft(6));  // Posicion de Montos
        //             lnAcuMtoTotal = lnAcuMtoTotal + Convert.ToDecimal(lcMto);
        //             lnIndexPosY = lnIndexPosY - 19; 
        //         }
        //     }
        //     #endregion
        //     //lcText += StrToLinePrint(85 + nfactorEjeY, 947 + nfactorEjeX, nAcuMtoTotal.ToString().PadLeft(6),5, 0); //Importe en Factura
        //     int lnFactorLenMonto = 0;
        //     if (lnAcuMtoTotal.ToString().Length >= 7)
        //         lnFactorLenMonto = -32;
        //     lcText += StrToLinePrint(100 + nfactorEjeY, 890 + nfactorEjeX + lnFactorLenMonto, lnAcuMtoTotal.ToString().PadLeft(6), 4, 0); //Importe en Factura
        //     return lcText;
        // }
        // catch (Exception ex)
        // {
        //     string lcMetodo = String.Format("ImprimirBoleta10.getDatosFactura(idsocio: {0})", tnId_Socio);
        //     ErrorBLL.Guardar(ex, 0, lcMetodo);
        //     return null;
        // }
    }
}