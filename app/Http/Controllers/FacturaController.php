<?php

namespace App\Http\Controllers;

use App\Modelos\mPaqueteTodoFacil;

use App\BLL\ImprimirBLLPrueba;
use App\BLL\ImprimirBLL;
use App\BLL\FacturaBLL;

use App\DAL\GeneracionLecturaDAL;
use App\DAL\ClienteDAL;
use App\DAL\FacturaDAL;

use Illuminate\Http\Request;
use App\Models\Usuario;

class FacturaController extends Controller
{
    public function CrearFactura(Request $request){
        try {
            $GeneracionFactura = $request->tnGeneracionFactura;
            $Cliente           = $request->tnCliente;
            $Plomero           = $request->tnPlomero;
            $DataBaseAlias     = $request->DataBaseAlias;
            $EmpresaNombre     = $request->EmpresaNombre;

            $loClienteDAL = new ClienteDAL;
            $loFacturaDAL = new FacturaDAL;
            $loFacturaBLL = new FacturaBLL;
            $textimprimir = new ImprimirBLL;
            $loPaquete    = new mPaqueteTodoFacil();
            $loGeneracionLecturaDAL = new GeneracionLecturaDAL;

            $FacturaGenerada = $loFacturaBLL->TieneFacturaGeneradoDeSyscoop($Cliente, $DataBaseAlias);
            if ($FacturaGenerada == null || count($FacturaGenerada) == 0){
                $loPaquete->error   = 1;
                $loPaquete->status  = 0;
                $loPaquete->message = "El Cliente no tiene Factura...";
                $loPaquete->values  = $FacturaGenerada;
                return response()->json($loPaquete);
            }


            $loClienteDAL = $loClienteDAL->GetIDBy($Cliente, $DataBaseAlias);
            if ($loClienteDAL[0]->Estado == 1 && $loClienteDAL[0]->Medidor > 0) {
                $lnOkFactura = $loFacturaBLL->RecalcularFacturaFull($GeneracionFactura, $Cliente, $Plomero, $DataBaseAlias);
                if ($lnOkFactura == -1) {
                    $loPaquete->error   = 1;
                    $loPaquete->status  = 0;
                    $loPaquete->message = "Error al generar Factura...";
                    $loPaquete->values  = $lnOkFactura;
                    return response()->json($loPaquete);
                }
            }else{
                $Factura = $loFacturaDAL->GetIDBy($Cliente, $DataBaseAlias);
                $loFacturaDAL->ActualizarFacturaSinMedidor($Factura, $GeneracionFactura, $Cliente, $DataBaseAlias);
            }    
    
            $lnGeneracionLectura = $loGeneracionLecturaDAL->GetIDBy($GeneracionFactura, $Cliente, $DataBaseAlias); // TODO : se le aumento $GeneracionFactura
            $lnCobro            = $lnGeneracionLectura[0]->Cobro;
            $lnCodigoUbicacion  = $lnGeneracionLectura[0]->CodigoUbicacion;
            $lnLectura_Actual   = $lnGeneracionLectura[0]->LecturaActual;
            $lnLectura_Anterior = $lnGeneracionLectura[0]->LecturaAnterior;
            $lnConsumo          = $lnGeneracionLectura[0]->Consumo;
    
            $textimprimir = $textimprimir->GetFactura($Cliente, $lnCodigoUbicacion, $lnLectura_Actual, $lnLectura_Anterior, $lnConsumo, $lnCobro, $GeneracionFactura, $DataBaseAlias, $EmpresaNombre);

            $loPaquete->values = $textimprimir;
            return response()->json($loPaquete);

        } catch (Exception $th) {
            return \response()->json([
                'status' => 500,
                'message' => 'Error, contactese con el Administrador...'
            ]);
        }
    }
}
