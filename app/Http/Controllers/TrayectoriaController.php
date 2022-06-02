<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Http\Request;
use App\Models\Trayectoria;

class TrayectoriaController extends Controller
{
    public function GuardarUbicacion($GeneracionFactura, /*$Plomero,*/ $Cliente, $Latitud, $Longitud, $DataBaseAlias){
        try {
            $Trayectoria = Trayectoria::on($DataBaseAlias)->create([
                'GeneracionFactura' => $GeneracionFactura,
                'Plomero'           => JWTAuth::user()->Usuario,
                'Cliente'           => $Cliente,
                'Latitud'           => $Latitud,
                'Longitud'          => $Longitud,
                'Fecha'             => date("Y-m-d"),
                'Hora'              => date("H-i-s"),
                'Estado'            => 1
            ]);

            return 1;
        } catch (\Throwable $th) {
            return $th;
        }
    }
}
