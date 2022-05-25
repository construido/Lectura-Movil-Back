<?php

namespace App\Http\Controllers;

use App\BLL\LecturaMovilRestNET;
use Illuminate\Http\Request;

class LecturaMovilController extends Controller
{
    public function verificarConexionRestNET(Request $request)
    {
        $loLMRestNET = new LecturaMovilRestNET(6);
        $loContents = $loLMRestNET->verificarConexionRestNET($request);
        return response()->json($loContents);
    }

    public function guardarLecturaNubeToEmpresa(Request $request)
    {
        $loLMRestNET = new LecturaMovilRestNET(6);
        $loContents = $loLMRestNET->guardarLecturaNubeToEmpresa($request);
        return response()->json($loContents);
    }
}
