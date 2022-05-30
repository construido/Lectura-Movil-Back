<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        } catch (\Throwable $th) {
            return 0;
        }
    }
}
