<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Exception;
use App\Models\GeneracionLecturaFoto;

class GeneracionLecturaFotoController extends Controller
{
    public function imagenStore($imagen, $GeneracionFactura, $Cobro, $CodigoUbicacion, $Cliente, $EmpresaNombre, $DataBaseAlias)
    {
        try {
            $file = $imagen;
            $url = public_path() . '/' . $EmpresaNombre . '/Foto';
            $contarImagenes = count($file);
            $namePDF = $GeneracionFactura . '_' . $Cobro . '_' . $CodigoUbicacion . '_' . $Cliente . '_' . date("Y-m-d") . '_' . date("H-i-s");

            for ($i=0; $i < $contarImagenes; $i++) {
                $fileName = $namePDF . '_' . ($i + 1) . '.jpg';
                $path = $EmpresaNombre . '/' . 'Foto/' . $fileName;

                $loUsuario = GeneracionLecturaFoto::on($DataBaseAlias)->create([
                    'GeneracionFactura' => $GeneracionFactura,
                    'Cliente'           => $Cliente,
                    'Serial'            => $i + 1,
                    'FotoNombre'        => $fileName,
                    'Foto'              => $path
                ]);

                $file[$i]->move($url, $fileName);
            }

            return 1;
        } catch (Exception $th) {
            return 0;
        }
    }

    public function imagenAllClient(Request $request)
    {
        try {
            $image = GeneracionLecturaFoto::on($request->DataBaseAlias)
                ->where('GeneracionFactura', $request->GeneracionFactura)
                ->where('Cliente', $request->Cliente)
                ->get();

            $host = $_SERVER["HTTP_HOST"];
            $path = "http://".$host;
            $array = [];
            if(count($image)){
                for ($i = 0; $i < count($image); $i++) {
                    array_push($array, ($path."/".$image[$i]->Foto));
                }
            }

            return $array;
        } catch (Exception $th) {
            return $th;
        }
    }
}
