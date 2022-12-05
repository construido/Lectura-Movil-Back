<?php

namespace App\BLL;

use App\Models\Factura;
use App\Models\ActividadCliente;
use App\Models\ParametrosGenerales;

use App\DAL\IndicePrecioConsumidorDAL;
use App\DAL\ParametrosGeneralesDAL;
use App\DAL\MedidorAnormalidadDAL;
use App\DAL\GeneracionFacturaDAL;
use App\DAL\GeneracionLecturaDAL;
use App\DAL\ParametroLecturaDAL;
use App\DAL\CategoriaDetalleDAL;
use App\DAL\FacturaDetalleDAL;
use App\DAL\CategoriaDAL;
use App\DAL\FacturaDAL;
use App\DAL\ClienteDAL;
use App\DAL\CreditoDAL;
use App\DAL\FechaDAL;

use App\Modelos\ReglaLecturacion;
use App\Modelos\GuardarErrores;

    // La primera indica el tipo de variable
    // •	l - Local
    // •	g - Global
    // •	p - Private
    // •	t - Parameter

    // La segunda letra indica el tipo de dato.
    // •	c - Character
    // •	n - Numeric
    // •	d - Date
    // •	t - DateTime
    // •	l - Logical
    // •	m - Memo
    // •	a - Array
    // •	o - Object
    // •	x - Indeterminate

class FacturaBLL
{
    public $gnGeneracionFactura, $gnServicio, $gnPlomero, $gnFactura, $gnCategoria, $gnMedidorAnormalidad, $gnError, $gnLecturado, $gnMoneda = 2, $gnMto_Pago; // gn
    public $gnId_Tipo, $gnTipo, $gnTipoCambio, $gnCobro;

    public $goParametrosGenerales, $goCategoria, $goFecha, $goCategoriaDetalle, $goIndicePrecioConsumidor; // go
    public $goGeneracionLectura, $goFacturaDetalle, $goCliente, $goGeneracionFactura, $goCredito;

    public $ParametrosGeneralesDAL, $CategoriaDetalleDAL, $CategoriaDAL, $FacturaDAL, $ClienteDAL, $MedidorAnormalidadDAL, $CreditoDAL; // DAL
    public $FechaDAL, $FacturaDetalleDAL, $GeneracionLecturaDAL, $GeneracionFacturaDAL, $IndicePrecioConsumidorDAL, $ReglaLecturacion;

    function __construct(){
        $this->IndicePrecioConsumidorDAL = new IndicePrecioConsumidorDAL;
        $this->MedidorAnormalidadDAL     = new MedidorAnormalidadDAL;
        $this->ParametrosGeneralesDAL    = new ParametrosGeneralesDAL;
        $this->GeneracionFacturaDAL      = new GeneracionFacturaDAL;
        $this->GeneracionLecturaDAL      = new GeneracionLecturaDAL;
        $this->CategoriaDetalleDAL       = new CategoriaDetalleDAL;
        $this->FacturaDetalleDAL         = new FacturaDetalleDAL;
        $this->CategoriaDAL              = new CategoriaDAL;
        $this->FacturaDAL                = new FacturaDAL;
        $this->ClienteDAL                = new ClienteDAL;
        $this->CreditoDAL                = new CreditoDAL;
        $this->FechaDAL                  = new FechaDAL;

        $this->ReglaLecturacion          = new ReglaLecturacion;
    }

    public function TieneFacturaGeneradoDeSyscoop($Cliente, $DataBaseAlias){
        $loFactura = Factura::on($DataBaseAlias)
            ->where('Cliente', '=', $Cliente)->get();
        
        return $loFactura;
    }

    public function RecalcularFacturaFull($GeneracionFactura, $Cliente, $Plomero, $DataBaseAlias){
        $this->gnError = 0;
        try{
            $this->goFecha               = $this->FechaDAL->GetRegistroDiaActual($DataBaseAlias);
            $this->goParametrosGenerales = $this->ParametrosGeneralesDAL->GetAlldt($DataBaseAlias);
            $this->goGeneracionLectura   = $this->GeneracionLecturaDAL->GetIDBy($GeneracionFactura, $Cliente, $DataBaseAlias); // TODO : se le aumento $GeneracionFactura
            $this->DO_RecalcularValoresLecturas($GeneracionFactura, $Cliente, $DataBaseAlias);
            $this->goGeneracionLectura   = $this->GeneracionLecturaDAL->GetIDBy($GeneracionFactura, $Cliente, $DataBaseAlias); // TODO : se le aumento $GeneracionFactura

            $lnLecturaActual = 0;
            $lnConsumo       = 0;
            $lnMedidorAnormalidad = 0;
            $this->gnPlomero = $Plomero;
            $this->gnFactura = $this->FacturaDAL->GetIDBy($Cliente, $DataBaseAlias); // TODO: posible uso más adelante
            $this->gnGeneracionFactura = $GeneracionFactura;

            $this->goFacturaDetalle    = $this->FacturaDetalleDAL->Seek($this->gnFactura, $DataBaseAlias); // this.drFaturaItem
            
            $this->goGeneracionFactura = $this->GeneracionFacturaDAL->GetRecDt($GeneracionFactura, $DataBaseAlias);
            $this->gnCobro         = $this->goGeneracionFactura[0]->Cobro;
            $this->goCliente = $this->ClienteDAL->GetIDBy($Cliente, $DataBaseAlias);
            
            $this->goIndicePrecioConsumidor = $this->IndicePrecioConsumidorDAL->Seek($this->gnCobro, $DataBaseAlias);         

            if (count($this->goGeneracionLectura) > 0){
                $lnLecturaActual = $this->goGeneracionLectura[0]->LecturaActual;
                $lnConsumo       = $this->goGeneracionLectura[0]->Consumo;
                $lnMedidorAnormalidad = $this->goGeneracionLectura[0]->MedidorAnormalidad;
                $this->gnLecturado    = $lnLecturaActual > 0 || $lnConsumo > 0 || $lnMedidorAnormalidad > 0;
            }

            if (count($this->goFecha) > 0){
                $dFechaActual = $this->goFecha[0]->Dia;
                $this->gnTipoCambio  = $this->goFecha[0]->CambioCompra;
            }

            $this->gnCategoria = $this->goCliente[0]->Categoria;

            $this->verConsumo($Cliente, $Plomero, $DataBaseAlias);
            $this->verAlcantar($Cliente, $Plomero, $DataBaseAlias);
            $this->verLey1886($Cliente, $DataBaseAlias);

            return $this->DO_GrabarFactura($this->gnFactura, $GeneracionFactura, $Cliente, $Plomero, $lnLecturaActual, $lnConsumo, $lnMedidorAnormalidad, $DataBaseAlias);
        }
        catch (Exception $ex){
            // string lcMetodo = String.Format("FacturaBLL.RecalcularFacturaFull():Parametros -> Id_Socio = {0}, Id_GenFact = {1}",
            //                                 tnId_Socio, tnId_GenFact);
            // ErroresLectura.GuardarErrores(GrupoError.Facturacion, ex, tnId_Plomero, lcMetodo);
            return -1;
        }
    }

    public function DO_RecalcularValoresLecturas($GeneracionFactura, $Cliente, $DataBaseAlias){
        $lnConsumoFacturado = $this->goGeneracionLectura[0]->Consumo; // TODO: hace un recalculo para actualizar el campo ConsumoFacturado = Consumo
        $MedidorAnormalidad = $this->MedidorAnormalidadDAL->GetRecDt($this->goGeneracionLectura[0]->MedidorAnormalidad, $DataBaseAlias); // TODO: reemplaza a GetIDBy(); por el momento
        $laDatosActualizar  = [];
        $laDatosActualizar['GeneracionFactura'] = $GeneracionFactura;
        $laDatosActualizar['Cliente']           = $Cliente;
        $laDatosActualizar['ConsumoFacturado']  = $lnConsumoFacturado;
        $laDatosActualizar['DataBaseAlias']     = $DataBaseAlias;
        $this->GeneracionLecturaDAL->Update($laDatosActualizar);

        if ($MedidorAnormalidad[0]->MedidorAnormalidad > 0){
            
            $lnRegla = $MedidorAnormalidad[0]->Regla;
            if ($lnRegla == $this->ReglaLecturacion->CONSUMO_PROMEDIO){
                $lnConsumoFacturado = $this->goGeneracionLectura[0]->Media;
                $laDatosActualizar['GeneracionFactura'] = $GeneracionFactura;
                $laDatosActualizar['Cliente']           = $Cliente;
                $laDatosActualizar['ConsumoFacturado']  = $lnConsumoFacturado;
                $laDatosActualizar['DataBaseAlias']     = $DataBaseAlias;
                $this->GeneracionLecturaDAL->Update($laDatosActualizar);
            }
        }
    }

    public function verConsumo($Cliente, $Plomero, $DataBaseAlias){
        try{
            $this->gnServicio = $this->goParametrosGenerales[0]->Consumo;
            $this->gnTipo     = 1;
            $this->gnId_Tipo  = $this->gnGeneracionFactura;
            $lcFiltro = $this->gnCategoria;
            $this->goCategoria = $this->CategoriaDAL->SetFilter($lcFiltro, $DataBaseAlias);

            if (count($this->goCategoria) == 0)
            {
                $lcFiltro = 1; // Categoria por defecto para Consumo
                $this->goCategoria = $this->CategoriaDAL->SetFilter($lcFiltro, $DataBaseAlias);
            }

            $lnConsumoFacturado = $this->goGeneracionLectura[0]->Consumo;
            $this->gnMoneda     = $this->goCategoria[0]->Moneda;
            $this->gnMto_Pago   = $this->r_consumo($lnConsumoFacturado, $Cliente, $DataBaseAlias);
            
            if ($this->gnMoneda == 3){
                $this->gnMoneda = 2;
            }

            $lnMontoLey1294 = $this->gnMto_Pago;
            $this->DO_GrabarDetalle($Cliente, 1, 0, $DataBaseAlias); //1= Consumo

            //=============LEY 1294: Add: 20-04-2020=============
            //&& Ley 1294 para cuando tiene Cargo Fijo y Consumo
            if ($this->goCategoria[0]->CargoAAPP > 0)
            {
                $this->gnServicio  = $this->goCategoria[0]->CargoAAPP;
                $this->gnTipo     = 1;
                $this->gnId_Tipo  = $this->gnGeneracionFactura;
                $this->gnMto_Pago = $this->goCategoria[0]->MontoCargoAAPP;
                $this->gnMoneda   = $this->goCategoria[0]->Moneda;
                $this->DO_GrabarDetalle($Cliente, $this->gnTipo, $this->gnServicio, $DataBaseAlias);
                $lnMontoLey1294 = $lnMontoLey1294 + $this->gnMto_Pago;
            }
        }
        catch (Exception $ex){
            // string lcMetodo = String.Format("FacturaBLL.verConsumo(): Id_Socio = {0},  Genlect.RowsCount = {1}," +
            //                                 "ParaGen.RowsCount = {2}, Categoria.RowsCount = {3} ",
            //                                 tnId_Socio, iLenGenLect, iLenParagen, iLenCategor);
            // ErroresLectura.GuardarErrores(GrupoError.Facturacion, ex, tnId_Plomero, lcMetodo);
        }
    }

    public function r_consumo($ConsumoFacturado, $Cliente, $DataBaseAlias){
        try{
            $Categoria  = 0;
            $Rango      = 0;
            $lnTotal    = 0;
            $ConsumoCnt = 0;
            $i          = -1;
            $lnPrecioConMedidor = $this->goCategoria[0]->PrecioConMedidor;
            $lnPrecioSinMedidor = $this->goCategoria[0]->PrecioSinMedidor;
            $lnConsumoMinimo    = $this->goCategoria[0]->ConsumoMinimo;

            if($this->goCategoria[0]->ComoAplicaMinimo == 1)
            {
                $lnMonto = $this->gnLecturado ? $lnPrecioConMedidor : $lnPrecioSinMedidor;
                $lnTotal = $this->actIPC($lnMonto);
                
                if (!$this->gnLecturado){ //!Lecturado 
                    return $lnTotal;
                }

                $ConsumoCnt = $ConsumoFacturado;
                $lcFiltro   = $this->gnCategoria;
                $this->goCategoriaDetalle = $this->CategoriaDetalleDAL->Seek($lcFiltro, $DataBaseAlias);
                $r_ini = 0;
                $r_fin = 0;
                $i     = 0;
                $ipcValue  = 0;
                $total_acc = 0;
                $rangoDiff = 0;
                $Inicio = [];
                $Fin = [];
                $Total = [];

                if ($ConsumoFacturado > $lnConsumoMinimo)
                {
                    $swNoSalir = true;
                    do
                    {
                        if ($i > count($this->goCategoriaDetalle) - 1){
                            $swNoSalir = false;
                        }

                        $r_ini = $this->goCategoriaDetalle[$i]->Inicio;
                        $r_fin = $this->goCategoriaDetalle[$i]->Fin;
                        $Inicio[$i] = $r_ini;
                        $Fin[$i] = $r_fin;

                        if (($r_ini <= $ConsumoFacturado) && ($ConsumoFacturado <= $r_fin))
                        {
                            $ipcValue = $this->actIPC($this->goCategoriaDetalle[$i]->MontoCubo);
                            $rangoDiff = ($ConsumoFacturado - $r_ini + 1);
                            $total_acc = $rangoDiff * $ipcValue;
                            $lnTotal = $lnTotal + $total_acc;
                            $swNoSalir = false;
                        }
                        else
                        {
                            $ipcValue = $this->actIPC($this->goCategoriaDetalle[$i]->MontoCubo);
                            $rangoDiff = ($r_fin - $r_ini + 1);
                            $total_acc = $rangoDiff * $ipcValue;
                            $lnTotal = $lnTotal + $total_acc;
                        }
                        $Total[$i] = $lnTotal;
                        $i++;
                    } while ($swNoSalir);
                }
        
                $lnResult = round($lnTotal, 2);
                return $lnResult;
            }
            else
            {
                if ($lnConsumoMinimo <= $ConsumoFacturado)
                {
                    $lnMonto = $this->gnLecturado ? $lnPrecioConMedidor : $lnPrecioSinMedidor;
                    $lnTotal = $this->actIPC($lnMonto);
                    return round($lnTotal, 2);
                }
                else{
                    $lnTotal = 0;
                }

                $Categoria     = $this->goCategoria[0]->Categoria == 0 ? 1 : $this->goCategoria[0]->Categoria;
                $ConsumoCnt    = $ConsumoFacturado;
                $lcFiltro      = $Categoria;
                $this->goCategoriaDetalle = $this->CategoriaDetalleDAL->Seek($lcFiltro, $DataBaseAlias);

                for ($j = 0; $j < count($this->goCategoriaDetalle) - 1 ; $j++) { // TODO: posible fallo - hacer seguimiento
                    if ($this->goCategoriaDetalle[$j]->Inicio <= $ConsumoCnt) {
                        break;
                    }
                }

                while ((0 < $ConsumoCnt) && ($i + 1 <= count($this->goCategoriaDetalle)))
                {
                    $Rango = $ConsumoCnt - $this->goCategoriaDetalle[$i]->Inicio;
                    $ConsumoCnt = $ConsumoCnt - $Rango;
                    $lnTotal = $lnTotal + $Rango * $this->actIPC($this->goCategoriaDetalle[$i]->MontoCubo);
                    $i++;
                }

                return round($lnTotal, 2);
            }
        }
        catch (Exception $ex){
            // string sParam = String.Format("consumo = {0}, id_socio = {1}", tnConsumo, tnId_Socio);
            // ErrorBLL.Guardar(ex, 0, "FacturaBLL.r_consumo(" + sParam + " )");
            // return 0;
        }
    }

    public function actIPC($Monto){
        if ($this->gnMoneda == 3)
        {
            $lnIndice;
            if (count($this->goIndicePrecioConsumidor) == 1)
            {
                $lnIndice = $this->goIndicePrecioConsumidor[0]->Indice;
                $lnResult = $Monto * $lnIndice;
                //Se da como result con 6 digitos redondeados. para su mejor precision en los posteriores calcules.
                $lnResult = round($lnResult, 6);
                return $lnResult;
            }
        }
        
        return $Monto;
    }

    public function DO_GrabarDetalle($Cliente, $ItemTipo, $Servicio, $DataBaseAlias){
        $loCategoria = new CategoriaDAL;
        $loCategoria = $loCategoria->GetRecDt($this->gnCategoria, $DataBaseAlias);

        if ($this->gnMoneda == 1)
            $this->gnMto_Pago = ($this->gnMto_Pago * $this->gnTipoCambio); // redondear a dos decimales

        if (($this->gnMto_Pago < 0) && ($this->gnTipo == 6))
            return;

        if (($this->gnMto_Pago == 0) && ($this->gnId_Tipo == 0))
            return;

        $this->gnMto_Pago = round($this->gnMto_Pago, 2);

        $lnServicio  = 0;
        $lnTipoTabla = 0;
        if (($Servicio == 0))
        {
            if (($ItemTipo == 1))
            { // Consumo
                $lnServicio = $loCategoria[0]->ItemAAPP;
                if($lnServicio == 0){
                    $lnServicio = $this->goParametrosGenerales[0]->Consumo; // oParaGene["Consumo"].ToDecimal();
                }
                $lnTipoTabla = 1; //LECTURACION
            }
            else if (($ItemTipo == 2))
            { // Alcantar
                $lnServicio = $this->goParametrosGenerales[0]->Alcantar; // oParaGene["Alcantar"].ToDecimal();
                $lnTipoTabla = 1;
            }
            else if (($ItemTipo == 3))
            { // Ley1886
                $lnServicio = $this->goParametrosGenerales[0]->Ley1886; // oParaGene["Ley1886"].ToDecimal();
                $lnTipoTabla = 1;
            }
        }
        else
        {
            $lnServicio = $Servicio;
            $lnTipoTabla = -1;
        }

        // GuardarErrores::GuardarLog(0, "F:".$this->gnFactura, json_encode($this->gnMto_Pago), " TT:".$lnTipoTabla, " S:".$lnServicio);
        $this->FacturaDetalleDAL->ActualizarItem($this->gnFactura, $Cliente, $this->gnMto_Pago, $lnTipoTabla, $lnServicio, $DataBaseAlias);

        // if (($ItemTipo == 1) && (this.ModoFacturacion == 2)) // Inspeccion
        // {
        //     if (this.oDataMore != null)
        //     {
        //         ((FactuDe_)this.oDataMore).Id_Serv = $lnId_Serv;
        //         ((FactuDe_)this.oDataMore).Tipo = $ItemTipo;
        //         ((FactuDe_)this.oDataMore).Insertar();
        //     }
        // }

        if ($Servicio == 0)
            $this->CalularSobre($Cliente, $ItemTipo, $DataBaseAlias);
    }

    public function verAlcantar($Cliente, $Plomero, $DataBaseAlias){
        $this->gnServicio = -1;
        try{
            $this->gnServicio = $this->goParametrosGenerales[0]->Alcantar;
            $this->gnTipo = 1;
            if ($this->gnServicio == 0)
                return;

            if ($this->goCliente[0]->Cloaca == 0)
                return;
        
            $lnConsumoFac = $this->goGeneracionLectura[0]->ConsumoFacturado;

            if ($this->goCategoria[0]->MonedaAASS > 0){
                $this->gnMoneda = $this->goCategoria[0]->MonedaAASS;
            }else{
                $this->gnMoneda = $this->goCategoria[0]->Moneda;
            }
        
            $this->gnMto_Pago = $this->r_Alcantar($lnConsumoFac, $Cliente, $DataBaseAlias);
            $this->gnMoneda = 2;
            $lnMontoLey1294 = $this->gnMto_Pago;
            $this->DO_GrabarDetalle($Cliente, 2, 0, $DataBaseAlias); //2=Alcantar

            //=============LEY 1294: Add: 01-05-2020=============
            //&& Ley 1294 para cuando tiene Cargo Fijo y Consumo
            if ($this->goCategoria[0]->CargoAASS > 0){
                $this->gnServicio   = $this->goCategoria[0]->CargoAASS;
                $this->gnTipo       = 1;
                $this->gnId_Tipo    = $this->gnGeneracionFactura;
                $this->gnMto_Pago   = $this->goCategoria[0]->MontoCargoAASS;

                if($this->goCategoria[0]->MonedaAASS > 0){
                    $this->gnMoneda = $this->goCategoria[0]->MonedaAASS;
                }else{
                    $this->gnMoneda = $this->goCategoria[0]->Moneda;
                }

                $this->DO_GrabarDetalle($Cliente, $this->gnTipo, $this->gnServicio, $DataBaseAlias);
                $lnMontoLey1294 = $lnMontoLey1294 + $this->gnMto_Pago;
            }
        }
        catch (Exception $loEx)
        {
            // ErroresLectura.GuardarErrores(GrupoError.Facturacion, loEx, tnId_Plomero,
            // String.Format("FacturaBLL.verAlcantar() id_socio = {0}, id_serv = {1} ", tnId_Socio, Id_Serv));
        }
    }

    public function r_Alcantar($ConsumoFacturado, $Cliente, $DataBaseAlias){
        $tnTotal;
        $lnMto_IPC = 0;
        $Categoria;
        $lnConsumoCnt;
        $i = -1;
        $lnPrecioConMedidor = $this->goCategoria[0]->PrecioConMedidorAlcantarillado;
        $lnPrecioSinMedidor = $this->goCategoria[0]->PrecioSinMedidorAlcantarillado;
        $lnConsumoMinimo    = $this->goCategoria[0]->ConsumoMinimo;

        $lnMonto = $this->gnLecturado ? $lnPrecioConMedidor : $lnPrecioSinMedidor;
        $tnTotal = $this->actIPC($lnMonto);

        if (!$this->gnLecturado)
            return $tnTotal;

        $Categoria = $this->gnCategoria == 0 ? 1 : $this->gnCategoria;
        $lnConsumoCnt = $ConsumoFacturado;
        $CategoriaDetalle = $this->CategoriaDetalleDAL->Seek($this->gnCategoria, $DataBaseAlias);
        $r_ini = 0;
        $r_fin = 0;
        $i = 0;
        // $ini = [];
        // $fin = [];
        // $total = [];
        if ($ConsumoFacturado > $lnConsumoMinimo)
        {
            $swNoSalir = true;
            do
            {
                if ($i > count($CategoriaDetalle) - 1){
                    $swNoSalir = false;
                }
                
                $r_ini = $CategoriaDetalle[$i]->Inicio;
                $r_fin = $CategoriaDetalle[$i]->Fin;
                // $ini[$i] = $r_ini;
                // $fin[$i] = $r_fin;
                if (($r_ini <= $ConsumoFacturado) && ($ConsumoFacturado <= $r_fin))
                {
                    $lnMto_IPC = $this->actIPC($CategoriaDetalle[$i]->MontoAlcantarillado);
                    $tnTotal   = $tnTotal + ($ConsumoFacturado - $r_ini + 1) * $lnMto_IPC;
                    $swNoSalir = false;
                }
                else
                {
                    $lnMto_IPC = $this->actIPC($CategoriaDetalle[$i]->MontoAlcantarillado);
                    $tnTotal   = $tnTotal + ($r_fin - $r_ini + 1) * $lnMto_IPC;
                }
                // $total[$i] = $tnTotal;
                $i++;
            } while ($swNoSalir);
            // GuardarErrores::GuardarLog(0, "r_Alcantar(CF:".$ConsumoFacturado." C:".$Cliente." DBA:".$DataBaseAlias.")", json_encode($ini), json_encode($fin), json_encode($total));
        }
        return round($tnTotal, 2);
    }

    public function verLey1886($Cliente, $DataBaseAlias){
        $this->gnServicio = $this->goParametrosGenerales[0]->Ley1886;
        if ($this->gnServicio > 0)
        {
            if ($this->goCliente[0]->Seguro != 0)
            {
                $this->gnTipo    = 1;
                $this->gnId_Tipo = $this->gnGeneracionFactura;
                $this->gnMoneda  = $this->goCategoria[0]->Moneda;
                $lnConsumo       = $this->goGeneracionLectura[0]->ConsumoFacturado;
                $this->gnMto_Pago = - $this->goParametrosGenerales[0]->Ley1886Por * $this->l_consumo($lnConsumo, $Cliente, $DataBaseAlias); // TODO : Se le aumento $DataBaseAlias

                if ($this->gnMoneda == 3) // TODO : hacer seguimiento
                    $this->gnMoneda = 2;

                $this->DO_GrabarDetalle($Cliente, 3, 0, $DataBaseAlias); //3=Ley1886
            }
        }
    }

    public function l_consumo($ConsumoArea, $Cliente, $DataBaseAlias){ // TODO : Se le aumento $DataBaseAlias
        $rtotal;
        $consumoLey = $this->goParametrosGenerales[0]->Ley1886Cub;
        if ($ConsumoArea <= $consumoLey)
            $rtotal = $this->r_consumo($ConsumoArea, $Cliente, $DataBaseAlias);
        else
            $rtotal = $this->r_consumo($consumoLey, $Cliente, $DataBaseAlias);

        return round($rtotal, 2);
    }

    public function CalularSobre($Cliente, $ItemTipo, $DataBaseAlias){
        try
        {
            for ($i=0; $i < count($this->goFacturaDetalle) ; $i++) // foreach (DataRow dr in drFaturaItem)
            {
                $ID_SOBRE = $this->goFacturaDetalle[$i]->SobreServicio; // Convert.ToInt32(dr["ID_Sobre"].ToString());
                $FacturaDetalle = $this->FacturaDetalleDAL->GetRecDt($this->gnFactura, $ID_SOBRE, $DataBaseAlias);
            
                if (count($FacturaDetalle) > 0)
                {
                    $this->gnServicio = $ID_SOBRE;
                    $this->gnMto_Pago = $FacturaDetalle[0]->MontoPago * $this->goFacturaDetalle[0]->PorcentajeServicio; // Convert.ToDecimal(drRR[0]["mto_pago"].ToString()) * Convert.ToDecimal(dr["porcserv"].ToString());
                    $this->gnMto_Pago = round($this->gnMto_Pago, 2);
            
                    // #region Es-Credito
                    if ($this->goFacturaDetalle[0]->Tipo == 6) // Es = Credito dr["tipo"].ToString().Equals("6")
                    {

                        $id_tipoCli = $this->goFacturaDetalle[0]->Id_Tipo; // Convert.ToInt32(dr["id_tipo"].ToString());
                        $this->goCredito = $this->CreditoDAL->GetCurCreditosCliente($Cliente, $this->gnCobro, $id_tipoCli, $DataBaseAlias); // DataTable dtCurCreditos = oCreditoDAL.GetCurCreditosCliente(tnId_Socio, Cobro);
            
                        if (count($this->goCredito) > 1) // drCredito.Length
                        {
                            $moneda_mes = $this->goCredito[0]->MonedaMes; // Convert.ToInt32(drCredito[0]["moneda_mes"].ToString());
                            $this->gnMoneda = $this->goCredito[0]->Moneda; // Convert.ToInt32(drCredito[0]["moneda"].ToString());
                            $psaldo = $this->goCredito[0]->Saldo; // Convert.ToDecimal(drCredito[0]["saldo"].ToString());
                            $pmto_mes = $this->gnMto_Pago;

                            if ($moneda_mes == $this->gnMoneda)
                            {
                                if ($psaldo > $pmto_mes)
                                    $this->gnMto_Pago = $pmto_mes;
                                else
                                    $this->gnMto_Pago = $psaldo;
                            }
                            else
                            {
                                $valor = ($moneda_mes == 2 ? (1 / $this->gnTipoCambio) : ($this->gnTipoCambio));
                                $pmto_mes2 = $pmto_mes * $valor;
                                if ($psaldo > $pmto_mes2)
                                    $this->gnMto_Pago = $pmto_mes2;
                                else
                                    $this->gnMto_Pago = $psaldo;
                            }
                        }
                    }
                    // #endregion
                    // Fecha:26-06-2013, Autor: Ing Cesar Corvera
                    $this->gnMto_Pago = round($this->gnMto_Pago, 2);
                    // dr["mto_pago"] = $this->gnMto_Pago;
                    $this->gnServicio = $this->goFacturaDetalle[0]->Servicio;
                    $this->FacturaDetalleDAL->ActualizarItem2($this->gnFactura, $this->gnServicio, $this->gnMto_Pago, $DataBaseAlias); // TODO: $Cliente - en duda
                }
            }
        }
        catch (Exception $ex)
        {
            // string lcMetodo = String.Format("FacturaBLL.CalularSobre(): Id_Socio = {0},  ItemTipo = {1}" +
            //                                 tnId_Socio, tnItem_Tipo);
            // ErroresLectura.GuardarErrores(GrupoError.Facturacion, ex, this.id_plomero, lcMetodo);
        }
    }

    public function DO_GrabarFactura($Factura, $GeneracionFactura, $Cliente, $Plomero, $LecturaActual, $Consumo, $MedidorAnormalidad, $DataBaseAlias){
        $lnMto_Total  = 0;
        $lnMto_Fiscal = 0;
        $lnConsumoFac = 0;
        $lnConsumoDeb = 0;
        $lnSireSe     = $this->goParametrosGenerales[0]->Sirese;
        $lnPorcSireSe = $this->goParametrosGenerales[0]->PorcSireSe;
        $loFacturaDetalle = $this->FacturaDetalleDAL->GetMontos($Factura, $lnSireSe, $DataBaseAlias);

        if (($loFacturaDetalle != null) && (count($loFacturaDetalle) > 0))
        {
            $lnMto_Total = $loFacturaDetalle[0]->MontoTotal;
            $lnMto_Fiscal = $loFacturaDetalle[0]->MontoFiscal;

        }
        // else
        //     ErrorBLL.GuardarLog("oFactuDet.GetMontos(tnId_Factura, lnSireSe)", "GRABAR_FACTURA_");

        if ($lnSireSe > 0)
            $lnMto_Total = $this->VerComisionTotal($Factura, $Cliente, $lnMto_Total, $lnSireSe, $lnPorcSireSe, $DataBaseAlias);

        $GeneracionLectura = $this->GeneracionLecturaDAL->GetIDBy($GeneracionFactura, $Cliente, $DataBaseAlias);
        $lnConsumoFac = $GeneracionLectura[0]->ConsumoFacturado;
        $lnConsumoDeb = $GeneracionLectura[0]->ConsumoDebito;

        return $this->FacturaDAL->ActualizarFactura($Factura, $GeneracionFactura, $Cliente, $Plomero, $LecturaActual, $Consumo, $MedidorAnormalidad,
                                        $lnMto_Total, $lnMto_Fiscal, $lnConsumoFac, $lnConsumoDeb, $DataBaseAlias);
    }

    public function VerComisionTotal($Factura, $Cliente, $MontoTotal, $Servicio, $lnPorcSireSe, $DataBaseAlias){
        $lnSireSeValor          = $MontoTotal * $lnPorcSireSe;
        $lnSireSeValor          = round($lnSireSeValor, 2);
        $lnMto_TotalNuevo       = $MontoTotal + $lnSireSeValor;

        // $this->goFacturaDetalle = 
        $this->FacturaDetalleDAL->ActualizarItem($Factura, $Cliente, $lnSireSeValor, -1, $Servicio, $DataBaseAlias);
        return $lnMto_TotalNuevo;
    }

    public function AplicableConsumoPromedio($MedidorAnormalidad, $DataBaseAlias){
        $llAplicable  = false;
        $lnId_MediEst = $this->MedidorAnormalidadDAL->GetRecDt($MedidorAnormalidad, $DataBaseAlias);
        if (count($lnId_MediEst) > 0)
        {
            $lnRegla = $lnId_MediEst[0]->Regla;
            $llAplicable = ($lnRegla == $this->ReglaLecturacion->CONSUMO_PROMEDIO);
        }
        return $llAplicable;
    }
}