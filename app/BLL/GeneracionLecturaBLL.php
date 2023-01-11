<?php
    namespace App\BLL;

    use App\DAL\MedidorDAL;
    use App\DAL\CategoriaDAL;
    use App\DAL\MarcaMedidorDAL;
    use App\DAL\MediaConsumoDAL;
    use App\DAL\CategoriaConsumoDAL;
    use App\DAL\GeneracionLecturaDAL;
    use App\DAL\GeneracionFacturaDAL;
    use App\DAL\MedidorAnormalidadDAL;
    use App\DAL\GeneracionLecturaMovilDAL;
    use App\DAL\ReglaLecturacionDetalleDAL;
    use App\DAL\ModificacionGeneracionLecturaDAL;

    use App\Modelos\MedidorInfo;
    use App\Modelos\TipoConsumo;
    use App\Modelos\GuardarErrores;
    use App\Modelos\ReglaLecturacion;
    use App\Modelos\TipoComportamiento;

    use App\Models\CategoriaConsumo;
    use Illuminate\Support\Facades\Storage;

    // La primera indica el tipo de variable
    // •	l - Local
    // •	g - Global
    // •	p - Private
    // •	t - Parameter

    // La segunda letra indica el tipo de dato.
    // •	c - Character
    // •	n - Numeric
    // •	d - Date
    // •	t - DateTime
    // •	l - Logical
    // •	m - Memo
    // •	a - Array
    // •	o - Object
    // •	x - Indeterminate

class GeneracionLecturaBLL{
        public $gnCliente, $gnGeneracionFactura, $gnMedidorAnormalidad, $gnLecturaAnterior, $gnLecturaActual, $gnMedidor, $gnTipoConsumo, $gnConsumoFacturado, $nErrorAdvertencia, $nError;
        public $gnConsumoActual, $gnMedia, $gnMediaAnterior, $gnCategoria, $gnAjusteConsumo, $gnAjusteMonto, $gnPorcentajeDesviacion, $llswMedidorVolcadoEnLimiteMAX = false, $ID_Clase = 2000;
        public $AplicarPromedio, $DesviacionSignificativa, $InspeccionRequerido, $Facturado, $ValidoLectura, $gnConsumoMinimo = 0, $Regla = 0, $swProcesadoCliente = false , $cMessage;
        public $MedidorInfo, $TipoConsumo, $TipoComportamiento, $ReglaLecturacion, $DataBaseAlias, $gnMedidorAnormalidad2; // TODO : se aumento la variable para la segunda anormalidad - $gnMedidorAnormalidad2

        function __construct()
        {
            $this->MedidorInfo = new MedidorInfo;
            $this->TipoConsumo = new TipoConsumo;
            $this->TipoComportamiento = new TipoComportamiento;
            $this->ReglaLecturacion   = new ReglaLecturacion;
        }

        public function ModificarYValidarLectura($datos){
            try {
                $lnResult = 0;
                $this->nError = 0;
                $lnConsumoMinimo = 0;
                $this->nErrorAdvertencia = 0;
                $this->gnMedidorAnormalidad2 = $datos['tcMedidorAnormalidad2']; // TODO : se inicializa la variable - $gnMedidorAnormalidad2
                
                $Consumo = $datos['tcLecturaActual'] - $datos['tcLecturaAnterior'];
            
                // 1.- INICIALIZAR CAMPOS
                $lnResult = $this->InicializarCampos($datos['tcCliente'], $datos['tcGeneracionLectura'], $datos['tcLecturaActual'],
                    $Consumo, $datos['tcMedidorAnormalidad'], $datos['tcMedidor'], $datos['tcCategoria'], $datos['DataBaseAlias']);

                if ($lnResult != 0) {
                    $this->nError = $this->ID_Clase + $lnResult;
                    return $this->ResultadoModificacionLecturaCliente();
                }

                // TODO : obtener la categoria del cliente y obtener el consumo minimo de la tabla categoria $datos['tcCategoria']
                // enviar el consumo minimo como parametro $lnConsumoMinimo
                $CategoriaDAL    = new CategoriaDAL;
                $lnConsumoMinimo = $CategoriaDAL->GetConsumoMinimo($datos['tcCategoria'], $datos['DataBaseAlias']);

                // 2.- VALIDAR LECTURAS Y CONSUMO
                $lnResult = $this->ValidarLectura($datos['tcCliente'], $datos['tcGeneracionLectura'], 
                    $datos['tcLecturaActual'], $Consumo, $datos['tcMedidorAnormalidad'], $datos['tcMedia'], $lnConsumoMinimo);

                $llSeValida = $this->SeValida($datos['tcMedia'], $Consumo, $datos['tcCategoria']);

                if ($llSeValida == true) {
                    if ($lnResult != 0) {
                        $this->nError = $this->ID_Clase + $lnResult;
                        return $this->ResultadoModificacionLecturaCliente();
                    }
                }else {
                    $lnResult = 0;
                    $this->nError = 0;
                }

                // 3.- COPIAR LECTURAS A MODIFICAIONGENERACIONLECTURA
                if ($datos['llNuevaLectura'] == 'false') {
                    $lnResult = $this->DO_CopiarToModGenLe($datos['tcCliente'], $datos['tcGeneracionLectura']);
                }

                // 4.- APLICAR REGLA DE LECTURACION
                if ($lnResult == 0) {
                    $lnResult = $this->AplicarRegla($llSeValida);
                }

                // // 5.- RESULTADO DE LA LECTURACION VALIDADA
                $this->nError = $this->ID_Clase + $lnResult;
                return $this->ResultadoModificacionLecturaCliente();

            } catch (\Exception $th) {
                return "Error Grave " . $th;
            }
        }

        public function InicializarCampos($tcCliente, $tcGeneracionLectura, $tcLecturaActual, 
                    $Consumo, $tcMedidorAnormalidad, $tcMedidor, $tcCategoria, $DataBaseAlias){
            try {
                // CAMPOS PARA LA TABLA GENERACIONLECTURA
                $this->gnCliente            = $tcCliente;
                $this->gnGeneracionFactura  = $tcGeneracionLectura;
                $this->gnMedidorAnormalidad = $tcMedidorAnormalidad;
                $this->gnLecturaActual      = $tcLecturaActual;
                $this->gnConsumoActual      = $Consumo;
                $this->gnMedia              = 0;
                $this->gnMediaAnterior      = false;

                // CAMPOS PARA LA TABLA GENERACIONLECTURAMOVIL
                $this->gnCategoria             = $tcCategoria;
                $this->gnMedidor               = $tcMedidor;
                $this->gnTipoConsumo           = 0;
                $this->AplicarPromedio         = false;
                $this->gnAjusteConsumo         = 0;
                $this->gnAjusteMonto           = 0;
                $this->gnConsumoFacturado      = 0;
                $this->DesviacionSignificativa = false;
                $this->InspeccionRequerido     = false;
                $this->Facturado               = false;
                $this->ValidoLectura           = false;

                $this->DataBaseAlias           = $DataBaseAlias;
                $texto["ConsumoActual"] = "ConsumoActual".$this->gnConsumoActual;
                Storage::disk('local')->put('Error/error_CA_.txt', $texto);

                return 0;
            } catch (\Exception $th) {
                return 1;
            }
        }

        public function ValidarLectura($tcCliente, $tcGeneracionLectura, 
                $tcLecturaActual, $Consumo, $tcMedidorAnormalidad, $tcMedia, $tnConsumoMinimo){
            $MedidorAnormalidadDAL = new MedidorAnormalidadDAL;
            $GeneracionLecturaDAL = new GeneracionLecturaDAL;
            $lnLecturaAnteriorDAL = $GeneracionLecturaDAL->GetRecDt2($tcGeneracionLectura, $tcCliente, $this->DataBaseAlias);
            
            $lnResult = 0;
            $this->gnMedia = $tcMedia;
            $this->gnLecturaAnterior = $lnLecturaAnteriorDAL[0]->LecturaAnterior;

            // TIPO DE COMPORTAMIENTO
            $this->nError = $this->GetTipoComportamiento($tcCliente, $tcGeneracionLectura, $tcLecturaActual);
            if ($this->nError != 0) {
                return $this->nError; // Error al identificar el comportamiento del medidor
            }

            // VERIFICAR SI ES UNA LECTURA IRREAL
            if ($this->MedidorInfo->MedidorTipoComportamiento == $this->TipoComportamiento->Irreal) {
                $Anormalidad = $MedidorAnormalidadDAL->GetRecDt($tcMedidorAnormalidad, $this->DataBaseAlias);

                if (count($Anormalidad) > 0) {
                    $llSinLecturaPendiente = ($this->TipoConsumo->SinLectura == $Anormalidad[0]->TipoConsumo) &&
                                            ($MedidorAnormalidadDAL->Aplicar_LecturaPendiente == $Anormalidad[0]->Regla);
                                        
                    $llSinLecturaPromedio = ($this->TipoConsumo->SinLectura == $Anormalidad[0]->TipoConsumo) &&
                                            ($MedidorAnormalidadDAL->Aplicar_ConsumoPromedio == $Anormalidad[0]->Regla);
                    
                    if (!$llSinLecturaPendiente && !$llSinLecturaPromedio) {
                        $this->nError = 3;
                        $this->cMessage = 'Consumo IRREAL, Verifique los datos';
                        return $this->nError;
                    }
                }else{
                    $this->nError = 3;
                    $this->cMessage = 'Consumo IRREAL, Verifique los datos';
                    return $this->nError;
                }
            }

            if (($this->gnLecturaAnterior >= 0) && ($tcLecturaActual == 0) && ($tcMedidorAnormalidad > 0)) {
                $lnResult = $this->AnormalidadCorrecta($tcMedidorAnormalidad, $this->TipoConsumo->SinLectura, $this->TipoComportamiento->NoLecturable);

                if ($lnResult == 0) {
                    $this->MedidorInfo->MedidorTipoComportamiento = $this->TipoComportamiento->NoLecturable;
                    $this->MedidorInfo->MedidorTipoConsumo = $this->TipoConsumo->SinLectura;
                    return $lnResult;
                }else {
                    if ($this->MedidorInfo->MedidorTipoConsumo == $this->TipoConsumo->ConsumoNegativo) {
                        $this->gnTipoConsumo = $this->MedidorInfo->MedidorTipoConsumo;
                    }else {
                        $this->gnTipoConsumo = $this->GetTipoConsumo($tcGeneracionLectura, $tcLecturaActual, $tcMedia);
                    }

                    $lnResult = $this->AnormalidadCorrecta($tcMedidorAnormalidad, $this->gnTipoConsumo, $this->MedidorInfo->MedidorTipoComportamiento);
                    if ($lnResult != 0) {
                        $this->cMessage = 'Anormalidad no válida '. $this->gnTipoConsumo .' - '. $this->MedidorInfo->MedidorTipoComportamiento;
                        return $lnResult;
                    }
                    return $lnResult;
                }
            }

            if (($this->MedidorInfo->MedidorTipoConsumo == $this->TipoConsumo->ConsumoNegativo) && ($tcLecturaActual == 0) && ($this->gnLecturaActual > 0)) {
                $this->nErrorAdvertencia = 1;
                $this->cMessage = 'Seleccione Anormalidad Correcta';
            }

            if ($this->MedidorInfo->MedidorTipoConsumo == $this->TipoConsumo->ConsumoNegativo) {
                $this->gnTipoConsumo = $this->MedidorInfo->MedidorTipoConsumo;
            }else {
                $this->gnTipoConsumo = $this->GetTipoConsumo($tcGeneracionLectura, $tcLecturaActual, $tcMedia);
            }

            if ($this->MedidorInfo->MedidorTipoComportamiento == $this->TipoComportamiento->Lecturable) {
                $this->MedidorInfo->MedidorTipoConsumo = $this->gnTipoConsumo;
                $this->MedidorInfo->MedidorConsumo = $this->gnConsumoActual;
            }

            $this->DesviacionSignificativa = (($this->gnTipoConsumo == $this->TipoConsumo->ConsumoBajo) || ($this->gnTipoConsumo == $this->TipoConsumo->ConsumoAlto));

            if ($tcMedidorAnormalidad == 0) {
                if ($this->gnTipoConsumo == $this->TipoConsumo->ConsumoNormal) {
                    $lnResult = 0;
                }else {
                    if (($this->gnLecturaAnterior == 0) && ($this->gnLecturaActual == 0) && ($this->TipoConsumo->ConsumoCero)) {
                        $lnResult = 2;
                        $this->cMessage = 'Consulte con Administrador';
                    }elseif (($this->gnConsumoActual <= $this->gnConsumoMinimo) && ($this->gnConsumoActual >= 0) && ($this->gnMedia <= $this->gnConsumoMinimo)) {
                        $lnResult = 0;
                    }else {
                        $lnResult = 2;
                        $this->gnConsumoActual = $this->MedidorInfo->MedidorConsumo;
                        if ($this->nErrorAdvertencia == 0) {
                            $this->cMessage = 'Seleccione Anormalidad Válida';
                        }
                    }
                }
            }else {
                $this->gnConsumoFacturado = $this->gnMedia;
                $lnResult = $this->EsCasoSinLectura_AplicarPromedio($tcMedidorAnormalidad, $this->gnTipoConsumo, $this->gnLecturaAnterior, 
                    $this->gnLecturaActual, $this->gnConsumoActual, $this->gnMedia, $this->gnConsumoFacturado, $tnConsumoMinimo); // TODO
                
                if($lnResult != 0) {
                    $this->gnConsumoFacturado = $this->gnConsumoActual;
                }
                
                if($lnResult == 3 || $lnResult == 2){
                    $lnResult = $this->AnormalidadCorrecta($tcMedidorAnormalidad, $this->gnTipoConsumo, $this->MedidorInfo->MedidorTipoComportamiento);
                }
            }
            return $lnResult;
        }

        // public function PostValidacion(){
        //     $lnEsInstalacionNueva = GeneracionLectura::esInstalacionNueva($this->gnMedidorAnormalidad, $this->gnCliente, $this->gnCobro);
        //     if($lnEsInstalacionNueva){ //IF INLIST(lnEsInstalacionNueva, 0, 1)
        //         $lnId_MediEst = $ID_Nuevo; //pGlobal.ID_Nuevo
        //         AplicarRegla($lnLectAnt, $lnLectAct, $lnConsumo, $lnMedia, $lnId_MediEst, $lnId_Medidor); //THISFORM.oGenLect.AplicarRegla(lnLectAnt, lnLectAct, lnConsumo, lnMedia, $lnId_MediEst, lnId_Medidor)			
        //         // lcError = THISFORM.oGenLect.GetErrorMsgBy(8) &&Parametro: 8 = APLICAR INSTALACION NUEVA
        //         // SELECT TEMPORAL //TODO
        //         // REPLACE TEMPORAL.LectAnt WITH THISFORM.oGenLect.nLectAnt //TODO
        //         // REPLACE TEMPORAL.LectAct WITH THISFORM.oGenLect.nLectAct //TODO
        //         // REPLACE TEMPORAL.Consumo WITH THISFORM.oGenLect.nConsumo //TODO
        //         // REPLACE TEMPORAL.ConsumoFac WITH THISFORM.oGenLect.nConsumoFac //TODO
        //         // REPLACE TEMPORAL.ID_MediEst WITH $lnId_MediEst && Se colocar de Forma Predeterminada 			
        //         // REPLACE TEMPORAL.Variacion WITH THISFORM.oGenLect.nPorcentajeDesviacion //TODO
        //         // REPLACE TEMPORAL.Error WITH  lcError
        //     }else{
        //         $lnEsCambioMedidor = GeneracionLectura::EsCambioDeMedidor($this->gnMedidorAnormalidad, $this->gnCliente);
        //         SELECT TEMPORAL
        //         IF !EMPTY(lcErrorEstado)
        //             REPLACE TEMPORAL.Error WITH lcErrorEstado
        //         ENDIF
        //         IF INLIST(lnEsCambioMedidor, 0, 1)
        //             THISFORM.oGenLect.AplicarRegla(lnLectAnt, lnLectAct, lnConsumo, lnMedia, $lnId_MediEst, lnId_Medidor)			
        //             lcError = THISFORM.oGenLect.GetErrorMsgBy(9) &&Parametro: 8 = APLICAR INSTALACION NUEVA
        //             SELECT TEMPORAL
        //             REPLACE TEMPORAL.LectAnt WITH THISFORM.oGenLect.nLectAnt
        //             REPLACE TEMPORAL.LectAct WITH THISFORM.oGenLect.nLectAct
        //             REPLACE TEMPORAL.Consumo WITH THISFORM.oGenLect.nConsumo
        //             REPLACE TEMPORAL.ConsumoFac WITH THISFORM.oGenLect.nConsumoFac
        //             *REPLACE TEMPORAL.ID_MediEst WITH $lnId_MediEst && Se colocar de Forma Predeterminada 			
        //             REPLACE TEMPORAL.Variacion WITH THISFORM.oGenLect.nPorcentajeDesviacion
        //             IF(lnEsCambioMedidor = 0)
        //                 REPLACE TEMPORAL.Error WITH  lcError
        //             ENDIF
        //         ELSE
        //             lnEsRegulaBajaTemporal=THISFORM.oGenLect.EsRegularizacionBajaTemporal($lnId_MediEst, lnID_Socio, @lcErrorEstado)
        //             SELECT TEMPORAL
        //             IF !EMPTY(lcErrorEstado)
        //                 REPLACE TEMPORAL.Error WITH lcErrorEstado
        //             ENDIF
        //             IF INLIST(lnEsRegulaBajaTemporal, 0, 1)
        //                 THISFORM.oGenLect.AplicarRegla(lnLectAnt, lnLectAct, lnConsumo, lnMedia, $lnId_MediEst, lnId_Medidor)			
        //                 lcError = THISFORM.oGenLect.GetErrorMsgBy(10) &&Parametro: 8 = APLICAR INSTALACION NUEVA
        //                 SELECT TEMPORAL
        //                 REPLACE TEMPORAL.LectAnt WITH THISFORM.oGenLect.nLectAnt
        //                 REPLACE TEMPORAL.LectAct WITH THISFORM.oGenLect.nLectAct
        //                 REPLACE TEMPORAL.Consumo WITH THISFORM.oGenLect.nConsumo
        //                 REPLACE TEMPORAL.ConsumoFac WITH THISFORM.oGenLect.nConsumoFac
        //                 *REPLACE TEMPORAL.ID_MediEst WITH $lnId_MediEst && Se colocar de Forma Predeterminada 			
        //                 REPLACE TEMPORAL.Variacion WITH THISFORM.oGenLect.nPorcentajeDesviacion
        //                 IF(lnEsRegulaBajaTemporal = 0)
        //                     REPLACE TEMPORAL.Error WITH  lcError
        //                 ENDIF
        //             ELSE
        //                 IF (llSeValida = .F.) 
        //                     lcTipoConsumoNombre = THISFORM.oGenLect.oMedidorInfo.GetTipoConsumo(THISFORM.oGenLect.nTipoConsumo)	
        //                     IF(THISFORM.MostrarConsumoMenorFactorMinimo.Value = .T.)
        //                         lcError = "[Informativo][" + lcTipoConsumoNombre + "]"
        //                     ELSE
        //                         lcError = ""
        //                     ENDIF
        //                     IF(lnValido = 0)
        //                         lcError = ""
        //                     ENDIF 
        //                     SELECT TEMPORAL
        //                     REPLACE TEMPORAL.Error WITH  lcError
        //                 ENDIF
        
        //                 IF (TEMPORAL.CONSUMO <= 0 OR TEMPORAL.CONSUMOFAC = 0 ) AND TEMPORAL.ID_MEDIEST = 0
        //                     THISFORM.oGenLect.AplicarRegla(lnLectAnt, lnLectAct, lnConsumo, lnMedia, $lnId_MediEst, lnId_Medidor)
        //                     SELECT TEMPORAL
        //                     REPLACE TEMPORAL.LectAnt WITH THISFORM.oGenLect.nLectAnt		
        //                     REPLACE TEMPORAL.LectAct WITH THISFORM.oGenLect.nLectAct
        //                     IF THISFORM.oGenLect.nLectAct > 0
        //                         REPLACE TEMPORAL.Consumo WITH THISFORM.oGenLect.nConsumo
        //                     ELSE
        //                         REPLACE TEMPORAL.Consumo WITH IIF(THISFORM.oGenLect.nConsumo < 0, 0, THISFORM.oGenLect.nConsumo) 
        //                     ENDIF 
        //                     &&REPLACE TEMPORAL.Consumo WITH THISFORM.oGenLect.nConsumo					
        //                     REPLACE TEMPORAL.ConsumoFac WITH IIF(THISFORM.oGenLect.nConsumoFac < 0, 0, THISFORM.oGenLect.nConsumoFac) 
                            
        //                     // &&VERIFICAMOS SI EL CONSUMOFAC ES MENOR QUE EL CONSUMOMINIMO}, By: ASF, Fecha: 04-11-2022
        //                     IF USED("CATEGORI")		
        //                         lnArea2 = SELECT()
        //                         SELECT CATEGORI
        //                         SEEK TEMPORAL.ID_CATEG
        //                         IF FOUND()
        //                             IF (CATEGORI.ConsumoMin > 0) AND (TEMPORAL.ConsumoFac < CATEGORI.ConsumoMin)
        //                                 REPLACE TEMPORAL.ConsumoFac WITH CATEGORI.ConsumoMin
        //                             ENDIF 
        //                         ENDIF 
        //                         SELECT(lnArea2) 
        //                     ENDIF 
        //                 ELSE
        //                     THISFORM.oGenLect.AplicarRegla(lnLectAnt, lnLectAct, lnConsumo, lnMedia, $lnId_MediEst, lnId_Medidor)				
        //                     SELECT TEMPORAL
        //                     REPLACE TEMPORAL.LectAct WITH THISFORM.oGenLect.nLectAct
        //                     REPLACE TEMPORAL.Consumo WITH IIF(THISFORM.oGenLect.nConsumo < 0, 0, THISFORM.oGenLect.nConsumo)
        //                     REPLACE TEMPORAL.ConsumoFac WITH IIF(THISFORM.oGenLect.nConsumoFac < 0, lnMedia, THISFORM.oGenLect.nConsumoFac)
                            
        //                     // &&VERIFICAMOS SI EL CONSUMOFAC ES MENOR QUE EL CONSUMOMINIMO}, By: ASF, Fecha: 04-11-2022
        //                     IF USED("CATEGORI")		
        //                         lnArea2 = SELECT()
        //                         SELECT CATEGORI
        //                         SEEK TEMPORAL.ID_CATEG
        //                         IF FOUND()
        //                             IF (CATEGORI.ConsumoMin > 0) AND (TEMPORAL.ConsumoFac < CATEGORI.ConsumoMin)
        //                                 &&SET STEP ON 
        //                                 REPLACE TEMPORAL.ConsumoFac WITH CATEGORI.ConsumoMin
        //                             ENDIF 
        //                         ENDIF
        //                         SELECT(lnArea2) 
        //                     ENDIF 
        //                 ENDIF
        //                 REPLACE TEMPORAL.Variacion WITH THISFORM.oGenLect.nPorcentajeDesviacion
        //             ENDIF
        //         ENDIF
        //     }
        //     IF(THISFORM.MostrarMedidorInfoAlValidar.Value = .T.)
        //         lcMsg = THISFORM.oGenLect.oMedidorInfo.ToString()
        //         MESSAGEBOX( lcMsg, 0, "Aviso")
        //     ENDIF
        //     return 1 //&& Salimos sin restricciones.
        // }

        public function EsCasoSinLectura_AplicarPromedio($tnMedidorAnormalidad, $tnTipoConsumo, $tnLecturaAnterior, $tnLecturaActual, $tnConsumoActual, $tnMedia, $tnConsumoFacturado, $tnConsumoMinimo){

            $lcSQL;
            $lnArea;
            $lnResult = 3;
            $lnMessageError;
            $lnTipoConsumoSistema;

            $lnMedidorAnormalidad = new MedidorAnormalidadDAL;
            $lnMedidorAnormalidad = $lnMedidorAnormalidad->EsCasoSinLectura($tnMedidorAnormalidad, $this->DataBaseAlias);

            if(count($lnMedidorAnormalidad) > 0){
                $this->Regla = $lnMedidorAnormalidad[0]->Regla;
                //$lnRegla = $lnMedidorAnormalidad[0]->Regla;
                $lnTipoConsumoSistema = $lnMedidorAnormalidad[0]->TipoConsumo;

                if(($tnLecturaActual == $tnLecturaAnterior) && ($tnConsumoActual == 0)){
                    if(($lnTipoConsumoSistema == $this->TipoConsumo->SinLectura) && (/*$lnRegla*/$this->Regla == $this->ReglaLecturacion->CONSUMO_PROMEDIO)){
                        if(($tnConsumoMinimo[0]->ConsumoMinimo > 0) && ($tnConsumoFacturado <= $tnConsumoMinimo[0]->ConsumoMinimo) && ($tnMedia <= $tnConsumoMinimo[0]->ConsumoMinimo)){
                            $lnResult = 0;
                        }else if($tnConsumoFacturado <> $tnMedia){
                            $lnResult = 4;
                            $lnMessageError = "ConsumoFacturado Inválido,  tnConsumoFacturado:".$tnConsumoFacturado." tnMedia:".$tnMedia;
                            GuardarErrores::GuardarLog(0, "EsCasoSinLectura_AplicarPromedio()", json_encode($lnMessageError), 'Linea 405', 0);
                        }else{
                            $lnResult = 0;
                        }
                    }else{
                        $lnResult = 2;
                        $lnMessageError = "TipoConsumo no Compatible con la Anormalidad";
                    }
                }else{
                    $lnResult = 2;
                    $lnMessageError = "TipoConsumo no Compatible con la Anormalidad";
                }
            }else{
                $lnResult = 3;
                $lnMessageError = "No Existe Anormalidad";
            }
            return $lnResult;
        }

        public function AnormalidadCorrecta($tnMedidorAnormalidad, $tnTipoConsumo, $tnTipoComportamiento){
            $loReglaLecturacionDetalle = new ReglaLecturacionDetalleDAL;
            $MedidorAnormalidad        = new MedidorAnormalidadDAL;
    
            $lnResult = 0;
            $loMedidorAnormalidad = $MedidorAnormalidad->GetRecDt($tnMedidorAnormalidad, $this->DataBaseAlias);
    
            if (count($loMedidorAnormalidad) > 0) { // TODO && (dtMediEsta.Select(filtro).Length > 0)


                $this->Regla = $loMedidorAnormalidad[0]->Regla;
    
                if ($tnTipoComportamiento == $this->TipoComportamiento->FinDeCiclo) {
                    if ($this->Regla != $this->ReglaLecturacion->FIN_DE_CICLO) {
                        $this->cMessage = 'Anormalidad seleccionada no tiene Regla: Fin de Ciclo';
                        return 3;
                    }
                }

                if (($tnTipoComportamiento == $this->TipoComportamiento->VolcadoAntesDeLimite) || ($tnTipoComportamiento == $this->TipoComportamiento->VolcadoEnLimite)) {
                    if ($this->Regla != $this->ReglaLecturacion->MEDIDOR_VOLCADO) {
                        $this->cMessage = 'Anormalidad seleccionada no tiene Regla: Medidor Volcado';
                        return 4;
                    }
                }
                $ReglaLec = $loReglaLecturacionDetalle->GetIDBy($this->Regla, $this->DataBaseAlias);
                // GuardarErrores::GuardarLog(0, "AnormalidadCorrecta()", $ReglaLec, 'Linea 327', 0);
                
                if (count($ReglaLec) > 0) { //VERIFICAMOS SI LA REGLA ES APLICABLE AL TIPOCONSUMO
                    $llAplicable = $loReglaLecturacionDetalle->ReglaAplicable($tnTipoConsumo);
    
                    if ($llAplicable == true) {
                        $lnResult = 0; //Existe Anormalidad y su Tipo consumo es Valido
                        $this->cMessage = "";
                    }else {
                        $lnResult = 2;
                        $this->cMessage = "No Hay Regla Aplicable";
                    }
                }else {
                    $lnResult = 2;
                    $this->cMessage = "No Hay Registros";
                }
            }else {
                $lnResult = 2; // Existe Anormalidad pero  posiblemente su tipo consumo no es correcto
                $this->cMessage = "MediEsta[No Hay Registros o TipoConsumo no es Compatible con la Anormalidad]";
            }
            return $lnResult;
        }

        public function GetTipoConsumo($GeneracionFactura, $LecturaActual, $Media){
            $CategoriaConsumoDAL  = new CategoriaConsumoDAL;
            $CategoriaDAL         = new CategoriaDAL;
            $GeneracionFacturaDAL = new GeneracionFacturaDAL;
            $GeneracionFacturaDAL = $GeneracionFacturaDAL->GetRecDt($GeneracionFactura, $this->DataBaseAlias);

            if (count($GeneracionFacturaDAL) > 0) {
                $MesCobro = $GeneracionFacturaDAL[0]->Cobro;
            }

            $lnConsumoHistorico = $Media;

            if ($this->gnCategoria > 0) {
                $ConsumoMinimo = $CategoriaDAL->GetConsumoMinimo($this->gnCategoria, $this->DataBaseAlias);
                $this->gnConsumoMinimo = $ConsumoMinimo[0]->ConsumoMinimo;
            }

            $LimiteConsumoDAL = $CategoriaConsumoDAL->Get_LimitesConsumo($this->gnCategoria, $lnConsumoHistorico, $this->DataBaseAlias);
            $LimiteConsumoMaximo = $LimiteConsumoDAL['Maximo'];
            $LimiteConsumoMinimo = $LimiteConsumoDAL['Minimo'];

            $this->gnLecturaActual = $LecturaActual;
            $this->gnConsumoActual = $this->gnLecturaActual - $this->gnLecturaAnterior;
            $this->gnMedia = $lnConsumoHistorico;

            if ($lnConsumoHistorico == 0) {
                $this->gnPorcentajeDesviacion = 0;
            }else {
                $this->gnPorcentajeDesviacion = (($this->gnConsumoActual / $lnConsumoHistorico) - 1) * 100;
            }

            // IDENTIFICACION DEL TIPO DE CONSUMO
            if (($this->gnLecturaAnterior == 0) && ($this->gnLecturaActual == 0)) {
                return $this->TipoConsumo->ConsumoCero;
            }

            if ($this->gnConsumoActual == 0) {
                return $this->TipoConsumo->ConsumoCero;
            }

            if ($LecturaActual <= 0) {
                return $this->TipoConsumo->SinLectura;
            }

            if ($this->gnConsumoActual < 0) {
                return $this->TipoConsumo->ConsumoNegativo;
            }

            if ($this->gnConsumoActual < $LimiteConsumoMinimo) {
                return $this->TipoConsumo->ConsumoBajo;
            }elseif ($this->gnConsumoActual > $LimiteConsumoMaximo){
                return $this->TipoConsumo->ConsumoAlto;
            }else{
                return $this->TipoConsumo->ConsumoNormal;
            }
        }

        public function SeValida($tcMedia, $Consumo, $tcCategoria){
            $MediaConsumoDAL = new MediaConsumoDAL;
            $lnValorRef = 15;
            $llSeValida = true;
    
            if ($tcCategoria > 0) {
                $lnResult = CategoriaConsumo::on('mysql_LMCoopaguas')
                    ->where('Categoria', '=', $tcCategoria)
                    ->where('Inicio', '=', '0')->get();
    
                if (count($lnResult) > 0) {
                    $lnValorRef = $lnResult[0]->Fin;
                }
            }
            
            if ($Consumo > 0) {
                $llSeValida = $MediaConsumoDAL->SeValida($tcMedia, $Consumo, $lnValorRef);
            }else {
                $llSeValida = true;
            }
    
            return $llSeValida;
        }

        public function GetTipoComportamiento($tcCliente, $tcGeneracionLectura, $tcLecturaActual){
            $GeneracionLecturaDAL = new GeneracionLecturaDAL;

            $lnResult       = 0;
            $lnFinMedidor   = 0;
            $lnQmaxMes      = 0;
            $lnFactorAnterior = 0;
            $lnFactorActual = 0;
            $lnDifFactores  = 0;
            $lnFactorProximidadIzquierda = 0;
            $lnFactorProximidadDerecha   = 0;
            $lnFactorLimiteConsumoNegativo = 0;
            $lnFactorLimiteConsumoPositivo = 0;

            try {
                $lnMedidorDAL = $GeneracionLecturaDAL->ObtenerMedidor($this->gnMedidor, $this->DataBaseAlias);
                $lnDiametroMedidorDAL = $GeneracionLecturaDAL->ObtenerDiametroMedidor($lnMedidorDAL[0]->DiametroMedidor, $this->DataBaseAlias);
                $lnLecturaAnteriorDAL = $GeneracionLecturaDAL->GetRecDt2($tcGeneracionLectura, $tcCliente, $this->DataBaseAlias);
                $this->gnLecturaAnterior = $lnLecturaAnteriorDAL[0]->LecturaAnterior;

                if (count($lnMedidorDAL) > 0) {
                    if (is_numeric($lnMedidorDAL[0]->FinMedidor)) {
                        $lnFinMedidor = $lnMedidorDAL[0]->FinMedidor;
                    }else{
                        $lnResult = -12; // Registro no es Numero
                        $this->cMessage = 'Medidor FinMedidor no es Numero';
                    }
                }else{
                    $lnResult = -11; // Error No Existe Registro
                    $this->cMessage = 'No hay Registros para el Medidor ' . $this->gnMedidor;
                }

                if ($lnResult < 0) {
                    return $lnResult;
                }

                if (count($lnDiametroMedidorDAL) > 0) {
                    $lnQmaxMes = $lnDiametroMedidorDAL[0]->CantidadMaximaMes;
                }else{
                    $lnResult = -11;
                    $this->cMessage = 'Diametro Acometida, No hay registros';
                }

                if ($lnResult < 0) {
                    return $lnResult;
                }
    
                if ($lnQmaxMes == 0) {
                    $this->cMessage = 'Diametro Acometida, No hay registros';
                    return -1;
                }
    
                if ($tcLecturaActual < 0) { // Valor Asigando a $this->lecturaActual en Get_TipoConsumo()
                    return $lnResult; // Caso improbable dado que nunca se colocara en el dispositivo un valor NEGATIVO
                }

                $lnFactorAnterior = $lnFinMedidor - $this->gnLecturaAnterior;
                $lnFactorActual = $lnFinMedidor - $tcLecturaActual;
                $lnDifFactores = $lnFactorAnterior - $lnFactorActual;
                $lnFactorProximidadIzquierda = ($lnDifFactores / $lnFinMedidor) * 100;
                $lnFactorProximidadDerecha = 100 - abs($lnFactorProximidadIzquierda);
                $lnFactorLimiteConsumoPositivo = (($lnQmaxMes / $lnFinMedidor) * 100);
                $lnFactorLimiteConsumoNegativo = 100 - $lnFactorLimiteConsumoPositivo;

                $this->MedidorInfo->MedidorConsumo = $tcLecturaActual - $this->gnLecturaAnterior;
                $this->MedidorInfo->MedidorDifFactores = $lnDifFactores;
                $this->MedidorInfo->MedidorFactorActual = $lnFactorActual;
                $this->MedidorInfo->MedidorFactorAnterior = $lnFactorAnterior;
                $this->MedidorInfo->MedidorFactorProximidadIzquierda = $lnFactorProximidadIzquierda;
                $this->MedidorInfo->MedidorFactorProximidadDerecha = $lnFactorProximidadDerecha;
                $this->MedidorInfo->MedidorFactorLimiteConsumoPositivo = $lnFactorLimiteConsumoPositivo;
                $this->MedidorInfo->MedidorFactorLimiteConsumoNegativo = $lnFactorLimiteConsumoNegativo;
                $this->MedidorInfo->MedidorFinMedidor = $lnFinMedidor;
                $this->MedidorInfo->MedidorLecturaActual = $tcLecturaActual;
                $this->MedidorInfo->MedidorLecturaAnterior = $this->gnLecturaAnterior;
                $this->MedidorInfo->MedidorTipoConsumo = $this->TipoConsumo->ConsumoNegativo;
                $this->MedidorInfo->MedidorCantidadMaximaMes = $lnQmaxMes;

                $lnResult = 0;

                // MEDIDOR FIN DE CICLO
                if ((abs($lnFactorProximidadIzquierda) >= $lnFactorLimiteConsumoNegativo) &&
                        ($lnFactorProximidadDerecha <= $lnFactorLimiteConsumoPositivo) &&
                        ($lnFactorProximidadIzquierda < 0) && ($lnFactorProximidadDerecha >= 0)) {
                    $this->MedidorInfo->MedidorTipoComportamiento = $this->TipoComportamiento->FinDeCiclo; // Es Medidor Fin de Ciclo
                    $this->MedidorInfo->MedidorConsumo = $lnFinMedidor - $this->gnLecturaAnterior + $tcLecturaActual + 1;

                }// MEDIDOR VOLCADO EN LÍMITE
                elseif(($lnFactorProximidadIzquierda >= $lnFactorLimiteConsumoNegativo) &&
                        ($lnFactorProximidadDerecha <= $lnFactorLimiteConsumoPositivo) &&
                        ($lnFactorProximidadIzquierda > 0) && ($lnFactorProximidadDerecha >= 0)){
                    $this->MedidorInfo->MedidorTipoComportamiento = $this->TipoComportamiento->VolcadoEnLimite; // Es Medidor Volcado en Limite
                    $this->MedidorInfo->MedidorConsumo = $lnFinMedidor - $tcLecturaActual + $this->gnLecturaAnterior + 1;
                }// MEDIDOR VOLCADO ANTES DEL LÍMITE
                elseif ((abs($lnFactorProximidadIzquierda) <= $lnFactorLimiteConsumoPositivo) &&
                        ($lnFactorProximidadDerecha >= $lnFactorLimiteConsumoNegativo) &&
                        ($lnFactorProximidadIzquierda < 0) && ($lnFactorProximidadDerecha >= 0)) {
                    $this->MedidorInfo->MedidorTipoComportamiento = $this->TipoComportamiento->VolcadoAntesDeLimite;  // Es Medidor Volcado Antes del Limite
                    $this->MedidorInfo->MedidorConsumo =  $this->gnLecturaAnterior - $tcLecturaActual;
                }// MEDIDOR CON CONSUMO REAL
                elseif (($lnFactorProximidadIzquierda <= $lnFactorLimiteConsumoPositivo) &&
                        ($lnFactorProximidadDerecha >= $lnFactorLimiteConsumoNegativo) &&
                        ($lnFactorProximidadIzquierda >= 0) && ($lnFactorProximidadDerecha >= 0)) {

                    $this->MedidorInfo->MedidorTipoComportamiento = $this->TipoComportamiento->Lecturable; // Medidor con consumo REAL(LECTURABLE)
                    $this->MedidorInfo->MedidorConsumo =  $tcLecturaActual - $this->gnLecturaAnterior;
                    $this->MedidorInfo->MedidorTipoConsumo = $this->TipoConsumo->ConsumoNormal; // Paracialmente
                }// MEDIDOR CON CONSUMO DESCONOCIDO IRREAL
                else {
                    $this->MedidorInfo->MedidorTipoComportamiento = $this->TipoComportamiento->Irreal; // Medidor con consumo IRREAL!!!
                    $this->MedidorInfo->MedidorTipoConsumo = $this->TipoConsumo->SinLectura;
                }

            } catch (\Exception $th) {
                $lnResult = -1;
                $this->cMessage = 'Error al procesar [Tipo de Comportamiento]';
                $lcParametros = "tcCliente = ".$tcCliente.", tcGeneracionLectura = ".$tcGeneracionLectura.", tcLecturaActual = ".$tcLecturaActual;
                GuardarErrores::GuardarErrores($th, $this->ID_Clase, "GetTipoComportamiento(".$lcParametros.")");
            }
            return $lnResult;
        }

        public function ResultadoModificacionLecturaCliente(){
            $Resultado = [];

            $MarcaMedidorDAL = new MarcaMedidorDAL;

            $MedidorDAL = new MedidorDAL;
            $MedidorDAL = $MedidorDAL->GetRecDt($this->gnMedidor, $this->DataBaseAlias);

            $CategoriaDAL = new CategoriaDAL;
            $CategoriaDAL = $CategoriaDAL->GetRecDt($this->gnCategoria, $this->DataBaseAlias);

            $MedidorAnormalidadDAL = new MedidorAnormalidadDAL;
            $MedidorAnormalidadDAL = $MedidorAnormalidadDAL->GetRecDt($this->gnMedidorAnormalidad, $this->DataBaseAlias);

            $NombreAnormalidad = "N/E";
            $NombreCategoria   = "N/E";
            $MedidorMarca      = "N/E";
            $MedidorSerie      = "N/E";
            $MedidorNumero     = "N/E";

            if (count($MedidorAnormalidadDAL) > 0) {
                $NombreAnormalidad = $MedidorAnormalidadDAL[0]->NombreAnormalidad;
                $this->Regla = $MedidorAnormalidadDAL[0]->Regla;
            }

            if (count($CategoriaDAL) > 0) {
                $NombreCategoria = $CategoriaDAL[0]->NombreCategoria;
            }

            if (count($MedidorDAL) > 0) {
                $MarcaMedidorDAL = $MarcaMedidorDAL->GetRecDt($MedidorDAL[0]->MarcaMedidor, $this->DataBaseAlias);
    
                if (count($MarcaMedidorDAL) > 0) {
                    $MedidorMarca = $MarcaMedidorDAL[0]->NombreMarcaMedidor;
                }else {
                    $MedidorMarca = "N/E";
                }
                $MedidorSerie = $MedidorDAL[0]->NumeroSerie;
                $MedidorNumero = $MedidorDAL[0]->Numero;
            }

            $Resultado = ["Error" => $this->nError,
                "TipoConsumo"          => $this->GetTipoConsumoStr(),
                "Media"                => $this->gnMedia,
                "DesviacionSignificativa" => $this->DesviacionSignificativa,
                "ConsumoActual"        => $this->gnConsumoActual,
                "LecturaAnterior"      => $this->gnLecturaAnterior,
                "LecturaActual"        => $this->gnLecturaActual,
                "Anormalidad"          => $NombreAnormalidad,
                "Categoria"            => $NombreCategoria,
                "MedidorMarca"         => $MedidorMarca,
                "MedidorSerie"         => $MedidorSerie,
                "MedidorNumero"        => $MedidorNumero,
                "PorcentajeDesviacion" => $this->gnPorcentajeDesviacion,
                "ProcesadoCliente"     => $this->swProcesadoCliente,
                "Regla"                => $this->Regla,
                "FinMedidor"           => $MedidorDAL[0]->FinMedidor,
                "MedidorVolcado"       => $this->llswMedidorVolcadoEnLimiteMAX];

            return $Resultado;
        }

        public function GetTipoConsumoStr(){
            $lnResult = "Ninguno";
            switch ($this->gnTipoConsumo) {
                case 1: $lnResult = "Consumo Normal"; break;
                case 2: $lnResult = "Consumo Bajo"; break;
                case 3: $lnResult = "Consumo Alto"; break;
                case 4: $lnResult = "Consumo Cero"; break;
                case 5: $lnResult = "Consumo Negativo"; break;
                case 6: $lnResult = "Sin Lectura"; break;
                case 7: $lnResult = "Consumo Estimado"; break;
            }
            return $lnResult;
        }

        public function DO_CopiarToModGenLe($tcCliente, $tcGeneracionLectura){
            $MedidorAnormalidadDAL = new MedidorAnormalidadDAL;
            $ModificacionGeneracionLecturaDAL = new ModificacionGeneracionLecturaDAL;
    
            $GeneracionLecturaDAL = new GeneracionLecturaDAL;
                $GeneracionLectura = $GeneracionLecturaDAL->GetRecDt2($tcGeneracionLectura, $tcCliente, $this->DataBaseAlias);
    
            $GeneracionLecturaMovilDAL = new GeneracionLecturaMovilDAL;
                $DatosCliente = $GeneracionLecturaMovilDAL->GetRecDt($tcGeneracionLectura, $tcCliente, $this->DataBaseAlias);
    
            if (count($GeneracionLectura) == 0) {
                return 9;
            }
    
            if (count($DatosCliente) == 0) {
                $MedidorAnormalidad = $GeneracionLectura[0]->MedidorAnormalidad;
                $Auxiliar = $MedidorAnormalidadDAL->GetRecDt($MedidorAnormalidad, $this->DataBaseAlias);
                $TipoConsumoAnterior = 0;
    
                if (count($Auxiliar) > 0) {
                    $TipoConsumoAnterior = $Auxiliar[0]->TipoConsumo;
                }

                $GeneracionLecturaMovilDAL->Insert(0, $tcGeneracionLectura, $tcCliente, $this->gnCategoria, $this->gnMedidor, $TipoConsumoAnterior, 
                                    $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, $this->DesviacionSignificativa,
                                    $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2 /*$MedidorAnormalidad*/, $this->DataBaseAlias); // TODO : se hace uso de la variable
    
                $DatosCliente = $GeneracionLecturaMovilDAL->GetRecDt($tcGeneracionLectura, $tcCliente, $this->DataBaseAlias);
            }
            $ModificacionGeneracionLectura = [];
    
            $ModificacionGeneracionLectura = [
                // Datos ModificacionGeneracionLectura
                'GeneracionFactura' => $tcGeneracionLectura,
                'Cliente'           => $tcCliente,
                'Glosa'             => 'Prueba',
    
                // Datos GeneracionLectura
                'CodigoUbicacion'   => $GeneracionLectura[0]->CodigoUbicacion,
                'LecturaAnterior'   => $GeneracionLectura[0]->LecturaAnterior,
                'LecturaActual'     => $GeneracionLectura[0]->LecturaActual,
                'Consumo'           => $GeneracionLectura[0]->Consumo,
                'MedidorAnormalidad'=> $GeneracionLectura[0]->MedidorAnormalidad,
                'MediaAnterior'     => $GeneracionLectura[0]->MediaAnterior,
                'Cobro'             => $GeneracionLectura[0]->Cobro,
                'Media'             => $GeneracionLectura[0]->Media,
                'ConsumoFacturado'  => $GeneracionLectura[0]->ConsumoFacturado,
                'ConsumoDebito'     => $GeneracionLectura[0]->ConsumoDebito,
    
                // Datos GeneracionLecturaMovil
                'FechaAnterior'          => $DatosCliente[0]->Fecha,
                'HoraAnterior'           => $DatosCliente[0]->Hora,
                'Categoria'              => $DatosCliente[0]->Categoria,
                'Medidor'                => $DatosCliente[0]->Medidor,
                'TipoConsumo'            => $DatosCliente[0]->TipoConsumo,
                'MedidorAnormalidad2'    => $DatosCliente[0]->MedidorAnormalidad2,
                'AplicoMedia'            => $DatosCliente[0]->AplicoMedia,
                'ConsuFactu'             => $DatosCliente[0]->ConsuFactu,
                'AjusteConsumo'          => $DatosCliente[0]->AjusteConsumo,
                'AjusteMonto'            => $DatosCliente[0]->AjusteMonto,
                'DesviacionSignificativa'=> $DatosCliente[0]->DesviacionSignificativa,
                'InspeccionRequerida'    => $DatosCliente[0]->InspeccionRequerido,
                'Facturado'              => $DatosCliente[0]->Facturado,
                'ValidoLectura'          => $DatosCliente[0]->ValidoLectura,

                'DataBaseAlias'          => $this->DataBaseAlias,
            ];
    
            $ModificacionGeneracionLecturaDAL->Insertar($ModificacionGeneracionLectura);
    
            return 0;
        }

        public function verificarConsumoFacturado(){
            if(($this->gnConsumoMinimo > 0) && (/*$this->gnConsumoFacturado*/$this->gnConsumoActual < $this->gnConsumoMinimo)){
                $this->gnConsumoFacturado = $this->gnConsumoMinimo;
            }else{
                $this->gnConsumoFacturado = $this->gnConsumoActual;
            }
        }

        public function AplicarRegla($llSeValida){
            $MedidorAnormalidadDAL = new MedidorAnormalidadDAL;
            $this->verificarConsumoFacturado();

            $lnResult = 0;
            $TipoReglaAplicar = $MedidorAnormalidadDAL->Get_TipoReglaAAplicar($this->gnMedidorAnormalidad, $this->DataBaseAlias);
            if ($llSeValida == false) {
                $lnResult = $this->Aplicar_LecturaActual();
            }else{
                if (($this->gnMedidorAnormalidad == 0) && (($this->gnTipoConsumo == $this->TipoConsumo->ConsumoNormal) || ($this->gnConsumoActual <= $this->gnConsumoMinimo))) {
                    return $this->Aplicar_ConsumoNormal();
                }

                switch ($TipoReglaAplicar) {
                    case 1: $lnResult = $this->Aplicar_LecturaPendiente(); break;
                    case 2: $lnResult = $this->Aplicar_LecturaActual(); break;
                    case 3: $lnResult = $this->Aplicar_FinDeCiclo(); break;
                    case 4: $lnResult = $this->Aplicar_ConsumoPromedio(); break;
                    case 5: $lnResult = $this->Aplicar_MedidorVolcado(); break;
                    case 6: $lnResult = $this->Aplicar_ConsumoAsignado(); break;
                    case 7: $lnResult = $this->Aplicar_AjusteLectura(); break;
                    case 8: $lnResult = $this->Aplicar_InstalacionNueva(); break;
                }
            }
            return $lnResult;
        }

        public function Aplicar_LecturaActual(){
            try {
                $MedidorAnormalidadDAL     = new MedidorAnormalidadDAL;
                $GeneracionLecturaDAL      = new GeneracionLecturaDAL;
                // $TipoConsumo               = new TipoConsumo;
                $GeneracionLecturaMovilDAL = new GeneracionLecturaMovilDAL;
                
                $this->ValidoLectura = true;
                $TipoReglaAplicar = $MedidorAnormalidadDAL->Aplicar_LecturaActual;
                // TODO : verificar que el consumo facturado sea menor al minimo debe guardar con el consumo minino Categoria.ConsumoMinimo
                $GeneracionLecturaDAL->actualizarLecturaDAL($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnLecturaActual, 
                    $this->gnConsumoActual, $this->gnMedidorAnormalidad, $this->DataBaseAlias, $this->ValidoLectura, $this->gnConsumoFacturado); // TODO: $this->gnConsumoFacturado

                // Campos para la table GeneracionLecturaMovil
                $this->AplicarPromedio   = false;
                $this->gnAjusteConsumo    = 0;
                $this->gnAjusteMonto      = 0;
                //$this->gnConsumoFacturado = 0; // TODO : guardar segun calculo
                $this->DesviacionSignificativa = (($this->gnTipoConsumo == $this->TipoConsumo->ConsumoBajo) || ($this->gnTipoConsumo == $this->TipoConsumo->ConsumoAlto));
                $MedidorAnormalidad = $MedidorAnormalidadDAL->GetRecDt($this->gnMedidorAnormalidad, $this->DataBaseAlias);
                $this->InspeccionRequerido = $MedidorAnormalidad[0]->Inspeccion;
                $this->Facturado = false;
                
                $existe = $GeneracionLecturaMovilDAL->Existe($this->gnGeneracionFactura, $this->gnCliente, $this->DataBaseAlias);

                if (count($existe) > 0) {
                    $GeneracionLecturaMovilDAL->Update($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias);  // TODO : se hace uso de la variable
                }else {
                    $GeneracionLecturaMovilDAL->Insert($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria,
                        $this->gnMedidor, $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias);  // TODO : se hace uso de la variable
                }
                
                return 0;
            } catch (\Exception $th) {
                GuardarErrores::GuardarErrores($th, 0, "GeneracionLecturaBLL->Aplicar_LecturaActual");
                return 5;
            }
        }

        public function Aplicar_ConsumoNormal(){
            try {
                $MedidorAnormalidad        = new MedidorAnormalidadDAL;
                $GeneracionLecturaDAL      = new GeneracionLecturaDAL;
                $GeneracionLecturaMovilDAL = new GeneracionLecturaMovilDAL;
    
                $TipoReglaAplicar = $MedidorAnormalidad->Aplicar_ConsumoNormal;
                $this->ValidoLectura = true;
                $GeneracionLecturaDAL->actualizarLecturaDAL($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnLecturaActual, 
                    $this->gnConsumoActual, $this->gnMedidorAnormalidad, $this->DataBaseAlias, $this->ValidoLectura, $this->gnConsumoFacturado); // TODO:  $this->gnConsumoFacturado
    
                // Campos para la tabla GeneracionLecturaMovil
                $this->AplicarPromedio   = false;
                $this->gnAjusteConsumo    = 0;
                $this->gnAjusteMonto      = 0;
                //$this->gnConsumoFacturado = 0;
                $this->DesviacionSignificativa = (($this->gnTipoConsumo == $this->TipoConsumo->ConsumoBajo) || ($this->gnTipoConsumo == $this->TipoConsumo->ConsumoAlto));
                $this->InspeccionRequerido = false;
                $this->Facturado = false;
    
                $existe = $GeneracionLecturaMovilDAL->Existe($this->gnGeneracionFactura, $this->gnCliente, $this->DataBaseAlias);
                if (count($existe) > 0) {
                    $GeneracionLecturaMovilDAL->Update($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente,$this->gnCategoria, 
                        $this->gnMedidor, $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                
                }else {
                    $GeneracionLecturaMovilDAL->Insert($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, 
                        $this->gnMedidor, $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo,$this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                }
                
                return 0;
    
            } catch (\Exception $th) {
                GuardarErrores::GuardarErrores($th, 0, "GeneracionLecturaBLL->Applicar_ConsumoNormal");
                return 3;
            }
        }

        public function Aplicar_LecturaPendiente(){
            try {
                $MedidorAnormalidad        = new MedidorAnormalidadDAL;
                $GeneracionLecturaDAL      = new GeneracionLecturaDAL;
                $GeneracionLecturaMovilDAL = new GeneracionLecturaMovilDAL;
    
                $TipoReglaAplicar = $MedidorAnormalidad->Aplicar_LecturaPendiente;
                $this->gnLecturaActual = 0; // llevar seguimiento
                $this->gnConsumoActual = 0; // llevar seguimiento
                $GeneracionLecturaDAL->actualizarLecturaDAL($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnLecturaActual, 
                    $this->gnConsumoActual, $this->gnMedidorAnormalidad, $this->DataBaseAlias, $this->ValidoLectura, $this->gnConsumoFacturado); // TODO: $this->gnConsumoFacturado
    
                $existe = $GeneracionLecturaMovilDAL->Existe($this->gnGeneracionFactura, $this->gnCliente, $this->DataBaseAlias);
                if (count($existe) > 0) {
                    $GeneracionLecturaMovilDAL->Update($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                
                }else {
                    $GeneracionLecturaMovilDAL->Insert($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                }
    
                return 0;
    
            } catch (\Exception $th) {
                GuardarErrores::GuardarErrores($th, 0, "GeneracionLecturaBLL->Aplicar_LecturaPendiente");
                return 4;
            }
        }

        public function Aplicar_FinDeCiclo(){
            try {
                $MedidorAnormalidadDAL     = new MedidorAnormalidadDAL;
                $GeneracionLecturaDAL      = new GeneracionLecturaDAL;
                $GeneracionLecturaMovilDAL = new GeneracionLecturaMovilDAL;
    
                $TipoReglaAplicar = $MedidorAnormalidadDAL->Aplicar_FinDeCiclo;
                $this->gnTipoConsumo = $this->CalcularConsumoXFinDeCiclo($this->gnCliente, $this->gnGeneracionFactura, $this->gnLecturaActual);
                $this->ValidoLectura = true;
                $GeneracionLecturaDAL->actualizarLecturaDAL($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnLecturaActual, 
                    $this->gnConsumoActual, $this->gnMedidorAnormalidad, $this->DataBaseAlias, $this->ValidoLectura, $this->gnConsumoFacturado); // TODO: $this->gnConsumoFacturado
    
                // Campos para la table GeneracionLecturaMovil
                $this->AplicarPromedio   = false;
                $this->gnAjusteConsumo    = 0;
                $this->gnAjusteMonto      = 0;
                //$this->gnConsumoFacturado = 0;
                $this->DesviacionSignificativa = (($this->gnTipoConsumo == $this->TipoConsumo->ConsumoBajo) || ($this->gnTipoConsumo == $this->TipoConsumo->ConsumoAlto));
                $MedidorAnormalidad = $MedidorAnormalidadDAL->GetRecDt($this->gnMedidorAnormalidad, $this->DataBaseAlias);
                $this->InspeccionRequerido = $MedidorAnormalidad[0]->Inspeccion;
                $this->Facturado = false;

                $existe = $GeneracionLecturaMovilDAL->Existe($this->gnGeneracionFactura, $this->gnCliente, $this->DataBaseAlias);
                if (count($existe) > 0) {
                    $GeneracionLecturaMovilDAL->Update($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                
                }else {
                    $GeneracionLecturaMovilDAL->Insert($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                }
    
                return 0;
    
            } catch (\Exception $th) {
                GuardarErrores::GuardarErrores($th, 0, "GeneracionLecturaBLL->Aplicar_FinDeCiclo");
                return 6;
            }
        }

        public function Aplicar_ConsumoPromedio(){
            try {
                if($this->gnMedia > $this->gnConsumoMinimo){ // 20-11-2022
                    $this->gnConsumoFacturado = $this->gnMedia;
                }

                $MedidorAnormalidadDAL     = new MedidorAnormalidadDAL;
                $GeneracionLecturaDAL      = new GeneracionLecturaDAL;
                $GeneracionLecturaMovilDAL = new GeneracionLecturaMovilDAL;
    
                $this->gnLecturaActual = $this->gnLecturaAnterior;
                $this->gnConsumoActual = $this->gnMedia;
                $this->gnMediaAnterior = true;
    
                $TipoReglaAplicar = $MedidorAnormalidadDAL->Aplicar_ConsumoPromedio;
                $this->ValidoLectura = false;
                $GeneracionLecturaDAL->actualizarLecturaDAL($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnLecturaActual, 
                    $this->gnConsumoActual, $this->gnMedidorAnormalidad, $this->DataBaseAlias, $this->ValidoLectura, $this->gnConsumoFacturado); // TODO: $this->gnConsumoFacturado
    
                // Campos para la table GeneracionLecturaMovil
                $this->AplicarPromedio   = true;
                $this->gnAjusteConsumo    = 0;
                $this->gnAjusteMonto      = 0;
                //$this->gnConsumoFacturado = $this->gnMedia;
                $this->DesviacionSignificativa = false;
                $this->InspeccionRequerido = true;
                $this->Facturado = true;
                
                $existe = $GeneracionLecturaMovilDAL->Existe($this->gnGeneracionFactura, $this->gnCliente, $this->DataBaseAlias);
                if (count($existe) > 0) {
                    $GeneracionLecturaMovilDAL->Update($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                
                }else {
                    $GeneracionLecturaMovilDAL->Insert($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                }
    
                return 0;
    
            } catch (\Exception $th) {
                GuardarErrores::GuardarErrores($th, 0, "GeneracionLecturaBLL->Aplicar_ConsumoPromedio");
                return 7;
            }
        }

        public function Aplicar_MedidorVolcado(){
            try {
                $GeneracionLecturaMovilDAL = new GeneracionLecturaMovilDAL;
                $MedidorAnormalidadDAL     = new MedidorAnormalidadDAL;
                $GeneracionLecturaDAL      = new GeneracionLecturaDAL;
    
                $TipoReglaAplicar = $MedidorAnormalidadDAL->Aplicar_MedidorVolcado;
    
                $this->gnTipoConsumo = $this->CalcularConsumoXMedidorVolcado($this->gnCliente, $this->gnGeneracionFactura, $this->gnLecturaActual);
                $this->ValidoLectura = true;
                $GeneracionLecturaDAL->actualizarLecturaDAL($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnLecturaActual, 
                    $this->gnConsumoActual, $this->gnMedidorAnormalidad, $this->DataBaseAlias, $this->ValidoLectura, $this->gnConsumoFacturado); // TODO: $this->gnConsumoFacturado
    
                // Campos para la table GeneracionLecturaMovil
                $this->AplicarPromedio   = false;
                $this->gnAjusteConsumo    = 0;
                $this->gnAjusteMonto      = 0;
                //$this->gnConsumoFacturado = 0;
                $this->DesviacionSignificativa = (($this->gnTipoConsumo == $this->TipoConsumo->ConsumoBajo) || ($this->gnTipoConsumo == $this->TipoConsumo->ConsumoAlto));
                $MedidorAnormalidad = $MedidorAnormalidadDAL->GetRecDt($this->gnMedidorAnormalidad, $this->DataBaseAlias);
                $this->InspeccionRequerido = $MedidorAnormalidad[0]->Inspeccion;
                $this->Facturado = false;
                
                $existe = $GeneracionLecturaMovilDAL->Existe($this->gnGeneracionFactura, $this->gnCliente, $this->DataBaseAlias);
                if (count($existe) > 0) {
                    $GeneracionLecturaMovilDAL->Update($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                
                }else {
                    $GeneracionLecturaMovilDAL->Insert($TipoReglaAplicar, $this->gnGeneracionFactura, $this->gnCliente, $this->gnCategoria, $this->gnMedidor, 
                        $this->gnTipoConsumo, $this->AplicarPromedio, $this->gnConsumoFacturado, $this->gnAjusteConsumo, $this->gnAjusteMonto, 
                        $this->DesviacionSignificativa, $this->InspeccionRequerido, $this->Facturado, $this->ValidoLectura, $this->gnMedidorAnormalidad2, $this->DataBaseAlias); // TODO : se hace uso de la variable
                }
    
                return 0;
    
            } catch (\Exception $th) {
                GuardarErrores::GuardarErrores($th, 0, "GeneracionLecturaBLL->Aplicar_MedidorVolcado");
                return 8;
            }
        }

        public function Aplicar_ConsumoAsignado(){
            return 0;
        }

        public function Aplicar_AjusteLectura(){
            return 0;
        }

        public function Aplicar_InstalacionNueva(){
            return 0;
        }

        public function CalcularConsumoXFinDeCiclo($Cliente, $GeneracionFactura, $LecturaActual){
            $MedidorDAL     = new MedidorDAL;
            $CategoriaConsumoDAL = new CategoriaConsumoDAL;

            $lnResult = $this->TipoConsumo->SinLectura;
            $LecturaMaxima = 0;

            $Medidor = $MedidorDAL->GetRecDt($this->gnMedidor, $this->DataBaseAlias);
            if (($Medidor != null) && (count($Medidor) > 0)) {
                $LecturaMaxima = $Medidor[0]->FinMedidor;
            }else {
                return $lnResult;
            }

            $this->gnConsumoActual = ($LecturaMaxima - $this->gnLecturaAnterior) + $this->gnLecturaActual + 1;

            if ($this->gnMedia == 0) {
                $this->gnPorcentajeDesviacion = 0;
            }else {
                $this->gnPorcentajeDesviacion = (($this->gnConsumoActual / $this->gnMedia) - 1) * 100;
            }

            $CategoriaConsumoDAL = $CategoriaConsumoDAL->Get_LimitesConsumo($this->gnCategoria, $this->gnMedia, $this->DataBaseAlias);
            $LimiteConsumoMinimo = $CategoriaConsumoDAL['Minimo'];
            $LimiteConsumoMaximo = $CategoriaConsumoDAL['Maximo'];

            if ($this->gnConsumoActual < $LimiteConsumoMinimo) {
                $lnResult = $this->TipoConsumo->ConsumoBajo;
            }elseif ($this->gnConsumoActual > $LimiteConsumoMaximo) {
                $lnResult = $this->TipoConsumo->ConsumoAlto;
            }else {
                $lnResult = $this->TipoConsumo->ConsumoNormal;
            }

            return $lnResult;
        }

        public function CalcularConsumoXMedidorVolcado($Cliente, $GeneracionFactura, $LecturaActual){
            $MedidorDAL     = new MedidorDAL;
            $CategoriaConsumoDAL = new CategoriaConsumoDAL;
    
            $lnResult = $this->TipoConsumo->SinLectura;
            $LecturaMaxima = 0;
            $this->gnLecturaActual = $LecturaActual;
    
            $Medidor = $MedidorDAL->GetRecDt($this->gnMedidor, $this->DataBaseAlias);
            if (($Medidor != null) && (count($Medidor) > 0)) {
                $LecturaMaxima = $Medidor[0]->FinMedidor;
            }else {
                return $lnResult;
            }
    
            $CategoriaConsumoDAL = $CategoriaConsumoDAL->Get_LimitesConsumo($this->gnCategoria, $this->gnMedia, $this->DataBaseAlias);
            $LimiteConsumoMinimo = $CategoriaConsumoDAL['Minimo'];
            $LimiteConsumoMaximo = $CategoriaConsumoDAL['Maximo'];
    
            $this->gnConsumoActual = $this->gnLecturaAnterior - $this->gnLecturaActual;
    
            if ($this->gnConsumoActual < 0) {
                $this->gnConsumoActual = ($LecturaMaxima - $this->gnLecturaActual) + $this->gnLecturaAnterior + 1;
            }elseif ($this->gnConsumoActual > 0) {
                $this->gnConsumoActual = $this->gnLecturaAnterior - $this->gnLecturaActual;
            }
    
            if ($this->gnMedia == 0) {
                $this->gnPorcentajeDesviacion = 0;
            }else {
                $this->gnPorcentajeDesviacion = (($this->gnConsumoActual / $this->gnMedia) - 1) * 100;
            }
    
            if ($this->gnConsumoActual < $LimiteConsumoMinimo) {
                $lnResult = $this->TipoConsumo->ConsumoBajo;
            }elseif ($this->gnConsumoActual > $LimiteConsumoMaximo) {
                $lnResult = $this->TipoConsumo->ConsumoAlto;
            }else {
                $lnResult = $this->TipoConsumo->ConsumoNormal;
            }
    
            return $lnResult;
        }

        public function ActualizarLecturaMovilSinMedidor($GeneracionFactura, $Cliente, $Categoria, $TipoConsumo, $MedidorAnormalidad2, $DataBaseAlias){
            $GeneracionLecturaMovilDAL = new GeneracionLecturaMovilDAL;

            $TipoReglaAplicar = 1;
            $Medidor = 0;

            $existe = $GeneracionLecturaMovilDAL->Existe($GeneracionFactura, $Cliente, $DataBaseAlias);
                if (count($existe) > 0) {
                    $GeneracionLecturaMovilDAL->Update($TipoReglaAplicar, $GeneracionFactura, $Cliente, $Categoria, $Medidor, 
                        $TipoConsumo, false, 0, 0, 0, false, false, false, true, $MedidorAnormalidad2, $DataBaseAlias);
                
                }else {
                    $GeneracionLecturaMovilDAL->Insert($TipoReglaAplicar, $GeneracionFactura, $Cliente, $Categoria, $Medidor, 
                        $TipoConsumo, false, 0, 0, 0, false, false, false, true, $MedidorAnormalidad2, $DataBaseAlias);
                }
        }
    }
?>