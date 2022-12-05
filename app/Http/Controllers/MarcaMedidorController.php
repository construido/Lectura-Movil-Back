<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Modelos\mPaqueteTodoFacil;
use App\Models\MarcaMedidor;
use Exception;

class MarcaMedidorController extends Controller
{
    public function llenarSelectMarca(Request $request){
        $loPaquete = new mPaqueteTodoFacil();
        try {
            $loMarcaMedidor = MarcaMedidor::on($request->DataBaseAlias)->get();
            $loPaquete->values  = $loMarcaMedidor;
        } catch (Exception $th) {
            $loPaquete->error   = 1;
            $loPaquete->status  = 0;
            $loPaquete->message = $th->getMessage();
        }
        return response()->json($loPaquete);
    }
}
