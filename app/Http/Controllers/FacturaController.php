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
use DB;

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

    public function EJECUTARSCRIPT(Request $request){
        try {
            $tcSQLBase64 = $request->tcSQL;
            $tcAliasDB = $request->tcAlias;
    
            // Decodificar la consulta SQL desde Base64
            $sql = base64_decode($tcSQLBase64);
    
            // Validar que la consulta SQL es segura antes de ejecutarla
            // Esto es importante para evitar la inyección de SQL
            // Puedes agregar más validaciones según tus necesidades

             // Divide la cadena SQL en consultas individuales
                $queries = explode(';', $sql);

                $results = [];

                // Validar que cada consulta SQL sea segura y ejecutarlas
                foreach ($queries as $query) {
                    $query = trim($query);

                    if (!empty($query) &&  $this->validarConsultaSQL($query)) {
                        // Si no se proporciona un alias de base de datos, usa el alias por defecto
                        if (empty($tcAliasDB)) {
                            $result = DB::select(DB::raw($query));
                        } else {
                            $result = DB::connection($tcAliasDB)->select(DB::raw($query));
                        }
                        
                        // Agregar el resultado de cada consulta al array de resultados
                        $results[] = $result;
                    }
                }
                
           // if ($this->validarConsultaSQL($sql)) {
                
                return response()->json([
                    'status' => 200,
                    'message' => 'Consultas SQL ejecutadas con éxito',
                    'data' => $results,
                ]);
           /* } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'Consulta SQL no válida',
                ]);
            }*/
        } catch (Exception $th) {
            return \response()->json([
                'status' => 500,
                'message' => 'Error, contacte al administrador...',
            ]);
        }
    }

    function validarConsultaSQL($sql) {
        // Aquí puedes agregar lógica de validación personalizada para asegurarte de que la consulta sea segura.
        // Por ejemplo, puedes verificar que no contenga instrucciones peligrosas.
        // También puedes usar librerías de validación de consultas SQL disponibles.
        // Asegúrate de adaptar esta función según tus necesidades y requisitos de seguridad.
        return true; // O retorna false si la consulta no es válida
    }
}
