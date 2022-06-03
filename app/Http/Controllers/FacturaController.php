<?php

namespace App\Http\Controllers;

use App\Modelos\mPaqueteTodoFacil;

use App\BLL\ImprimirBLLPrueba;
use App\BLL\ImprimirBLL;
use App\BLL\FacturaBLL;

use App\DAL\GeneracionLecturaDAL;
use App\DAL\ClienteDAL;

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
            
            $lnOkFactura = $loFacturaBLL->RecalcularFacturaFull($GeneracionFactura, $Cliente, $Plomero, $DataBaseAlias);
            if ($lnOkFactura == -1) {
                $loPaquete->error   = 1;
                $loPaquete->status  = 0;
                $loPaquete->message = "Error al generar Factura...";
                $loPaquete->values  = $lnOkFactura;
                return response()->json($loPaquete);
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

    // public function Prueba(Request $request){
    //     $loFactura = 'http://192.168.100.95:8000/COSEPW/PDF/12831_2022-05_010100600_512_2022-06-01_19-58-38.pdf';
    //     return $loFactura;
    // }
}
