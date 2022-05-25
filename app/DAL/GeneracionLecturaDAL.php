<?php

namespace App\DAL;

use App\Models\Medidor;
use App\Models\DiametroAcometida;
use App\Models\GeneracionLectura;
use App\Models\GeneracionLecturaMovil;
use App\Models\ModificacionGeneracionLectura;
use App\Http\Controllers\GeneracionLecturaMovilController;
use Exception;
use Illuminate\Support\Facades\DB;

class GeneracionLecturaDAL
{
    /**
     * Metodo que devuelve un objeto con la GENERACIONLECTURA registrado
     * @method      registrarLectura()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       06-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      registro GENERACIONLECTURA
     */
    public static function registrarLectura($datos){
        try {
            DB::beginTransaction();
            
            $GeneracionFactura  = $datos['tcGeneracionLectura'];
            $Cliente            = $datos['tcCliente'];
            $LecturaActual      = $datos['tcLecturaActual'];
            $MedidorAnormalidad = $datos['tcMedidorAnormalidad'];
            $ConsumoFacturado   = $datos['tcLecturaActual'] - $datos['tcLecturaAnterior'];
            $Consumo            = $datos['tcLecturaActual'] - $datos['tcLecturaAnterior'];

            $loGeneracionLectura = GeneracionLectura::on('mysql_LMCoopaguas')
                                ->where('Cliente', '=', $Cliente)
                                ->where('GeneracionFactura', '=', $GeneracionFactura)
                                ->update([  "LecturaActual"      => $LecturaActual, 
                                            "MedidorAnormalidad" => $MedidorAnormalidad, 
                                            "ConsumoFacturado"   => $ConsumoFacturado, 
                                            "Consumo"            => $Consumo
                                        ]);
            
            $laGeneracionLecturaMovil['GeneracionFactura'] = $GeneracionFactura;
            $laGeneracionLecturaMovil['Cliente'] = $Cliente;
            GeneracionLecturaMovilController::guardarGeneracionLecturaMovil($laGeneracionLecturaMovil);

            DB::commit();
            return $loGeneracionLectura;

        } catch (Exception $e) {
            DB::rollback();
            return $e->getMessage();
        }
    }

    public function ObtenerMedidor($Medidor, $DataBaseAlias){
        $loMedidor = Medidor::on($DataBaseAlias)
                    ->where('Medidor', '=', $Medidor)->get();
        
        return $loMedidor;
    }

    public function ObtenerDiametroMedidor($DiametroMedidor, $DataBaseAlias){
        $loDiametroAcometida = DiametroAcometida::on($DataBaseAlias)
                    ->where('DiametroAcometida', '=', $DiametroMedidor)->get();
        return $loDiametroAcometida;
    }

    public function GetRecDt($GeneracionFactura, $DataBaseAlias){ // TODO - Sin uso - verificar
        $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('GeneracionFactura', '=', $GeneracionFactura)->get();
        
        return $loGeneracionLectura;
    }

    public function GetRecDt2($tcGeneracionLectura, $tcCliente, $DataBaseAlias){
        $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('GeneracionFactura', '=', $tcGeneracionLectura)
                    ->where('Cliente', '=', $tcCliente)->get();
        
        return $loGeneracionLectura;
    }

    public function actualizarLecturaDAL($TipoReglaAplicar, $gnGeneracionFactura, $gnCliente, $gnLecturaActual, $gnConsumoActual, $gnMedidorAnormalidad, $DataBaseAlias, $validar){
        $gnLecturaActual    = $gnLecturaActual == null ? 0 : $gnLecturaActual; // TODO - hacer seguimiento para LecturaActual, ConsumoFacturado y ConsumoActual:no debe guardar valor negativo
        $gnConsumoActual    = $gnConsumoActual < 0 ? 0 : $gnConsumoActual;
        $lnConsumoFacturado = $validar == true ? $gnConsumoActual : 0;
            
        switch ($TipoReglaAplicar) {
            case 0: // Aplicar Cosumo Normal sin Anormalidad
                $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('Cliente', '=', $gnCliente)
                    ->where('GeneracionFactura', '=', $gnGeneracionFactura)
                    ->update([ "LecturaActual"       => $gnLecturaActual,
                                "Consumo"            => $gnConsumoActual,
                                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                                "ConsumoFacturado"   => $lnConsumoFacturado,
                ]);
                break;
            
            case 1: // Aplicar Lectura Pendiente
                $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('Cliente', '=', $gnCliente)
                    ->where('GeneracionFactura', '=', $gnGeneracionFactura)
                    ->update([ "LecturaActual"       => $gnLecturaActual,
                                "Consumo"            => $gnConsumoActual,
                                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                                "ConsumoFacturado"   => $lnConsumoFacturado,
                ]);
                break;

            case 2: // Aplicar Lectura Actual con Anormalidad
                $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('Cliente', '=', $gnCliente)
                    ->where('GeneracionFactura', '=', $gnGeneracionFactura)
                    ->update([ "LecturaActual"       => $gnLecturaActual,
                                "Consumo"            => $gnConsumoActual,
                                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                                "ConsumoFacturado"   => $lnConsumoFacturado,
                ]);
                break;

            case 3: // Aplicar Fin de Ciclo
                $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('Cliente', '=', $gnCliente)
                    ->where('GeneracionFactura', '=', $gnGeneracionFactura)
                    ->update([ "LecturaActual"       => $gnLecturaActual,
                                "Consumo"            => $gnConsumoActual,
                                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                                "ConsumoFacturado"   => $lnConsumoFacturado,
                ]);
                break;

            case 4: // Aplica Consumo Promedio
                $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('Cliente', '=', $gnCliente)
                    ->where('GeneracionFactura', '=', $gnGeneracionFactura)
                    ->update([ "LecturaActual"       => $gnLecturaActual,
                                "Consumo"            => $gnConsumoActual,
                                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                                "ConsumoFacturado"   => $lnConsumoFacturado,
                ]);
                break;
                
            case 5: // Aplicar Medidor Volcado
                $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('Cliente', '=', $gnCliente)
                    ->where('GeneracionFactura', '=', $gnGeneracionFactura)
                    ->update([ "LecturaActual"       => $gnLecturaActual,
                                "Consumo"            => $gnConsumoActual,
                                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                                "ConsumoFacturado"   => $lnConsumoFacturado,
                ]);
                break;

            case 7: // Aplicar Ajuste Lectura
                $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('Cliente', '=', $gnCliente)
                    ->where('GeneracionFactura', '=', $gnGeneracionFactura)
                    ->update([ "LecturaActual"       => $gnLecturaActual,
                                "Consumo"            => $gnConsumoActual,
                                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                                "ConsumoFacturado"   => $lnConsumoFacturado,
                ]);
                break;

            case 8: // Aplicar Instalacion Nueva
                $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                    ->where('Cliente', '=', $gnCliente)
                    ->where('GeneracionFactura', '=', $gnGeneracionFactura)
                    ->update([ "LecturaActual"       => $gnLecturaActual,
                                "Consumo"            => $gnConsumoActual,
                                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                                "ConsumoFacturado"   => $lnConsumoFacturado,
                ]);
                break;
            }

        return $loGeneracionLectura;
    }

    public static function ActualizarLecturaSinMedidorDAL($gnGeneracionFactura, $gnCliente, $gnLecturaActual, $gnConsumoActual, $gnMedidorAnormalidad, $DataBaseAlias){
        $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
            ->where('Cliente', '=', $gnCliente)
            ->where('GeneracionFactura', '=', $gnGeneracionFactura)
            ->update([
                "LecturaActual" => $gnLecturaActual,
                "Consumo"       => $gnConsumoActual,
                "MedidorAnormalidad" => $gnMedidorAnormalidad,
                "ConsumoFacturado"   => $gnConsumoActual
            ]);
        
        return $loGeneracionLectura;
    }

    public function GetIDBy($GeneracionFactura, $Cliente, $DataBaseAlias){
        $loCliente = GeneracionLectura::on($DataBaseAlias)
                    ->where('GeneracionFactura', '=', $GeneracionFactura)
                    ->where('Cliente', '=', $Cliente)->get();
        
        return $loCliente;
    }

    public function Update($laDatosActualizar){
        $loGeneracionLectura = GeneracionLectura::on($laDatosActualizar['DataBaseAlias'])
            ->where('GeneracionFactura', '=', $laDatosActualizar['GeneracionFactura'])
            ->where('Cliente', '=', $laDatosActualizar['Cliente'])
            ->update(["ConsumoFacturado" => $laDatosActualizar['ConsumoFacturado']]);
        
        return $loGeneracionLectura;
    }

    public function TieneConsumo($GeneracionFactura, $Cliente, $DataBaseAlias){
        $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
            ->where('Cliente', '=', $Cliente)
            ->where('GeneracionFactura', '=', $GeneracionFactura)->get();

        $loGeneracionLectura = $loGeneracionLectura[0]->Consumo;

        return $loGeneracionLectura;
    }

    public function Get_LecturaByID_Socio($Cliente, $DataBaseAlias){
        $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
                ->where('Cliente', '=', $Cliente)->get();
    
        return $loGeneracionLectura;
    }
}