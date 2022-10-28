<?php

namespace App\Http\Controllers;

use App\Modelos\mPaqueteTodoFacil;
use App\BLL\LecturaMovilRestNET;
use Illuminate\Http\Request;
use Storage;

class LecturaMovilController extends Controller
{
    public function WMAutenticar(Request $request){
        $loLMRestNET = new LecturaMovilRestNET(6);
        $loContents = $loLMRestNET->WMAutenticar($request->login, $request->password);
        return $loContents;
    }

    public function WMGet_Lecturas_Pendientes(){
        $loLMRestNET = new LecturaMovilRestNET(6);
        $loContents = $loLMRestNET->WMGet_Lecturas_Pendientes();
        $loContents = $loContents['diffgrdiffgram']['NewDataSet']['Table'];
        return $loContents;
    }

    public function WMSincronizacionBDListDemo(Request $request){
        $loLMRestNET = new LecturaMovilRestNET(6);
        $loContents = $loLMRestNET->WMSincronizacionBDListDemo($request);

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->error   = $loContents == 0 ? 0 : 1;
        $loPaquete->status  = $loContents;
        $loPaquete->message = $loContents == 1 ? $loPaquete->message : 'Error';
        $loPaquete->values  = $loContents == 1 ? 'Sincronizacion Correcta...' : 'Error al Sincronizar...';
        return response()->json($loPaquete);
    }
}
