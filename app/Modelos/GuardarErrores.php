<?php

namespace App\Modelos;

use Illuminate\Support\Facades\Storage;
use Exception;

class GuardarErrores{

    public static function GuardarErrores(Exception $Error, $CodigoClase, $Metodo){
        $texto["Error"]       = "Error:".$Error->getMessage()."\n";
        $texto["CodigoClase"] = "Código Clase:".$CodigoClase."\n";
        $texto["Metodo"]      = "Método:".$Metodo."\n";
        $texto["Linea"]       = "Línea:".$Error->getLine();

        $fecha = date('Y-m-d');
        $hora  = date('H-i-s');
        Storage::disk('local')->put('Errores/'.$fecha.'/error_'.$hora.'_.txt', $texto);
    }

    public static function GuardarLog($CodigoClase, $Metodo, $Inicio, $Fin, $Total){
        $texto["CodigoClase"] = "Código Clase:".$CodigoClase."\n";
        $texto["Metodo"]      = "Método:".$Metodo."\n";
        $texto["Inicio"]      = "Inicio:".$Inicio."\n";
        $texto["Fin"]         = "Fin:".$Fin."\n";
        $texto["Total"]       = "Total:".$Total."\n";

        $fecha = date('Y-m-d');
        $hora  = date('H-i-s');
        Storage::disk('local')->put('Logs/'.$fecha.'/log_'.$hora.'_.txt', $texto);
    }
}

?>