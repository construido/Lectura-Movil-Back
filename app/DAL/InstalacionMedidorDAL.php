<?php

namespace App\DAL;

use App\Models\InstalacionMedidor;
use DB;

class InstalacionMedidorDAL
{
    public function instalacionNueva($FechaInicio, $FechaFin, $ZonaRuta, $DataBaseAlias){
        $loInstalacionNueva = InstalacionMedidor::on($DataBaseAlias)
            ->select('InstalacionMedidor', 'Cliente', 'CodigoUbicacion', 'LecturaInicial as LecturaAnterior', 'Medidor',
                    'FechaInstalacion', 'FechaActivacion', 'FechaTrabajo', 'FechaFacturacion', 'NuevaInstalacion')
            ->where('CodigoUbicacion', 'like', '%'.$ZonaRuta.'%')
            ->where('NuevaInstalacion', '=', 1)
            ->where('Medidor', '>', 0)
            ->where('Estado', '=', 2)
            ->orderBy('Cliente', 'DESC')
            ->get();

            // ldFechaFin = tdFechaLect - pGlobal.DiasInstal  // obtener de la tabla ParametroLectura - DiasDeInstalacion
            // ldFechaIni = tdFechaLect - 31
            // lcCobroFin =  oUtil.AAMMANT(tcCobro)

            // loInstalacionNueva = " SELECT I.ID_INSTALA, I.ID_Socio, I.COD_SOCIO, I.LectIni AS LectAnt, I.Id_Medidor," +;
            //          "		 I.F_INSTALA, F_ACTIVA, I.F_TRABAJO, I.F_FACTURAR, I.NuevaIns " +;
            //         "   FROM INSTALAM I " +;
            //         "  WHERE I.F_Facturar >= " + oMySQL.Fox2SQL(ldFechaIni) +;
            //         "    AND I.F_Facturar <= " + oMySQL.Fox2SQL(ldFechaFin) +;
            //            "    AND I.Es_Instala = 2" +;
            //            "    AND I.NuevaIns = " + oMySQL.Fox2SQL(.T.) +;
            //            "    AND SUBSTR(I.Cod_Socio,1,4)= " + oMySQL.FOX2SQL(tcZonaRuta) +;
            //            "    AND I.Id_Medidor > 0" +;
            //            "  ORDER BY I.Cliente DESC"  // obtener de la tabla InstalacionMedidor

        return $loInstalacionNueva;
    }
}