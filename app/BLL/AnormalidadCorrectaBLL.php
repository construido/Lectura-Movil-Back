<?php

namespace App\BLL;

use App\DAL\InstalacionMedidorDAL;
use App\DAL\GeneracionFacturaDAL;
use App\DAL\ParametroLecturaDAL;

use Exception;

class AnormalidadCorrectaBLL
{
    public $cError = "";

    public function EsInstalacionNueva($GeneracionFactura, $MedidorAnormalidad, $Cliente, $tcCobro, $DataBaseAlias){
        $lnResult;
        $llEsNuevaIns = false;
        $lnCantidadLecturas = -1;
        $this->cError = "";

        try {
            $llTieneUnicoInstalam = $this->TieneUnicoInstalam($GeneracionFactura, $Cliente, $DataBaseAlias);
            $lnCantidadLecturas = $this->CantidadLecturas($tcCobro, $Cliente);
            if($lnCantidadLecturas >= 0){
                $llEsNuevaIns = ($lnCantidadLecturas <= $MesesNuevo); // obtener desde ParametroLectura - MesesNuevo
                if(!$llEsNuevaIns) $llEsNuevaIns = $llTieneUnicoInstalam;  
            }else{
                $this->cError = "Error al Consultar Cantidad";
            }
            
            if($ID_Nuevo > 0){ // obtener desde ParametroLectura - AnormalidadNuevo > 0
                if($ID_Nuevo == $MedidorAnormalidad){ // obtener desde ParametroLectura - AnormalidadNuevo > 0
                    $lnResult = 0;
                    // THIS.ErrorMsg = "[Valido][Instalaci�n Nueva]"
                    if(!$llEsNuevaIns){
                        $lnResult = 1;
                        $this->cError = "[Error][No Tiene Instalaci�n Nueva el Asocciado]";
                    }
                }else{
                    // &&Verificamos si no esta en la lista de los cambios en _SOCIMEDI
                    if($llEsNuevaIns){
                        $lnResult = 1;
                        $this->cError = "[Error][Tiene Instalaci�n Nueva el Asocciado]";
                    }else{
                        $lnResult = 2; //&&Ignorar en los siguientes invocaciones.
                    }
                }
            }else{
                $lnResult = 3; //&&Ignorar en los siguientes invocaciones.
                $this->cError = "[Informativo] Instalaci�n Nueva no tiene Valor en ParaLect";
            }
        } catch (Exception $th) {
            $lcLog = "  ProcedureInitial: GenLect.EsInstalacionNueva() ";
            $this->cError = "Error Try/Catch";
            // oError.Guardar($th, $lcLog)
        }

        return $lnResult;
    }

    public function TieneUnicoInstalam($GeneracionFactura, $Cliente, $DataBaseAlias){
        $llResult = false;

        $loParametroLectura = new ParametroLecturaDAL;
        $loParametroLectura = $loParametroLectura->GetAlldt(1, $DataBaseAlias);

        $loGeneracionFactura = GeneracionFacturaDAL::GetRecDt($GeneracionFactura, $DataBaseAlias);
        $FechaInicio = $loGeneracionFactura[0]->FechaGeneracionLectura - $loParametroLectura[0]->DiasDeInstalacion; // obtener de la tabla ParametroLectura - DiasDeInstalacion
        $FechaFin = $loGeneracionFactura[0]->FechaGeneracionLectura - 31;
        $zona = $loGeneracionFactura[0]->Zona < 10 ? '0'.$loGeneracionFactura[0]->Zona : $loGeneracionFactura[0]->Zona;
        $ruta = $loGeneracionFactura[0]->Ruta < 10 ? '0'.$loGeneracionFactura[0]->Ruta : $loGeneracionFactura[0]->Ruta;
        $ZonaRuta = $zona . $ruta;

        try {
            $loInstalacionNueva = new InstalacionMedidorDAL;
            $loInstalacionNueva = $loInstalacionNueva->instalacionNueva($FechaInicio, $FechaFin, $ZonaRuta, $DataBaseAlias);
            
            if(count($loInstalacionNueva) > 0){ // obtener de la tabla InstalacionMedidor
                $llResult = ($loInstalacionNueva[0]->NuevaInstalacion == 1); // obtener de la tabla InstalacionMedidor - cINSTALAM.NuevaInstalacion = 1
            }
        } catch (Exception $th) {
            $lcLog = "  ProcedureInitial: AnormalidadCorrectaBLL.TieneUnicoInstalam()";
            // oError.Guardar($th, $lcLog);
        }

        return $llResult;
    }

    public function AAMMANT($tcAAMM){
        $lcValor;
        // $lnAno = VAL(SUBSTR(tcAAMM,1,4)) // 2023-01 - separa el año del mes - hacer lo mismo en PHP
        // $lnMes = VAL(SUBSTR(tcAAMM,6,2)) // 2023-01 - separa el año del mes - hacer lo mismo en PHP

        if($lnMes == 1){
            // $lcValor = STR(lnAno-1,4)+'-'+'12' 
        }else{
            // $lcValor = STR(lnAno  ,4)+'-'+STR(lnMes-1,2) 
        }
        
        // return STRTRAN(lcValor,' ','0')
    }

    public function CantidadLecturas($tcCobro, $Cliente){
            $lnResult = 0;

            try {
                // $loHistoricoFactura = "SELECT ID_SOCIO, Count(*) AS Cantidad "+;
                //         "  FROM _HISTLECT "+;
                //         " WHERE ID_SOCIO = " + oMySQL.Fox2SQL(Cliente) +;
                //         "   AND COBRO <= " + oMySQL.Fox2SQL(tcCobro) +;
                //         " GROUP BY ID_SOCIO"
                // oMySQL.EjecutarCursor(lcSQL, "curNuevaInsta", THIS.DataSession) // obtener datos de la tabla HistoricoFactura

                if(count($loHistoricoFactura)){
                    $lnResult = $loHistoricoFactura[0]->Cantidad; // obtener datos de la tabla HistoricoFactura - Cantidad
                }
            } catch (Exception $th) {
                $lnResult = -1;
                $lcLog ="   ProcedureInitial: GenLect.CantidadLecturas()";
                // oError.Guardar($th, $lcLog)
            }
            
            return $lnResult;
    }

    public function EsCambioDeMedidor($MedidorAnormalidad, $Cliente){
        $lnResult = 0;
        $this->cError = "";

        if($ID_Cambio > 0){ // obtener desde ParametroLectura - AnormalidadCambioMedidor > 0

        //     ldFechaFin = tdFechaLect - pGlobal.DiasInstal // obtener de la tabla ParametroLectura - DiasDeInstalacion
        //     ldFechaIni = tdFechaLect - 31
        //     lcSQL = " SELECT I.ID_SOCIMED, I.ID_Socio, I.COD_SOCIO, I.LectIni AS LectAnt, I.F_SociMed, " +;
        //     oMySQL.Fox2SQL(ldFechaIni) + " AS FechaAct, I.F_Trabajo, I.F_Facturar " +;
        //   "   FROM SOCIMEDI I " +;
        //   "  WHERE I.F_Facturar >= " + oMySQL.Fox2SQL(ldFechaIni) +;
        //   "    AND I.F_Facturar <= " + oMySQL.Fox2SQL(ldFechaFin) +;
        //   "    AND I.Es_SociMed = 2" +;
        //   "    AND SUBSTR(I.Cod_Socio,1,4) = " + oMySQL.FOX2SQL(tcZonaRuta) +;
        //   "  ORDER BY I.Cliente DESC"
        //     oMySQL.Ejecutar(lcSQL, "_SOCIMEDI", THIS.DataSession) // obtener de la tabla InstalacionMedidor

            if($ID_Cambio == $MedidorAnormalidad){ // obtener desde ParametroLectura - AnormalidadCambioMedidor > 0
                $lnResult = 0;
                $this->cError = "[Valido][Cambio de Medidor]";

                if(count($loClienteMedidor) == 0){
                    $lnResult = 1;
                    $this->cError = "[Error][No Tiene Cambio de Medidor el Asocciado]";
                }
            }else{
                // &&Verificamos si no esta en la lista de los cambios en _SOCIMEDI
                if(count($loClienteMedidor)){
                    $lnResult = 1;
                    $this->cError = "[Error][Tiene Cambio de Medidor el Asocciado]";
                }else{
                    $lnResult = 2; // &&Ignorar en los siguientes invocaciones.
                }
            }
        }else{
            $lnResult = 3; // &&Ignorar en los siguientes invocaciones.
            $this->cError = "[Informativo] Cambio de Medidor no tiene Valor en ParaLect";
        }

        return $lnResult;
    }

    public function EsRegularizacionBajaTemporal($MedidorAnormalidad, $Cliente){
        $lnResult = 0;
        $this->cError = "";
        
        if($ID_Regula > 0){ // obtener de la tabla ParametroLectura - AnormalidadRegularizacionBajaTemporal

            // ldFechaFin = tdFechaLect - pGlobal.DiasInstal // obtener de la tabla ParametroLectura - DiasDeInstalacion
            // ldFechaIni = tdFechaLect - 31
            //     $loInstalacionMedidor = " SELECT I.ID_INSTALA, I.ID_Socio, I.COD_SOCIO, I.LectIni AS LectAnt, I.Id_Medidor," +;
            //     "		 I.F_INSTALA, F_ACTIVA, I.F_TRABAJO, I.F_FACTURAR, I.NuevaIns " +;
            //    "   FROM INSTALAM I " +;
            //    "  WHERE I.F_Facturar >= " + oMySQL.Fox2SQL(ldFechaIni) +;
            //    "    AND I.F_Facturar <= " + oMySQL.Fox2SQL(ldFechaFin) +;
            //       "    AND I.Es_Instala = 2" +;
            //       "    AND I.TIPOINSTAL = 1" +;
            //       "    AND I.NuevaIns = " + oMySQL.Fox2SQL(.F.) +;
            //       "    AND SUBSTR(I.Cod_Socio,1,4) = " + oMySQL.FOX2SQL(tcZonaRuta) +;
            //       "    AND I.Id_Medidor = 0" +
            //        " AND I.Cliente = $Cliente;
            //       "  ORDER BY I.COD_SOCIO"
            //     *oError.GuardarLog("_INSTALAM2", lcSQL)
            //     oMySQL.Ejecutar(lcSQL, "_INSTALAM2", THIS.DataSession) // obtener de la tabla InstalacionMedidor

            if($ID_Regula == $MedidorAnormalidad){ // obtener de la tabla ParametroLectura - AnormalidadRegularizacionBajaTemporal
                $lnResult = 0;
                $this->cError = "[Valido][Regularizaci�n Baja Temporal]";

                if(count($loInstalacionMedidor) == 0){
                    $lnResult = 1;
                    $this->cError = "[Error][No Tiene Regularizaci�n Baja Temporal el Asocciado]";
                }
            }else{
                // &&Verificamos si no esta en la lista de los cambios en _SOCIMEDI
                if(count($loInstalacionMedidor) > 0){
                    $lnResult = 1;
                    $this->cError = "[Error][Tiene Regularizaci�n Baja Temporal el Asocciado";
                }else{
                    $lnResult = 2; // &&Ignorar en los siguientes invocaciones.
                }
            }
        }else{
            $lnResult = 4; // &&Ignorar en los siguientes invocaciones.
            $this->cError = "[Informativo] Regularizacion x Baja Temporal no existe en ParaLect ";
        }

        return $lnResult;
    }

    public function GetErrorMsgBy($tnRegla){
        // FUNCTION GetErrorMsgBy(tnRegla AS Integer) AS String
        //     LOCAL lcTipoConsumoNombre, lcError 
        //     lcTipoConsumoNombre = ""
        //     lcError = ""

        //     IF(tnRegla == THIS.oReglaLectura.INSTALACION_NUEVA)
        //         lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
        //         lcError = "[V][" + lcTipoConsumoNombre + "] [Instalaci�n Nueva]"
        //     ELSE
        //         IF(tnRegla == THIS.oReglaLectura.CAMBIO_DE_MEDIDOR)
        //             lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
        //             lcError = "[V][" + lcTipoConsumoNombre + "] [Cambio de Medidor]"
        //         ELSE
        //             IF(tnRegla == THIS.oReglaLectura.REGULARIZACION_BAJA_TEMPORAL)
        //                 lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
        //                 lcError = "[V][" + lcTipoConsumoNombre + "] [Regularizaci�n Baja Temporal]"
        //             ELSE
        //                 lcError = THIS.ErrorMsg
        //             ENDIF 
        //         ENDIF 
        //     ENDIF
        //     RETURN lcError

        // ENDFUNC
    }
}