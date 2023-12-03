*********************************************************
* Clase.........: GENLECT
* Descripción...: Descripción de GENLECT 
* Fecha.........: 26-05-2018
* Diseñador.....: Ing.Corvera
* MigradorInicial.: Ing. Rene Villalpando
* Encargado.....: Ing. Alfonzo Salgado Flores
* CopyRight.....: Syscoop Solution 1995-2018  
*********************************************************
DEFINE CLASS GenLect  AS CustomSYSCOOP
	
	&&OBJETOS    
	oReglaLectura = .NULL.
    oMedidorInfo = .NULL.
    oTipoConsumo = .NULL.
	oTipoComportamiento = .NULL.
	oAnorLect = .NULL.
	oAjusLect = .NULL.
	oReglaSolEx = .NULL.
	oMediaConsumo = .NULL. &&Add: 06-05-2019, By: Ing. Alfonzo Salgado Flores, Nota:Requerimientos Coopaguas..
	oEstadistica = .NULL. &&Add: 12-08-2019, By: Ing. Alfonzo Salgado Flores, Nota: Requerimiento para Solucio de uso continuo de anormalidades

	&&MODELOS
    mFactura = .NULL.
    
    &&Propiedades de Creacion Cursores Bases
    nID_GenFactActual = 0
    lCrearCursorInstalacionesNuevas = .T.
    lCrearCursorCambioMedidores = .T.
    lCrearCursorRegularizacionBajaTemporal = .T.

	lPorcLect_Usuario = .F.
	lValidarMinimo = .F.
	lVerAnormalidad2 = .T.
	lMenorQueMinimo = .F. && Para TEMPORAL.MqM
	nPorcLECT = 0.5
	curActivos = ""
	curLecLec  = ""
	lcZonaRuta = ""
	ReglaNombre = ""
	AnormalidadNombre = ""
	AnormalidadID = 0
	
	&&Variable de Calculo para Lecturas y Consumos
	nRegla = 0
	nTipoConsumo = 1
	cTipoConsumoNombre = ""
	nLectAnt  = 0
	nLectAct  = 0
	nConsumo  = 0
	nConsumoMinimo = 0
	nConsumoFac = 0
	nMedia = 0
	nId_Medidor = 0000000
	nFinMedidor = 000000000
	nPorcentajeDesviacion = 0.00
	nDesviacionSignificativa = 0.00

	&&Variables para Mostrar o no Mostrar Ciertos Tipos de Consumo o otros casos
	*!* Mostrar Consumo < Factor Minimo?
	lMostrarConsumoMenorFactorMinimo = .T.
	*!*Variable para Colocar o no Colocar el Estado de Instalacion nueva en ID_MediEst
	lInstalacionNuevaEnEstado = .F.

	*!*Variables para Controlar Validacion de la Planilla
	lErrorPendientes = .F.
	lErrorConsumoNegativos = .F.

	*********************************************************
	* Método........: InitDatos
	* Descripción...: Descripión de InitDatos
	* Fecha.........: 25-08-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE InitDatos()
        SET DATASESSION TO THIS.DataSession &&Importante...
        THIS.oReglaLectura = oPrograma.NewClass("ReglaLectura", "oReglaLectura")
        THIS.oMedidorInfo = oPrograma.NewClass("MedidorInfo", "oMedidorInfo")
        THIS.oTipoConsumo = oPrograma.NewClass("TipoConsumo", "oTipoConsumo")
        THIS.oTipoComportamiento = oPrograma.NewClass("TipoComportamiento", "oTipoComportamiento")

        THIS.oAnorLect = oPrograma.NewClass("AnorLect", "oAnorLect")
        THIS.oAjusLect = oPrograma.NewClass("AjusLect", "oAjusLect")
        THIS.oReglaSolEx = oPrograma.NewClass("ReglaSolEx", "oReglaSolEx")
        THIS.oMediaConsumo = oPrograma.NewClass("MediaConsumo", "oMediaConsumo")

        THIS.oReglaLectura.SetDataSession(THIS.DataSession)
        THIS.oMedidorInfo.SetDataSession(THIS.DataSession)
        THIS.oTipoConsumo.SetDataSession(THIS.DataSession)
        THIS.oTipoComportamiento.SetDataSession(THIS.DataSession)
        THIS.oAnorLect.SetDataSession(THIS.DataSession)
        THIS.oAjusLect.SetDataSession(THIS.DataSession)
        THIS.oReglaSolEx.SetDataSession(THIS.DataSession)
        THIS.oMediaConsumo.SetDataSession(THIS.DataSession)

        THIS.oEstadistica =  oPrograma.NewClass("Estadisticas", "oEstadistica")
        THIS.oEstadistica.SetDataSession(THIS.DataSession)
        SET DATASESSION TO THIS.DataSession &&Importante...
    ENDPROC

    *********************************************************
	* Método........: InitDatos
	* Descripción...: Descripión de InitDatos
	* Fecha.........: 15-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE InitVariables()
        SET DATASESSION TO THIS.DataSession &&Importante...
        THIS.lCrearCursorInstalacionesNuevas = .T.
        THIS.lCrearCursorCambioMedidores = .T.
        THIS.lCrearCursorRegularizacionBajaTemporal = .T.
    ENDPROC

	FUNCTION TieneFactura(tnId_Socio AS Integer, tcCobro AS String) AS Boolean
		LOCAL lnArea, llResult
		lnArea = SELECT()
		llResult = .F.
		lcSQL = "SELECT STR(F.Id_Socio) + F.Cobro AS SOCIOMES "+;
				"  FROM viewFactura F "+;
				" WHERE STR(F.Id_Socio) + F.Cobro = "+ oMySQL.Fox2SQL(STR(tnId_Socio) + tcCobro)
				
		oMySQL.EjecutarCursor(lcSQL, "curTieneFac", THIS.DataSession)
	    llResult = RECCOUNT("curTieneFac") > 0
	   	IF llResult
			SELECT TEMPORAL
			REPLACE ID_Factura WITH curTieneFac.ID_Factura
		ENDIF
		USE IN SELECT("curTieneFac")
		SELECT(lnArea)
		RETURN llResult
	ENDFUNC

	*********************************************************
	* Método........: ObtenerFacturaVista
	* Descripción...: Descripión de ObtenerFacturaVista
	* Fecha.........: 24-05-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE ObtenerFacturaVista(tcCobro AS String, tcZonaRuta AS String, tdF_GenLect)
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		
		IF EMPTY(THIS.curActivos)
			THIS.ObtenerListaLectura(tcZonaRuta, .F., tdF_GenLect)
		ENDIF
		lcFechaAnterior = oMySQL.FOX2SQL(pGlobal.Fecha-365)
		TRY
			WAIT WINDOW "[PROCESO] Obteniendo Vista de Facturas" NOWAIT 
			lcListaClientesID = "AND F.ID_SOCIO  IN (SELECT ID_SOCIO FROM " + THIS.curActivos + ")"
		    lcSQL = " SELECT F.ID_Socio, F.Cobro " +;
		    		"   FROM FACTURA F " +;
		    		" WHERE SUBSTR(F.Cobro, 1, 7) = " + oMySQL.Fox2SQL(tcCobro) +;
		    		"  	AND SUBSTR(F.Cod_Socio, 1, 4) = " + oMySQL.Fox2SQL(tcZonaRuta) +;
		    		" 	AND F.Es_Factura <> 3 " +;
		    		"   AND F.F_Emision > "+lcFechaAnterior +;
		    		" " + lcListaClientesID 

		    oMySQL.Ejecutar(lcSQL, "viewFactura",THIS.DataSession)
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.ObtenerFacturaVista()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	*********************************************************
	* Método........: ObtenerListaLectura
	* Descripción...: Descripión de CrearTemporal
	* Fecha.........: 23-05-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE ObtenerListaLectura(tcZonaRuta AS String, tlConMedidor AS Boolean, tdF_GenLect AS Date)
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, lcMedidor, lcLookTabla, ldFechaFin
		TRY
			IF(tlConMedidor)
				lcMedidor = " AND ID_Medidor > 0 "
			ELSE
				lcMedidor = ""
			ENDIF
			ldFechaFin = tdF_GenLect - pGlobal.DiasInstal
			WAIT WINDOW "[PROCESO] Obteniendo Lista de Asociados" NOWAIT 			
			&&fecha: 15-11-2018Para la Empresa que lectura Adelantado,
			&&		 las condiciones de obtencion de socios es diferente.
			&& ' ' As Error >> LEN  se hizo mas grande para que quepa los mensajes de errores
			&&Add: 30-11-2020, By: ASF, Nota: "   AND S.F_Facturar <= " + ldFechaFin, se agrego para mejorar que no traiga los asociados que no deben.
			lcSQL = "SELECT 0000000000 AS ID_GenFact, S.ID_SOCIO, S.ID_Persona, S.COD_SOCIO, ' ' AS Nombre, " +;
					"		0000000000 AS LectAnt, 0000000000 AS LectAct, 0000000000 AS LectValida," +;
					"       0000000000 AS Consumo, 0000000000 AS ConsumoFac, ROUND(S.Consumo,0) As Media, " +;
					" 		0000 AS Id_MediEst, 000000.00 AS Variacion, " +;
					"		'                                                                                                   ' AS Error, " +;
					" "  +  oMySQL.Fox2SQL(.F.) + " AS Media_Ant, '        '  AS Cobro ," +;
					" 		000000000 AS ConsumoDeb, S.Id_Categ, S.ID_Medidor, 0000 AS ID_MediEs2, "+oMySQL.Fox2SQL(.F.) +" AS MqM, "+;
					"		'        ' AS HORA, 0000000000 AS AnorLect, 0000000000 AS AjusLect,   " +;
					"		0000000000 AS CantAnor, 0000000000 AS ID_ESMODA, 0000000000 AS ID_ESMODA2, 0000000.00 AS IndiceUso " +;
					"  FROM SOCIOS S" +;
					" WHERE S.ES_SOCIO = 1 " +;
					"   AND SUBSTR(S.Cod_Socio, 1, 4) = " + oMySQL.Fox2SQL(tcZonaRuta) +;
					" " + lcMedidor +;
					"   AND (S.F_Facturar ) <= " + oMySQL.Fox2SQL(ldFechaFin) 

			
			THIS.curActivos = oMySQL.CrearTemporal(lcSQL)
			strtofile(LCSQL, oError.PathLOGs + "\curActivos.TXT")
			lcLookTabla = THIS.curActivos
			IF(oMySQL.Tipo = 0)
				USE (&lcLookTabla) IN 0 SHARED
			ENDIF
			&&oMySQL.Ejecutar(lcSQL, "_SO_", THIS.DataSession) && unidad de TEST.
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.CrearTemporal()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	*********************************************************
	* Método........: [MAIN] ObtenerLecturas
	* Descripción...: Descripión de ObtenerLecturas
	* Fecha.........: 01-01-2014
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE ObtenerLecturas(tcCobro AS String, tdF_GenLect AS Date, tcZonaRuta AS String,;
							  tlConMedidor AS Boolean, tlInstalacionNuevaEnEstado AS Boolean)
	&&BEGIN
		LOCAL lnArea, lcCobroAnt, lcListaClientesID, lcLookTabla
		LOCAL loEx AS Exception, lcLog AS String
		LOCAL ldFechaIni, ldFechaFin, lcRecTotal, lcRecNo
		LOCAL lmNuevo, lmNuevoMED, lnFila
		lnArea = SELECT()		
		THIS.lInstalacionNuevaEnEstado = tlInstalacionNuevaEnEstado
		TRY
			lnFila = 0
			IF NOT USED("CATECONS")
			   	oMySQL.GetTablaIndexada("CATECONS", "CATECONS", "*", THIS.DataSession)
			ENDIF
			IF NOT USED("CATEGORI")
			   	oMySQL.GetTablaIndexada("CATEGORI", "CATEGORI", "*", THIS.DataSession)
			ENDIF

			THIS.ObtenerListaLectura(tcZonaRuta, tlConMedidor, tdF_GenLect)
			THIS.ObtenerFacturaVista(tcCobro, tcZonaRuta, tdF_GenLect)
			******* Lecturas Anteriores **********
			lcCobroAnt = oUtil.AAMMANT(tcCobro)
			ldFechaIni = tdF_GenLect - 31
			ldFechaFin = tdF_GenLect - pGlobal.DiasInstal
		 	
		 	lcSQL = "SELECT "
		 	*IF VERSION(2) = 2
		 	*	WAIT WINDOW "FECHA ANTERIOR = " + DTOC(ldFechaIni)
		 	*ENDIF
	    	lcSQL = " SELECT L.ID_Socio, L.LectACT, L.Consumo, L.LectANT,  G.F_GENLECT," +;
		   			" 		 IIF(L.LectACT = 0, "+ oMySQL.Fox2SQL(.T.) +", " + oMySQL.Fox2SQL(.F.) + ") AS Media_Ant " +;
		   			"   FROM GENLECT L, GENFACT G " +;
		   			"  WHERE L.ID_GenFact = G.ID_GenFact " +;
		    		"    AND G.Cobro = " + oMySQL.Fox2SQL(lcCobroAnt)+ ;
		    		"    AND SUBSTR(L.Cod_Socio, 1, 4) = " + oMySQL.Fox2SQL(tcZonaRuta)
		    WAIT WINDOW "[PROCESO] Obteniendo Lecturas de Planillas de Lecturacion" NOWAIT 
		    STRTOFILE(lcSQL, oError.PathLOGs + "\cLecLec.txt")
		   	oMySQL.Ejecutar(lcSQL, "cLecLec", THIS.DataSession)
			INDEX ON ID_Socio TAG ID_Socio ADDITIVE
			IF RECCOUNT("cLecLec") > 0
				ldFechaIni = cLecLec.F_GenLect + 1
			ENDIF 
			
			THIS.curLecLec = oMySQL.CrearTemporal(lcSQL)
			
			&&Solucion Parche TEMPORAL: 24-07-2018
			lcLookTabla = THIS.curLecLec
			IF(oMySQL.Tipo = 0)
				USE (&lcLookTabla) IN 0 SHARED
			ENDIF

			lcListaClientesID = "AND F.ID_SOCIO  IN (SELECT ID_SOCIO FROM " + THIS.curActivos + ")"
		    lcSQL = " SELECT F.ID_Socio, F.LectACT, F.Consumo , F.LectANT, " +;
		    		" 		 IIF(F.LectACT = 0, "+ oMySQL.Fox2SQL(.T.) +", " + oMySQL.Fox2SQL(.F.) + ") AS Media_Ant " +;
		    		"   FROM FACTURA F " +;
		    		"  WHERE SUBSTR(F.Cobro, 1, 7) = " + oMySQL.FOX2SQL(lcCobroAnt) +;
		    		"    AND SUBSTR(F.Cod_Socio, 1, 4) = " + oMySQL.FOX2SQL(tcZonaRuta) +;
		    		" 	 AND F.Es_Factura <> 3 " +;
		    		"    " + lcListaClientesID 
		    WAIT WINDOW "[PROCESO] Obteniendo Lecturas de Facturas" NOWAIT 
		    oMySQL.Ejecutar(lcSQL,"cLecFAC",THIS.DataSession)
			INDEX ON ID_Socio TAG ID_Socio ADDITIVE

			&& Correccion: 08-01-2019, By : Ing. Alfonzo Salgado Flores
			&& Nota: No se habia migrado correctamente la consulta.
			&&  AND YEAR(F.F_EMISION) >= YEAR(m.F_GenLect) - 1
			&& Razon de Error: en fecha 04-01-2019 se Creo la planilla 19356 con Cobro erroneo : 2019-06
			&& a consecuencia de ello cLecMAX se uso para LectAnt pero como estaba mal migrado, dio lectura max erroneo
			*"  MAX(IIF(F.LectACT = 0, "+ oMySQL.Fox2SQL(.T.) +", " + oMySQL.Fox2SQL(.F.) +" )) AS Media_Ant " +
		    lcSQL = " SELECT F.ID_Socio, MAX(F.LectACT ) AS LectACT , " +;
		    		"  MAX(IIF(F.LectACT = 0, "+ oMySQL.Fox2SQL(.T.) +", " + oMySQL.Fox2SQL(.F.) +" )) AS Media_Ant " +;
					"   FROM FACTURA F " +;
					"  WHERE NOT UPPER(SUBSTR(F.Cobro,1,5)) = UPPER('Cobro') " +;
					"    AND SUBSTR(F.cod_socio,1,4)= " + oMySQL.FOX2SQL(tcZonaRuta) +;
					"    AND YEAR(F.F_EMISION) >= " +  oMySQL.Fox2SQL(YEAR(tdF_GenLect) - 1) +;  
					"    " + lcListaClientesID+;
		    		"  GROUP BY F.ID_Socio "
		    WAIT WINDOW "[PROCESO] Obteniendo Lecturas Maximas" NOWAIT 

			oMySQL.Ejecutar(lcSQL,"cLecMAX",THIS.DataSession)
			INDEX ON ID_Socio TAG ID_Socio ADDITIVE
			oMySQL.GetTablaIndexada(THIS.curActivos,  "TEMPORAL", "*", THIS.DataSession)
			
			INDEX ON ID_Socio TAG ID_Socio ADDITIVE
			INDEX ON ID_ESMODA TAG ID_ESMODA ADDITIVE
			INDEX ON CANTANOR TAG CANTANOR ADDITIVE
			INDEX ON INDICEUSO TAG INDICEUSO ADDITIVE
			INDEX ON COD_Socio TAG COD_Socio ADDITIVE	

			REPLACE Cobro WITH tcCobro ALL

			lcSQL = "SELECT * FROM SOCIOPER WHERE ID_Persona IN(SELECT ID_Persona FROM " + THIS.curActivos + ")"
			oMySQL.Ejecutar(lcSQL,"SOCIOPER",THIS.DataSession)
			INDEX ON ID_Socio TAG ID_Socio ADDITIVE
			
			SELECT TEMPORAL
			SET RELATION TO ID_Socio INTO SOCIOPER ADDITIVE
			lcRecTotal = ALLTRIM(STR(RECCOUNT("TEMPORAL"),10))
			lnFila = 0
	    	SELECT TEMPORAL
		 	SCAN ALL
		 		lnFila = lnFila + 1
		 		*SET STEP ON
		 		lcRecNo =  ALLTRIM(STR(lnFila))  &&ALLTRIM(STR(RECNO("TEMPORAL"),10))
		 		WAIT WINDOW "Procesando [ASOCIADO] : " + ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) +" - " + TEMPORAL.Cod_Socio + CHR(13) +;
							"Registro: " + lcRecNo + " de " + lcRecTotal	 NOWAIT 

		    	m.ID_Socio = TEMPORAL.ID_Socio
				SELECT cLecFAC
				SEEK m.ID_Socio
		    	IF FOUND()
			        SELECT TEMPORAL
					REPLACE ID_Socio  WITH TEMPORAL.ID_Socio;
		 					LectANT   WITH cLecFAC.LectACT + IIF(cLecFAC.Media_Ant,cLecFAC.LectACT + cLecFAC.Consumo, 0);
				        	Media_Ant WITH cLecFAC.Media_Ant
		    	ELSE
			    	SELECT cLecLEC
				    SEEK m.ID_Socio
		    	    IF FOUND()
		        		SELECT TEMPORAL
				   		REPLACE ID_Socio  WITH TEMPORAL.ID_Socio;
		 			       		LectANT   WITH cLecLEC.LectACT + IIF(cLecLEC.Media_Ant, cLecLEC.LectANT + cLecLEC.Consumo, 0);
				           		Media_Ant WITH cLecLEC.Media_Ant
					ELSE
						SELECT cLecMAX
				     	SEEK m.ID_Socio
				      	IF FOUND()
		        	   		SELECT TEMPORAL
					   		REPLACE ID_Socio  WITH TEMPORAL.ID_Socio ;
		 				    	    LectANT   WITH cLecMAX.LectACT ;
					            	Media_Ant WITH cLecMAX.Media_Ant
					    ELSE
					    	lcSQL = " SELECT L.ID_Socio, L.LectACT, L.Consumo, L.LectANT,  G.F_GENLECT," +;
						   			" 		 IIF(L.LectACT = 0, "+ oMySQL.Fox2SQL(.T.) +", " + oMySQL.Fox2SQL(.F.) + ") AS Media_Ant " +;
						   			"   FROM GENLECT L, GENFACT G " +;
						   			"  WHERE L.ID_GenFact = G.ID_GenFact " +;
						    		"    AND G.Cobro = " + oMySQL.Fox2SQL(lcCobroAnt)+ ;
						    		"    AND Id_Socio = " + oMySQL.Fox2SQL(TEMPORAL.Id_Socio)
						   	oMySQL.Ejecutar(lcSQL, "cLectXID", THIS.DataSession)
						   	IF RECCOUNT("cLectXID") > 0
								SELECT TEMPORAL
						   		REPLACE ID_Socio  WITH TEMPORAL.ID_Socio ;
			 				    	    LectANT   WITH cLectXID.LectACT ;
						            	Media_Ant WITH cLectXID.Media_Ant						   	
						   	ENDIF
					  	ENDIF		
			    	ENDIF
			  	ENDIF
			  	&&Asignamos Indices de Uso y Anormalidad Mas Usado Historicacmente de los 12 ultimos meses
			  	*SET STEP ON 
			  	THIS.ObtenerIndiceAnormalidad(TEMPORAL.ID_SOCIO, tcCobro)
		 	ENDSCAN
		 	*********** Buscar los nuevos
			IF(tlConMedidor)
				lcMedidor = " AND ID_Medidor > 0 "
			ELSE
				lcMedidor = ""
			ENDIF

			SELECT TEMPORAL
			SET ORDER TO ID_Socio
		 	m.EsNuevo = .T.

		 	lcSQL = " SELECT I.ID_Socio, I.LectIni AS LectAnt, "+;
					"		 I.F_INSTALA, I.F_ACTIVA, I.F_TRABAJO, I.F_FACTURAR,  " +;
					"		 S.Cod_Socio, S.Id_Persona, S.Id_Categ, S.Id_Medidor  " +;
					"	FROM INSTALAM I, SOCIOS S " +;
					"  WHERE I.Id_Socio = S.Id_Socio " +;
					"	 AND I.F_FACTURAR >= " + oMySQL.Fox2SQL(ldFechaIni) +;
    				"    AND I.F_FACTURAR <= " + oMySQL.Fox2SQL(ldFechaFin) +;
    		   		"    AND I.Es_Instala = 2 " +;
    		   		"    AND I.TIPOINSTAL = 1 " +;
    		   		"    AND SUBSTR(I.Cod_Socio,1,4)= " + oMySQL.FOX2SQL(tcZonaRuta) +;
    		   		"    AND I.Id_Medidor = 0"
    		STRTOFILE(lcSQL, oError.PathLOGs + "\INSTALAM_BAJATEMPORAL.TXT")
		    oMySQL.Ejecutar(lcSQL, "cBajaTemporal", THIS.DataSession)
		   	SELECT cBajaTemporal
		   	lcRecTotal = ALLTRIM(STR(RECCOUNT("cBajaTemporal"),10))
		 	SCAN ALL
		 		lcRecNo = ALLTRIM(STR(RECNO("cBajaTemporal"),10))
				SCATTER NAME lmBajaTemporal
				SELECT TEMPORAL
				WAIT WINDOW "Procesando[BAJA TEMPORAL] Socio : " +;
							 ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) +" - " + TEMPORAL.Cod_Socio + CHR(13) +;
							"Registro: " + lcRecNo + " de " + lcRecTotal	 NOWAIT 
				SEEK cBajaTemporal.ID_Socio
				IF FOUND() 
					SELECT TEMPORAL
					REPLACE TEMPORAL.LECTANT WITH lmBajaTemporal.LectAnt
					IF(TYPE("pGlobal.ID_Regula") != 'U')
						REPLACE TEMPORAL.ID_MEDIEST WITH pGlobal.ID_Regula
					ENDIF
				ELSE
					&& Add: 17-09-2022, By: ASF, Nota: por que lo activan y colocan fechafacturacon dias depues de la obtencion de la planilla de lecturacion									   
					*SELECT cBajaTemporal
					*SCATTER MEMVAR 
					*SELECT TEMPORAL
					*APPEND BLANK
					*GATHER MEMVAR
					*IF(TYPE("pGlobal.ID_Regula") != 'U')
					*	REPLACE TEMPORAL.ID_MEDIEST WITH pGlobal.ID_Regula
					*ENDIF
				ENDIF
		 		SELECT cBajaTemporal
		 	ENDSCAN

		 	lcSQL = " SELECT I.ID_Socio, I.LectIni AS LectAnt, I.Id_Medidor As Id_MedidorI, "+;
					"		 I.F_INSTALA, I.F_ACTIVA, I.F_TRABAJO, I.F_FACTURAR,  " +;
					"		 S.Cod_Socio, S.Id_Persona, S.Id_Categ, S.Id_Medidor  " +;
					"	FROM INSTALAM I, SOCIOS S " +;
					"  WHERE I.Id_Socio = S.Id_Socio " +;
					"    AND I.F_FACTURAR >= " + oMySQL.Fox2SQL(ldFechaIni) +;
    				"    AND I.F_FACTURAR <= " + oMySQL.Fox2SQL(ldFechaFin) +;
    		   		"    AND I.Es_Instala = 2" +;
    		   		"    AND I.TIPOINSTAL = 1 " +;
    		   		"    AND SUBSTR(I.Cod_Socio,1,4)= " + oMySQL.FOX2SQL(tcZonaRuta) +;
    		   		"    AND I.Id_Medidor > 0"
		    oMySQL.Ejecutar(lcSQL, "Nuevos", THIS.DataSession)
		    STRTOFILE(lcSQL, oError.PathLOGs + "\INSTALAM_NUEVOS.TXT")
		   	SELECT Nuevos
		   	lcRecTotal = ALLTRIM(STR(RECCOUNT("Nuevos"),10))
		 	SCAN ALL
		 		lcRecNo = ALLTRIM(STR(RECNO("Nuevos"),10))
				SCATTER NAME lmNuevo
				SELECT TEMPORAL
				WAIT WINDOW "Procesando[INSTALAM] Asociados Nuevos : " +;
							 ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) +" - " + TEMPORAL.Cod_Socio + CHR(13) +;
							"Registro: " + lcRecNo + " de " + lcRecTotal	 NOWAIT 
				SEEK Nuevos.ID_Socio
				IF FOUND() AND TEMPORAL.LectANT = 0
					GATHER NAME lmNuevo
					IF(TYPE("pGlobal.ID_Nuevo") != 'U')
						REPLACE TEMPORAL.ID_MEDIEST WITH pGlobal.ID_Nuevo
					ENDIF					
				ELSE
					&& Add: 17-09-2022, By: ASF, Nota: por que lo activan y colocan fechafacturacon dias depues de la obtencion de la planilla de lecturacion									   
					*SELECT cBajaTemporal
					*SCATTER MEMVAR 
					*SELECT TEMPORAL
					*APPEND BLANK
					*GATHER MEMVAR
					*IF(TYPE("pGlobal.ID_Nuevo") != 'U')
					*	REPLACE TEMPORAL.ID_MEDIEST WITH pGlobal.ID_Nuevo
					*ENDIF			
				ENDIF
		 		SELECT Nuevos
		 	ENDSCAN

            lcSQL = " SELECT I.ID_Socio, I.COD_SOCIO, I.LectIni AS LectAnt, I.F_SociMed, " +;
	 			 		 	 oMySQL.Fox2SQL(ldFechaIni) + " AS FechaAct, " +;
	 			 	"        I.F_Facturar, I.F_Trabajo " +;
		   			"   FROM SOCIMEDI I " +;
		   			"  WHERE I.F_Facturar >= " + oMySQL.Fox2SQL(ldFechaIni) +;
		   			"    AND I.F_Facturar <= " + oMySQL.Fox2SQL(ldFechaFin) +;
		    		"    AND I.Es_SociMed = 2" +;
		    		"    AND SUBSTR(I.Cod_Socio,1,4)= " + oMySQL.FOX2SQL(tcZonaRuta) +;
		    		"  ORDER BY I.ID_SOCIO ASC "
		    STRTOFILE(lcSQL, oError.PathLOGs + "\SOCIMEDI_CAMBIO.TXT")
			oMySQL.Ejecutar(lcSQL, "NuevoMED", THIS.DataSession)
		   	
		   	SELECT NuevoMED
		   	lcRecTotal = ALLTRIM(STR(RECCOUNT("NuevoMED"),10))
		 	SCAN ALL
		 		lcRecNo = ALLTRIM(STR(RECNO("NuevoMED"),10))
				SCATTER NAME lmNuevoMED
				SELECT TEMPORAL
		 		WAIT WINDOW "Procesando[SOCIMEDI] Nuevo Medidor Socio : " + ;
		 					ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) +" - " + TEMPORAL.Cod_Socio + CHR(13) +;
							"Registro: " + lcRecNo + " de " + lcRecTotal	 NOWAIT 
				SEEK NuevoMED.ID_Socio
				IF FOUND()				
					GATHER NAME lmNuevoMED
					IF(TYPE("pGlobal.ID_Cambio") != 'U')
						REPLACE TEMPORAL.ID_MEDIEST WITH pGlobal.ID_Cambio
					ENDIF
				ENDIF
		 		SELECT NuevoMED
			ENDSCAN		
			************************************************************************************
			&&Add: 03-09-2019, By: Ing. Alfonzo Salgado Flores, Nota: Para los cambios de ubicacion en proceso de lectura 
			lcSQL = " SELECT D.ID_SOCIO, D.DETALLE AS COD_SOANT, D.COD_SOCIO AS COD_SOCACT" +;
    				"   FROM CAMBCODI M, CAMBCDET D" +;
    				"  WHERE M.ID_CAMBCOD = D.ID_CAMBCOD " +;
    				"	 AND M.F_CAMBCOD >= " + oMySQL.FOX2SQL(ldFechaIni) +;
    				"    AND SUBSTR(D.COD_SOCIO,1,4) = " + oMySQL.FOX2SQL(tcZonaRuta) +;
    				"    AND SUBSTR(D.DETALLE,1,4) <> SUBSTR(D.COD_SOCIO,1,4)" +;
    		   		"    AND M.ES_CAMBCOD = 2 "
		    oMySQL.Ejecutar(lcSQL, "curCambCod", THIS.DataSession)
		    
		   	SELECT curCambCod
		   	lcRecTotal = ALLTRIM(STR(RECCOUNT("curCambCod"),10))
		 	SCAN ALL
		 		lcRecNo = ALLTRIM(STR(RECNO("curCambCod"),10))
				SCATTER NAME lmNuevoMED
				SELECT TEMPORAL
				IF (curCambCod.ID_SOCIO = 19904)
				 	*SET STEP ON 
				ENDIF 
		 		WAIT WINDOW "Procesando[CAMBIO-UBICACION] Nuevo Medidor Socio : " + ;
		 					ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) +" - " + TEMPORAL.Cod_Socio + CHR(13) +;
							"Registro: " + lcRecNo + " de " + lcRecTotal	 NOWAIT 
				SEEK curCambCod.ID_Socio
				IF FOUND()
				*Mod: 11-09-2019, By: Ing. Alfonzo SF, Nota : No sirve > "   AND L.COD_SOCIO <> " + oMySQL.Fox2SQL(curCambCod.COD_SOCACT) 
					lcSQL = "SELECT TOP 1 L.ID_SOCIO, L.LECTANT, L.LECTACT " +;
							"  FROM GENLECT L " +;
							" WHERE L.ID_SOCIO = " + oMySQL.Fox2SQL(curCambCod.ID_Socio) +;
							"   AND L.COBRO < " + oMySQL.Fox2SQL(tcCobro) +;
							"   AND L.COD_SOCIO <> " + oMySQL.Fox2SQL(curCambCod.COD_SOCACT) +;
							" ORDER BY L.COBRO DESC "
					oMySQL.Ejecutar(lcSQL, "curLectAntxCamb", THIS.DataSession)
					IF RECCOUNT("curLectAntxCamb") > 0
						SELECT TEMPORAL
						REPLACE TEMPORAL.LECTANT WITH curLectAntxCamb.LectAct
					ELSE
						&&Obtener de factura o otro lado...: TODO
					ENDIF
				ENDIF
		 		SELECT curCambCod
			ENDSCAN		
		   	************************************************************************************

			THIS.ProcesoMedia(tcCobro, tdf_GenLect, 0)	
			SELECT TEMPORAL
			GO TOP
			SELECT(lnArea)
		CATCH TO loEx
			lcLog = "  Procedure: GenLect.ObtenerLecturas()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	*********************************************************
	* Método........: ObtenerIndiceAnormalidad
	* Descripción...: Descripión de ObtenerIndiceAnormalidad
	* Fecha.........: 12-08-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE ObtenerIndiceAnormalidad(tnID_Socio AS Integer, tcCobro AS String)
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, lcCobroIni
		lnArea = SELECT()
		TRY
			&&Si Esta habilitado ObtenerIndice Anormalidad Historico, calculamos los valores siguientes.
			lcCobroIni = THIS.GetAAMMANT(tcCobro, 12)
			lcSQL = "SELECT L.*, " +;
					" 		IIF(L.ID_MEDIEST > 0, 1, 0) AS TieneAnor " +;
					"  FROM GENLECT L" +;
					" WHERE L.Cobro >= " + oMySQL.Fox2SQL(lcCobroIni) +;
					"   AND L.ID_SOCIO = " + oMySQL.Fox2SQL(tnID_Socio)
			oMySQL.Ejecutar(lcSQL, "curLecturaSocio", THIS.DataSession)
			THIS.oEstadistica.CalculosEstadisticos3("curLecturaSocio", "ID_MEDIEST", "TIENEANOR", "COBRO")
			SELECT("curLecturaSocio")
        	CALCULATE SUM(TieneAnor) TO lnCantAnor
			SELECT TEMPORAL
			REPLACE CANTANOR WITH lnCantAnor;
					ID_ESMODA WITH INT(THIS.oEstadistica.nModa);
					ID_ESMODA2 WITH INT(THIS.oEstadistica.nModa2);
					INDICEUSO WITH THIS.oEstadistica.nIndiceUso
		CATCH TO loEx
			lcLog = "  ProcedureInitial: ClassName.ObtenerIndiceAnormalidad()"+ THIS._Enter +" "+ "VariableLog"
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC
	
	PROCEDURE CalcularMedia(tnID_Socio AS Integer, tcCurHistFact AS String)
		LOCAL lcWhereSocio
	    IF PARAMETERS() = 0
	       tnID_Socio = 0
	    ENDIF
		&& Variables previos
	    IF tnID_Socio = 0
	     	lcWhereSocio =" "
	    ELSE
	     	lcWhereSocio = " WHERE H.ID_SOCIO = " + oMySQL.FOX2SQL(tnID_Socio)
	    ENDIF
	    lnArea = SELECT()
		lcSQL= " SELECT H.* "+;
			   "   FROM " + tcCurHistFact + " H " +;
			   lcWhereSocio

		oMySQL.Ejecutar(lcSQL,"curHistFactSocio",THIS.DataSession)
		&& Obtenemos el consumo promedio del tnID_Socio
		IF (tnId_Socio = 380)
			&&SET STEP ON 
		ENDIF
		lnConsuAcum = 0.0
		lnExisteHist = 0
		SELECT curHistFactSocio
	    GO TOP
		SCAN ALL
			IF NOT INLIST(curHistFactSocio.Regla, 1, 4, 6)
				lnExisteHist	= lnExisteHist + 1
				lnConsuAcum 	= lnConsuAcum + curHistFactSocio.Consumo
				IF(lnExisteHist = pGlobal.Meses_Prom)
					EXIT
				ENDIF
			ELSE
			ENDIF
		ENDSCAN
		lnMediaNew = 0.0
		IF (lnExisteHist > 0)
			lnMediaNew = lnConsuAcum / lnExisteHist && No Siempre sera 6 meses
		ENDIF
		SELECT (lnArea)
		RETURN lnMediaNew
	ENDPROC

	*********************************************************
	* Método........: ProcesoMedia
	* Descripción...: Descripión de ProcesoMedia
	* Fecha.........: 01-01-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* FechaModificado.: 21-09-2019, Nota: Add:Parametro tcCobro.
	*********************************************************
	PROCEDURE ProcesoMedia(tcCobro AS String, tdF_GenLect AS Integer, tnId_Socio AS Integer)
	  	LOCAL lnArea, lnEsInstalacionNueva, lcHistFact
	  	LOCAL lcRecTotal, lcRecNo
	  	lnArea = SELECT()
	  	lnEsInstalacionNueva = -1

	   	lcHistFact = THIS.DO_HISTOFACT(tdF_GenLect, tnId_Socio)
	   	IF (EMPTY(lcHistFact))
	   		RETURN
	   	ENDIF
	   	lcSQL = "SELECT ID_Socio, 00000 AS Consumo "+;
	   			"  FROM SOCIOPER"
	   	oMySQL.EjecutarCursor(lcSQL,"SOCCONSUMO",THIS.DataSession)
	   	INDEX ON ID_Socio TAG ID_Socio ADDITIVE
	   	
	   	lcRecTotal = ALLTRIM(STR(RECCOUNT("TEMPORAL"),10))
	    SELECT TEMPORAL
	  	SCAN ALL
	  		lcRecNo = ALLTRIM(STR(RECNO("TEMPORAL"),10))
		 	WAIT WINDOW "Obteniendo Promedio[Consumo] del " +;
		 				" Socio : " + ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) + " - " + TEMPORAL.Cod_Socio + CHR(13) +;
						"Registro: " + lcRecNo + " de " + lcRecTotal	 NOWAIT 
	  		lnMediaNew = THIS.CalcularMedia(TEMPORAL.ID_Socio, lcHistFact)
	  		SELECT SOCCONSUMO
			SEEK Temporal.ID_Socio
			REPLACE SOCCONSUMO.Consumo WITH lnMediaNew
			SELECT TEMPORAL
			REPLACE TEMPORAL.Media WITH lnMediaNew
			*IF(TEMPORAL.ID_SOCIO = 28098)
			*	SET STEP ON 
			*ENDIF 
			IF(THIS.lInstalacionNuevaEnEstado = .T.)
				lnEsInstalacionNueva = THIS.EsInstalacionNueva(TEMPORAL.ID_MediEst, TEMPORAL.ID_Socio, tcCobro)
				IF INLIST(lnEsInstalacionNueva, 0 ,1)
					SELECT TEMPORAL
					REPLACE TEMPORAL.ID_MediEst WITH pGlobal.ID_Nuevo
					REPLACE TEMPORAL.Error WITH THIS.ErrorMsg
				ENDIF
				lnEsInstalacionNueva = -1
			ENDIF
	  	ENDSCAN
	  	oMySQL.ActualizarDatosKeys("SOCIOS","SOCCONSUMO","ID_Socio",THIS.DataSession)
	  	USE IN SOCCONSUMO
	  	
		IF tnId_Socio > 0

	        lcSQL = " SELECT S.ID_Socio, S.Cod_Socio, S.Consumo > 0 AS Confiable,"+;
	               	" 		 6 AS Meses, S.Consumo AS Promedio "+;
	          		"   FROM SOCIOS S "+;
	         		"  WHERE S.ID_SOCIO = "+oMySQL.Fox2SQL(tnId_Socio)+;
	         		"  GROUP BY S.ID_Socio "+;
	         		"  ORDER BY S.Cod_Socio "
	        
	        oMySQL.Ejecutar(lcSQL,"curMedia",THIS.DataSession)

	        SELECT curMedia
			DIMENSION vParametros(5,3 )
			vParametros(1,1) ="CurMedia.Id_Socio"
			vParametros(1,2) ="Codigo Fijo"
			vParametros(1,3) = 50

			vParametros(2,1) ="CurMedia.Cod_Socio"
			vParametros(2,2) ="Socio"
			vParametros(2,3) =60
			
			vParametros(3,1) ="CurMedia.Consumo"
			vParametros(3,2) ="Consumo"
			vParametros(3,3) = 45
			
			vParametros(4,1) ="CurMedia.Confiable"
			vParametros(4,2) ="Confiable"
			vParametros(4,3) = 30
			
			vParametros(5,1) ="CurMedia.Promedio"
			vParametros(5,2) ="Promedio"
			vParametros(5,3) =35

			loForm = CreateObject("Browse",@vParametros,"CurMedia")
			loForm.WindowType = 1 
			loForm.titulo.txtTitulo.Caption="Promedio de Lectura"
			loForm.Show()

	        USE IN curMedia
		ENDIF
	  	SELECT TEMPORAL
	  	SET ORDER TO COD_Socio
	  	SELECT(  lnArea )
		RETURN
	ENDPROC

	PROCEDURE CALCMEDIA(tnID_Socio AS Integer)	
	    IF PARAMETERS() = 0
	       tnID_Socio = 0
	    ENDIF
		&& Variables previos
	    IF tnID_Socio = 0
	     	cSTR = " .T. "
	    ELSE
	     	cSTR = " G.ID_SOCIO = tnID_Socio "
	    ENDIF
	    lnArea = SELECT()
		SELECT G.*  ;
	  	  FROM HISTFACT G ;
	  	 WHERE &cSTR ;
		  INTO CURSOR curHist
		&& Obtenemos el consumo promedio del tnID_Socio
		lnConsuAcum = 0.0
		lnExisteHist = 0
		SELECT curHist
	    GO TOP
		SCAN ALL
			lnExisteHist	= lnExisteHist + 1
			lnConsuAcum 	= lnConsuAcum + curHist.Consumo
			IF(lnExisteHist = 6)
				EXIT
			ENDIF
		ENDSCAN
		lnMediaNew = 0.0
		IF (lnExisteHist > 0)
			lnMediaNew = lnConsuAcum / lnExisteHist
		ENDIF
		SELECT (lnArea)
		RETURN lnMediaNew
	ENDPROC

	PROCEDURE DO_HISTOFACT(tdFecha AS Date, tnId_Socio AS Integer)	
	    PRIVATE lnArea, lcID_Socio
	    PRIVATE lcSQL, lcHistFactTemp, lcOrderBy
	    
	    lnArea = SELECT()
	    IF tnId_Socio > 0
	    	lcID_Socio = "AND F.ID_Socio = "+oMySQL.FOX2SQL(tnId_Socio)
	    ELSE
	        lcID_Socio = ""
	    ENDIF
	
	    *******************************************************************************************
	    *Version........: 2.0 : Para obtener Historico de Facturas de un Año de todos los Socios de una Id_GenFact
	    *Desarrollador..: Ing. Alfonzo Salgado Flores.
	    *Fecha..........: 21-06-2018, Corrgido 22-06-2018
	    lnCantCli = 0
	    lcCobroFin =  oUtil.AAMMANT(GENFACT.Cobro)
	    lcSQL = "SELECT COUNT(*) AS CANTIDAD " +;
	    		"  FROM " + THIS.curActivos 
	    oMySQL.Ejecutar(lcSQL, "_SocActivos_", THIS.DataSession)
	    IF USED ("_SocActivos_")
	    	IF NOT ISNULL(_SocActivos_.Cantidad)
	    		lnCantCli = _SocActivos_.Cantidad
	    	ENDIF
	    	USE IN SELECT("_SocActivos_")
	    ENDIF
	    
	    IF lnCantCli = 0
	    	&&Error Fatal, si no hay clientes no hay nada que hacer
	    	RETURN ""
	    ENDIF

	    lnNroMeses = 12
		lnCantReg = lnNroMeses * lnCantCli
		IF lnCantReg > 0
			lcTop = oMySQL.Fox2SQL(lnCantReg)
		ELSE
			lcTop =  1
		ENDIF
		lcCobroIni = THIS.GetAAMMANT(lcCobroFin, lnNroMeses)
		
		&& Modificado:12-12-2018, By: Alfonzo Salgado Flores, razon:se debe llevar todas las lecturas con anormalidades
		&&"   AND F.ID_MediEst =  0 "+;  && << esta se quito por la razon que estaba mal.
		lcOrderBy = ""
		IF(oMySQL.Tipo = 0)
			lcOrderBy = " ORDER BY F.ID_SOCIO, F.Cobro DESC "
		ENDIF 
		lcSQL = "SELECT TOP (" + lcTop + ") F.ID_FACTURA, F.N_FACTURA, F.ID_SOCIO, F.COBRO, " +;
				"  		F.F_EMISION AS FEMISION, F.MTO_TOTAL AS MONTO, F.FACPAGO,   F.ES_FACTURA, " +;
				"	 	F.LECTANT, F.LECTACT, F.CONSUMO, F.CONSUMOFAC, F.ID_MEDIEST " +;
			 	"  FROM FACTURA F " +;
				" WHERE " +;
				"       SUBSTR(F.Cobro, 1, 1) <> 'C' " + ;
				"   AND F.Cobro >= " + oMySQL.Fox2SQL(lcCobroIni) +;
		 		"   AND F.Cobro <= " + oMySQL.Fox2SQL(lcCobroFin) +;
		 		"   AND F.ES_FACTURA <> 3 " +;
		 		"	AND	F.ID_SOCIO IN (SELECT ID_SOCIO FROM " + THIS.curActivos + " ) " +;
		 		"   " + lcID_Socio +;
	 			"   " + lcOrderBy
		lcHistFactTemp = oMySQL.CrearTemporal(lcSQL)
		lcSQL = " SELECT F.*, NVL(ME.REGLA, 000) AS REGLA" +;
				"   FROM " + lcHistFactTemp + "  F LEFT JOIN MEDIESTA ME ON (F.ID_MEDIEST = ME.ID_MEDIEST) " 
				&&"  ORDER BY F.ID_SOCIO, F.Cobro DESC " 
		lcHistFact = oMySQL.CrearTemporal(lcSQL) 

	    SELECT(lnArea)
		RETURN 	lcHistFact
	ENDPROC

	*********************************************************
	* Método........: GetAAMMANT
	* Return........: Retorna el MesCobro(YYYY-MM) del tnNroMeses
	* Descripción...: Descripción de GetAAMMANT
	* Fecha.........: 21-06-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION GetAAMMANT(tcCobro AS String, tnNroMeses AS Integer) AS String
		LOCAL lnDias, ldFecha, lcFecha, lcCobro, lnDateFormat
		lnDateFormat = SET("Date")
		SET DATE MDY
		lnDias = (tnNroMeses - 1) * 30
		lcFecha = SUBSTR(tcCobro,6,2) +"/01/"+SUBSTR(tcCobro,1,4)
		ldFecha = CTOD(lcFecha)
		ldFecha = ldFecha - lnDias
		lcCobro = STR(YEAR(ldFecha),4)+"-"+ STRTRAN(STR(MONTH(ldFecha),2),' ', '0')
		SET DATE &lnDateFormat
		RETURN lcCobro
	ENDFUNC


	*********************************************************
	* Método........: HistSoc
	* Descripción...: Descripión de HistSoc
	* Fecha.........: 10-05-2019
	* Diseñador.....: Ing. Cesar Corvera Murakami
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE HistSoc(tnId_Socio AS Integer, tnID_GenFact AS Integer)
		PRIVATE lnArea, loFormHist
		LOCAL lcSocio, lcCod_Socio, lcNombre, lcSocioFull
		lnArea = SELECT()
		IF NOT USED("MEDIESTA")
			oMySQL.GetTablaIndexada("MEDIESTA","MEDIESTA","*",THIS.DataSession)
		ENDIF
		IF NOT USED("MEDIESTA2")
			oMySQL.GetTablaIndexada("MEDIESTA","MEDIESTA2","*",THIS.DataSession)
		ENDIF
		IF NOT USED("PLOMEROS2")
			oMySQL.GetTablaIndexada("PLOMEROS","PLOMEROS2","*",THIS.DataSession)
		ENDIF
		CREATE CURSOR LecSocio (Cobro   	C(8),;
								LectAnt 	N(6),;
								LectAct 	N(6),;
								Consumo 	N(10),;
								ConsumoFac 	N(10),;
								Media		N(10),;
								ID_MEDIEST  N(10),;
								Estado_Med 	C(50),;
								ID_MEDIES2  N(10),;
								Estado_Me2 	C(50),;
								Media_Ant 	L,;
								ID_PLOMERO  N(10),;
								PlomNombre  C(50))


		INDEX ON Cobro TAG Cobro ADDITIVE DESCENDING
		
		IF NOT USED("MEDIESTA3")
			oMySQL.GetTablaIndexada("MEDIESTA","MEDIESTA3","*",THIS.DataSession)
		ENDIF

		IF(EMPTY(tnID_GenFact))
			IF USED("TEMPORAL") AND (TYPE("TEMPORAL.ID_GENFACT") = "N")	
				IF TEMPORAL.ID_GenFact > 0
					lcGenFact =  " AND L.ID_GenFact <= "+ oMySQL.FOX2SQL(TEMPORAL.ID_GenFact)
				ELSE
					lcGenFact =  ""
				ENDIF
			ELSE
				lcGenFact =  ""
			ENDIF 
		ELSE
			lcGenFact =  " AND L.ID_GenFact <= "+ oMySQL.FOX2SQL(tnID_GenFact)
			IF NOT USED("MEDIESTA2")
				oMySQL.GetTablaIndexada("MEDIESTA","MEDIESTA2","*",THIS.DataSession)
			ENDIF
		ENDIF

		*lcSQL= 	" SELECT L.*, G.ID_PLOMERO, IIF(ISNULL(GL.ID_MEDIES2), 0 , GL.ID_MEDIES2) AS  ID_MEDIES2 "+;
				"   FROM GENLECT L, GENFACT G LEFT JOIN GENLECTN GL ON (G.ID_GENFACT = GL.ID_GENFACT) "+;
				"  WHERE G.ID_GENFACT = L.ID_GENFACT " +;
				"    AND L.Id_SOCIO =  " + oMySQL.FOX2SQL(tnId_Socio)+;
				"" +      lcGenFact +;
				"  ORDER BY L.Cobro DESC "
		lcSQL =	" SELECT TOP 12 L.*, G.ID_PLOMERO " +;
				"   FROM GENLECT L, GENFACT G " +;
				"  WHERE G.ID_GENFACT = L.ID_GENFACT " +;
				"    AND L.ID_SOCIO = " + oMySQL.FOX2SQL(tnId_Socio) +;
				" " +  lcGenFact +;
				"  ORDER BY L.COBRO DESC" 
		
		lcCurHistorico = oMySQL.CrearTemporal(lcSQL)						
		
		lcSQL =	" SELECT G.*, IIF(ISNULL(GL.ID_MEDIES2), 0 , GL.ID_MEDIES2) AS  ID_MEDIES2 "+;
				"   FROM " + lcCurHistorico + " G LEFT JOIN GENLECTN GL ON (G.ID_GENFACT = GL.ID_GENFACT AND G.ID_SOCIO = GL.ID_SOCIO) "+;
				"  ORDER BY G.Cobro DESC "

		oMySQL.Ejecutar(lcSQL, "Lectura", THIS.DataSession)
		IF(EMPTY(tnID_GenFact))
			SCAN ALL
	  			SCATTER MEMVAR	  			
	  			m.estado_med = ""
	  			SELECT MEDIESTA
	  			SEEK LECTURA.ID_MediEst
	  			IF FOUND()
						m.Estado_Med = MediEsta.Nomb_MediE
	  			ENDIF
	  			m.PlomNombre = ""
	  			SELECT PLOMEROS2
  				SEEK Lectura.ID_PLOMERO
  				IF FOUND()
  					m.PlomNombre = PLOMEROS2.Nombre
  				ENDIF

  				m.Estado_Me2 = ""			
	  			SELECT MEDIESTA3
	  			SEEK LECTURA.ID_MediEs2
	  			IF FOUND()
					m.Estado_Me2 = MEDIESTA3.Nomb_MediE
	  			ENDIF

	  			INSERT INTO LecSocio FROM MEMVAR
			ENDSCAN
		ELSE
			SCAN ALL			
	  			SCATTER MEMVAR	  
	  			m.estado_med = ""			
	  			SELECT MEDIESTA2
	  			SEEK LECTURA.ID_MediEst
	  			IF FOUND()
					m.estado_med = MEDIESTA2.Nomb_MediE
	  			ENDIF

	  			m.PlomNombre = ""
	  			SELECT PLOMEROS2
  				SEEK Lectura.ID_PLOMERO
  				IF FOUND()
  					m.PlomNombre = PLOMEROS2.Nombre
  				ENDIF
  				m.Estado_Me2 = ""			
	  			SELECT MEDIESTA3
	  			SEEK LECTURA.ID_MediEs2
	  			IF FOUND()
					m.Estado_Me2 = MEDIESTA3.Nomb_MediE
	  			ENDIF

	  			INSERT INTO LecSocio FROM MEMVAR
			ENDSCAN
		ENDIF

		lcID_Socio = ALLTRIM(STR(tnID_Socio,10))
		lcCod_Socio = "000000000"
		lcNombre = "No Identificado"
		lcSocioFull = "Cliente/Socio/Usuario = " + lcID_Socio +" - " + lcCod_Socio + " - " + lcNombre
		lcSQL = "SELECT COD_SOCIO, NOMBRE " +;
				"  FROM SOCIOPER " +;
				" WHERE ID_SOCIO = " + oMySQL.Fox2SQL(tnId_Socio)
		oMySQL.Ejecutar(lcSQL, "curSocio_", THIS.DataSession)
		IF USED("curSocio_")
			IF(RECCOUNT("curSocio_") > 0)
				lcCod_Socio = curSocio_.Cod_Socio
				lcNombre = ALLTRIM(curSocio_.Nombre)
				lcSocioFull = "Cli/Soc/Usr = " + lcID_Socio +" - " + lcCod_Socio + " - " + lcNombre
			ENDIF 
			USE IN SELECT("curSocio_")
		ENDIf
		SELECT LecSocio
		GOTO TOP
		loGridBrowse = CREATEOBJECT("GridBrowse")
        loGridBrowse.SetCursorName("LecSocio")
		loGridBrowse.AddCol("Cobro", "Mes", 50)
		loGridBrowse.AddCol("LectAnt", "L. Ant", 45)
		loGridBrowse.AddCol("LectAct", "L. Act", 45)
		loGridBrowse.AddCol("Consumo", "Consumo", 60)
		loGridBrowse.AddCol("ConsumoFac", "Con. Fac", 55)
		loGridBrowse.AddCol("Media", "Media", 40)
		loGridBrowse.AddCol("Media_Ant", "Media Val", 60)
		loGridBrowse.AddCol("ID_MediEst", "Estado", 45, 1)
		loGridBrowse.AddCol("Estado_Med", "Anormalidad", 150, 2)
		loGridBrowse.AddCol("ID_MediEs2", "Estado2", 45, 1)
		loGridBrowse.AddCol("Estado_Me2", "Anormalidad2", 150)
		loGridBrowse.AddCol("ID_PLOMERO", "Lector", 45)
		loGridBrowse.AddCol("PlomNombre", "Lecturador Nombre", 160)
		*SET STEP ON 
		loFormHist = CreateObject("Browse", @loGridBrowse, "LecSocio")
		loFormHist.WindowType = 1
		loFormHist.Name = "LecSocio"
		loFormHist.titulo.txtTitulo.Caption = "Historico de Lecturas " + THIS._Enter +  lcSocioFull
		loFormHist.titulo.txt2.Caption = "Historico de Lecturas " + THIS._Enter + lcSocioFull
		loFormHist.Show()
		USE IN SELECT("LecSocio")
		SELECT (lnArea)
	ENDPROC

	FUNCTION AAMMANT(tcAAMM AS String) AS String
		Ano = VAL(SUBSTR(tcAAMM ,1,4))
		Mes = VAL(SUBSTR(tcAAMM ,6,2))
		IF Mes = 1
			m.Valor = STR(Ano-1,4)+"-12"
		ELSE
			m.Valor = STR(Ano,4)+"-"+STR(Mes-1,2)
		ENDIF
		RETURN STRTRAN(m.Valor," ","0")
	ENDFUNC

	PROCEDURE CONSUMOSOC()
	    IF TEMPORAL.LECTACT = 0
	    	SELECT SOCIOS
	    	SEEK TEMPORAL.ID_Socio
	        SELECT TEMPORAL
	        m.Consumo   = SocioS.Consumo
	        m.Media_Ant = .T.
	        && Colocar Lamedia
	        RETURN
	    ENDIF

		CREATE CURSOR LecSocio ;
		( 	Id_Genfact	N(10),	;	
			lectant		n(6),;
			lectact		n(6),;
			consumo		n(10),;
			Media_ANT   l,;
			id_mediEst	n(2) )

	   SELECT LECTURA
	   SEEK TEMPORAL.ID_Socio
	   DO WHILE id_socio = TEMPORAL.ID_Socio AND NOT EOF()
		 IF ID_GenFact <> TEMPORAL.ID_GenFACT
			 SCATTER MEMVAR
			 SELECT LecSocio
			 APPEND BLANK
			 GATHER MEMVAR
		 ENDIF
	   	 SELECT LECTURA
	   	 SKIP 1	
	   ENDDO

	   m.LECT2 = TEMPORAL.LECTACT
	   m.n  = 1
	   m.SumConsumo = 0

	   SELECT LECSocio
	   GO BOTTOM
	   DO WHILE LecSocio.LectANT = 0.00  AND NOT BOF()
		   m.SumConsumo = m.SumConsumo + LecSOCIO.Consumo
		   m.n =m.n +1	
		   SELECT LECSocio
		   SKIP -1
	   ENDDO
	   IF EOF()
	   		GO TOP
	   ELSE
		   IF LecSocio.LectANT <> 0.00
			   m.SumConsumo = m.SumConsumo + LecSOCIO.Consumo
			   m.n =m.n +1	   	
		   ENDIF
	   ENDIF


	    m.LECT1 = LecSOCIO.LECTANT
	    m.DifConsumo = m.Lect2 - m.Lect1
	    IF m.DifConsumo < m.SumConsumo
	    	m.Consumo = 0
	    ELSE
		    m.Razon = m.DifConsumo/m.n
		    m.Razon1 = m.Razon * (m.n-1)
		    IF m.Razon1 < m.SumConsumo
		    	m.Consumo = m.DifConsumo - m.Razon1
		    ELSE
		    	m.Consumo = m.DifConsumo - m.SumConsumo
		    ENDIF
		ENDIF
	    USE
		SELECT TEMPORAL
	ENDPROC

	*--------------------------------------------------------------------
	PROCEDURE proInconsis(oThisform AS Form, tcCobro AS String, tdF_GenLect AS Date, tnZona AS Integer, tnRuta AS Integer) 
		SET DATASESSION TO THIS.PARENT.DATASESSIONID
		PRIVATE nArea
		nArea = SELECT()
		IF oThisform.ESSI("Advertencia ...!!"," Desea Colocar solo los con anormalidades ??")
		  mSOLOS = "  ID_MediEst  > 0  "
		ELSE
		   mSOLOS = "  .T.  "
		ENDIF
		CREATE CURSOR Planilla ;
		( 	ID_Socio	N(10),;	
			COD_Socio   C(9),;
			Nombre      C(60),;
			LectAnt1	N(6),;
			LectAct1	N(6),;		
			Consumo1    N(6),;		
			DiasCon1	N(6),;				
			LectAnt2	N(6),;
			LectAct2	N(6),;		
			Consumo2    N(6),;				
			DiasCon2	N(6),;				
			LectAnt3	N(6),;
			LectAct3	N(6),;		
			Consumo3    N(6),;				
			DiasCon3	N(6),;						
			Promedio	n(6),;
			PorcError   N(12,6),;
			Prioridad   N(2),;
			Conclusion  C(130),;
			Confiable   L )

		INDEX ON COD_Socio TAG COD_Socio ADDITIVE

		mmAnt1 = tcCobro
		mmMES  = VAL(SUBSTR(tcCobro,6,2))
		m.cMes3 = oUtilLib.fnMes( mmMes )
		m.cMes2 = oUtilLib.fnMes( mmMes-1 )
		m.cMes1 = oUtilLib.fnMes( mmMes-2 )
		
		mmAnt3 = oUtilLib.MmAAAnt(tdF_GenLect-90)
 
		lcZonaRuta = STRTRAN(STR(tnZona,2)+STR(tnRuta,2),' ','0')
		
		lcSQL =	" SELECT L.ID_Socio, L.Cobro, L.Cod_Socio, L.ID_MediEST>0 as Confiable, MM.F_GenLect, "+;
		      	"        L.LectAnt,L.LectAct,L.Consumo "+;
				"   FROM Genlect L , GENFACT MM "+;
		  		"  WHERE MM.Cobro >= "+ oMySQL.FOX2SQL(mmAnt3) +;
		    	"    AND MM.Cobro <= "+ oMySQL.FOX2SQL(mmAnt1) +;
		    	"    AND L.ID_GenFact = MM.ID_GenFact "+;
		    	"	 AND SUBSTR(L.Cod_Socio,1,4) = "+ oMySQL.FOX2SQL(lcZonaRuta)+;
		  		"  ORDER BY L.ID_Socio, L.Cobro  DESC"
		oMySQL.Ejecutar(lcSQL,"curMedA", THIS.PARENT.DATASESSIONID)
		
		lcSQL = " SELECT C.* "+;
				"	FROM TEMPORAL T, curMedA C "+;
				"  WHERE T.ID_Socio = C.ID_Socio "
		oMySQL.EjecutarCursor(lcSQL,"curMedia", THIS.PARENT.DATASESSIONID)

		*INTO CURSOR curMedia  
		SELECT curMedia
		INDEX ON ID_Socio TAG ID_Socio ADDITIVE

		SELECT TEMPORAL
		SCAN FOR  &mSOLOS
		  SELECT PLANILLA
		  SCATTER MEMVAR BLANK
		  I = 3 
		  K = 0
		  m.ID_Socio  = TEMPORAL.ID_Socio
		  m.COD_Socio = TEMPORAL.COD_Socio
		  m.Nombre    = SOCIOPER.Nombre &&PERSONAS.Nombre
		  INSERT INTO PLANILLA FROM MEMVAR
		  SELECT curMEDIA
		  SEEK TEMPORAL.ID_Socio
		  DO WHILE SOCIOPER.ID_Socio = curMedia.ID_Socio AND NOT EOF() AND I > 0 && SOCIOS.ID_Socio = curMedia.ID_Socio AND NOT EOF() AND I > 0
		     mCOD  = "m.LECTANT"+STR(I,1)
		     &mCOD = curMEDIA.LectANT
		     mCOD  = "m.LECTACT"+STR(I,1)
		     &mCOD = curMEDIA.LectACT
		     mCOD  = "m.Consumo"+STR(I,1)
		     &mCOD = curMEDIA.Consumo
		     mCOD  = "m.DiasCon"+STR(I,1)
		     m.Consumo = curMEDIA.Consumo
		     m.F_Lectura = curMEDIA.F_GenLect
		     K = K + 1
		     m.Promedio = (m.Promedio + m.Consumo )/ K
		     SELECT Planilla
		     GATHER MEMVAR
		     
		     SELECT curMEDIA
		     SKIP 1
		     I = I - 1
		     IF SOCIOPER.ID_Socio = curMedia.ID_Socio && SOCIOS.ID_Socio = curMedia.ID_Socio
		        &mCOD = m.F_Lectura - curMEDIA.F_GenLect
		        IF I = 2
					m.porcError = (m.consumo / IIF(curMEDIA.Consumo=0,1,curMEDIA.Consumo) -1)*100
				ENDIF
		        
		     ELSE
		        &mCOD =  30
		     ENDIF
		     DO CASE
		     	CASE m.porcError >= 100
		        	m.Prioridad = 1         
		        CASE m.porcError < 100
		          	m.Prioridad = 2                       
		        CASE m.Consumo < 15
		          	m.Prioridad = 4
		        CASE m.ID_MediEst > 0
		          	m.Prioridad = 3
		        OTHERWISE      
		          	m.Prioridad = 5                      
		     ENDCASE
		     
		     SELECT PLANILLA
		     GATHER MEMVAR
		  ENDDO
		  SELECT TEMPORAL
		ENDSCAN
		
		SELECT PLANILLA
		DEFINE WINDOW winESP NONE;
			TITLE "Planilla de Inconsistencia de lectura...";
			HALFHEIGHT			;
		    NOCLOSE NOGROW NOZOOM;	    
			AT 0 ,0;
		  	SIZE 30, 180 FONT "MS Sans Serif",8  ;
		  	COLOR SCHEME 8	

		MOVE WINDOW winESP CENTER
		PUSH KEY CLEAR
		BROW	WINDOW winESP

		RELEASE WINDOW winESP
		SELECT PLANILLA
 
		oThisform.REPORTE("GENLECT3.FRX")
		*USE
		*SELECT curMedia
		*USE
		SELECT(  nArea )

		RETURN
	ENDPROC

	*********************************************************
	* Método........: [MAIN]ValidarLecturasTodos
	* Descripción...: Descripión de ValidarLecturasTodos
	* Fecha.........: 18-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Nota......... : Cambiado el nombres chek copia 17-07-2018.
	*********************************************************
	PROCEDURE ValidarLecturasTodos(tnID_GenFact AS Integer, tcCobro AS String, tdF_GenLect As Date,;
								   tlPorcLect_Usuario AS Boolean, tlValidarMinimo AS Boolean,;
								   tlVerAnormalidad2 AS Boolean, tlValidarAnorAjus AS Boolean,;
								   tlMostrarConsumoMenorFactorMinimo AS Boolean, tlMostrarProcesando AS Boolean,;
								   tnId_Socio AS Integer)
	&&BEGIN
		PRIVATE lcTipoConsumoNombre, lnTipoConsumo
		LOCAL lnArea, lnValido, lcError, lnId_MediEst, lnConsumoMinimo
		LOCAL lnId_MediEsS, lnConsumo, lnConsumoFac, lnLectAct
		LOCAL llAnorLect, llAjusLect, lcZonaRuta, lcErrorEstado, lnFila, lcRecTotal

		lnArea = SELECT()

		lcErrorEstado = ""	
		THIS.lMostrarConsumoMenorFactorMinimo = tlMostrarConsumoMenorFactorMinimo		
		THIS.lPorcLect_Usuario = tlPorcLect_Usuario
		THIS.lValidarMinimo = tlValidarMinimo
		THIS.lVerAnormalidad2 = tlVerAnormalidad2

	    THIS.nPorcLECT = 0.5 && Hay que cambiar por la variacion de consumo
	    IF USED("GENFACT")
	    	THIS.nPorcLECT = GENFACT.PorcLect
	    	lcZonaRuta = STRTRAN(STR(GENFACT.ID_Zona, 2) + STR(GENFACT.Ruta, 2), ' ', '0')
	    ENDIF

		THIS.ErrorMsg = ""
		lcFechaAnterior = oMySQL.FOX2SQL(pGlobal.Fecha-365)
		lcCurHistFact = THIS.DO_HISTOFACT(tdF_GenLect, 0)

		TRY
			IF(oMySQL.Tipo = 0)
				USE (&lcCurHistFact) IN 0 SHARED
			ENDIF
		CATCH TO loEx
			THIS.ErrorMsg = "Historico de Facturas no Disponible" + CHR(13) +;
							"Consulte Administrador" + CHR(13) + ;
							"Error : " + loEx.Message
		ENDTRY

		IF(NOT EMPTY(THIS.ErrorMsg))
			RETURN
		ENDIF

	  	IF NOT USED("MEDIESTA")
	  		oMySQL.GetTablaIndexada("MEDIESTA","MEDIESTA","*",THIS.DataSession)
	  	ENDIF
	  	IF NOT USED("CATECONS")
	  		oMySQL.GetTablaIndexada("CATECONS","CATECONS","*",THIS.DataSession)
	  	ENDIF

	  	&&CICLO PARA VALIDAR TODAS LAS LECTURAS DEL CURSOR TEMPORAL...
	  	lnFila = 0
	  	lcRecTotal = TRANSFORM(RECCOUNT("TEMPORAL"))
		SELECT TEMPORAL
		SCAN ALL
			lnFila = lnFila + 1
			lcRecNo =  ALLTRIM(STR(lnFila))  &&ALLTRIM(STR(RECNO("TEMPORAL"),10))
			IF (tlMostrarProcesando)
		 		WAIT WINDOW "Validando [ASOCIADO] : " + ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) + " - " + TEMPORAL.Cod_Socio + CHR(13) +;
							"Registro: " + lcRecNo + " de " + lcRecTotal NOWAIT 
			ENDIF
			llAjusLect = .F.
			llAnorLect = .F.
			IF NOT EMPTY(tnId_Socio)
				IF (TEMPORAL.ID_SOCIO = tnId_Socio)
					SET STEP ON
				ENDIF
			ENDIF
			IF tlValidarAnorAjus = .F.
				&&Chekamos si hay Ajus o Anor(Accion in []) para NO VALIDAR...
				SELECT curAjusLect
				SEEK TEMPORAL.ID_Socio
				llAjusLect = FOUND("curAjusLect")

				SELECT curAnorLect
				SEEK TEMPORAL.ID_Socio
				llAnorLect = FOUND("curAnorLect")
				IF ((llAjusLect = .F.) AND (llAnorLect = .F.))
				ELSE
					IF ((llAjusLect = .T.) OR (llAnorLect = .T.))
						IF (llAjusLect =.T.) 
							IF (curAjusLect.Accion != 3)
								&&la Accion debe ser diferente a [Informe/Nota]
								&&Accion = 1 or 2
								LOOP
							ENDIF
						ELSE
							LOOP
						ENDIF
					ENDIF
				ENDIF
			ENDIF
			SELECT TEMPORAL
			lcTipoConsumoNombre = ""
			lcVariacion = "00.00%"
			lcError = ""
			THIS.lMenorQueMinimo = .F.

			IF(TEMPORAL.ID_SOCIO = 1615)
				SET STEP ON
			ENDIF

			&&ASIGNAMOS SOLUCION EXCEPCIONAL A LAS  ANORMALIDADES SINLECTURA CON REGLA= PROMEDIO
			IF(TEMPORAL.ID_MediEst > 0) AND (TEMPORAL.Id_MediEs2 = 0)
				lnId_MediEsS = THIS.oReglaSolEx.TieneSolucionExcepcion(TEMPORAL.ID_MediEst)
				IF (lnId_MediEsS > 0) && HAY SOLUCION EXP
					&&SET STEP ON 
					WAIT WINDOW "Revalidando Lectura/Regla DE : " + TEMPORAL.COD_SOCIO NOWAIT
					lnLectAct = TEMPORAL.LectAct
					lnConsumo = TEMPORAL.Consumo
					lnConsumoFac = TEMPORAL.ConsumoFac
					THIS.oReglaSolEx.GetSolucionExcepcion(lnId_MediEsS, TEMPORAL.LectAnt, @lnLectAct,;
								   		 				  @lnConsumo, @lnConsumoFac, TEMPORAL.Media)
					REPLACE TEMPORAL.LectAct WITH lnLectAct
					REPLACE TEMPORAL.Consumo WITH lnConsumo
					REPLACE TEMPORAL.ConsumoFac WITH lnConsumoFac
					REPLACE TEMPORAL.Id_MediEs2 WITH lnId_MediEsS
				ENDIF
			ENDIF

			&&Se toma primero Id_MediEs2 SSI Paralect.Id_Media > 0
			IF pGlobal.ID_Media > 0
				lnId_MediEst  = IIF(TEMPORAL.Id_MediEs2 > 0 , TEMPORAL.Id_MediEs2, TEMPORAL.Id_MediEst)
			ELSE
				lnId_MediEst  = TEMPORAL.Id_MediEst
			ENDIF
			&&aDD: Add PAram ConumoMin
			IF NOT USED("CATEGORI")
		   		oMySQL.GetTablaIndexada("CATEGORI", "CATEGORI", "*", THIS.DataSession)
			ENDIF

			lnArea2 = SELECT()
			lnConsumoMinimo = 0
			SELECT CATEGORI
			SEEK TEMPORAL.ID_CATEG
			IF FOUND()
				lnConsumoMinimo = CATEGORI.ConsumoMin
			ENDIF 
			SELECT(lnArea2) 

			lnValido = THIS.ValidarLectura(TEMPORAL.LectAnt, TEMPORAL.LectAct, TEMPORAL.Consumo,;
										   TEMPORAL.Media, lnId_MediEst, ;
										   TEMPORAL.Id_Medidor, TEMPORAL.Id_Categ, TEMPORAL.ConsumoFac, lnConsumoMinimo)
			lcVariacion = ALLTRIM(STR(THIS.nPorcentajeDesviacion, 10, 2)) 

			lnEsNuevaInstalacion = THIS.EsInstalacionNueva(TEMPORAL.ID_MediEst, TEMPORAL.Id_Socio, tcCobro, @lcErrorEstado)

			IF INLIST(lnEsNuevaInstalacion, 0, 1)  
				*!*Add:03-05-2019, By: Ing. Alfonzo Salgado Flores, Nota: Para Intalaciones Nuevas
				lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
				lcError = "[" + lcTipoConsumoNombre + "] [Instalación Nueva]"
				SELECT TEMPORAL
				REPLACE TEMPORAL.Variacion WITH THIS.nPorcentajeDesviacion
				IF (lnEsNuevaInstalacion == 0)
					REPLACE TEMPORAL.Error WITH lcError
				ELSE
					REPLACE TEMPORAL.Error WITH lcErrorEstado
				ENDIF
			ELSE
				SELECT TEMPORAL
				lnEsCambioMedidor = THIS.EsCambioDeMedidor(lnId_MediEst, TEMPORAL.ID_Socio, @lcErrorEstado)
				IF INLIST(lnEsCambioMedidor, 0, 1)
					lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
					IF (lnEsCambioMedidor == 0)
						lcError = "[" + lcTipoConsumoNombre + "] [Cambio de Medidor]"
					ELSE
						lcError = lcErrorEstado
					ENDIF
					SELECT TEMPORAL
					REPLACE TEMPORAL.Variacion WITH THIS.nPorcentajeDesviacion
					REPLACE TEMPORAL.Error WITH lcError
				ELSE
					lnEsRegularizacion = THIS.EsRegularizacionBajaTemporal(lnId_MediEst, TEMPORAL.ID_Socio, @lcErrorEstado)
					IF INLIST(lnEsRegularizacion, 0, 1)
						lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
						IF (lnEsCambioMedidor == 0)
							lcError = "[" + lcTipoConsumoNombre + "] [Regularización Baja Temporal]"
						ELSE
							lcError = lcErrorEstado
						ENDIF
						SELECT TEMPORAL
						REPLACE TEMPORAL.Variacion WITH THIS.nPorcentajeDesviacion
						REPLACE TEMPORAL.Error WITH lcError
					ELSE
						IF(lnValido = 5)
							&&Add: 26-05-2023, By:ASF, Nota: Caso ConsumoFac < Consumo Sin Anormalidad
							REPLACE TEMPORAL.Variacion WITH THIS.nPorcentajeDesviacion
							lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)	
							lcError = "[Error] " + THIS.ErrorMsg
							REPLACE TEMPORAL.Error WITH lcError
						ELSE
							llSeValida = THIS.SeValida(TEMPORAL.Media, TEMPORAL.Consumo, TEMPORAL.ID_Categ)
							IF (llSeValida = .F.) AND (lnValido <> 0)
								SELECT TEMPORAL
								IF(THIS.lMostrarConsumoMenorFactorMinimo = .T.)
									lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)	
									lcError = "[Informativo][" + lcTipoConsumoNombre + "]"
									REPLACE TEMPORAL.Error WITH lcError
								ELSE
									IF(TEMPORAL.Consumo >= 0) AND (TEMPORAL.ConsumoFac = 0 ) AND (TEMPORAL.Id_MediEst > 0)
										&&Add: 26-05-2023, By:ASF, Nota: Caso ConsumoFac < Consumo Sin Anormalidad
										REPLACE TEMPORAL.Variacion WITH THIS.nPorcentajeDesviacion
										REPLACE TEMPORAL.Error WITH THIS.ErrorMsg
									ELSE
										REPLACE TEMPORAL.Error WITH ""
									ENDIF
								ENDIF 
							ELSE
								*Falta el caso cuando es Instalacion de Nuevo Medidor
								IF (THIS.nTipoConsumo > 1 )
									&&Aqui se debe discriminar si se validara el ConsumoBajo
									lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)	
									lcError = "[" + lcTipoConsumoNombre + "]"
									REPLACE TEMPORAL.Variacion WITH THIS.nPorcentajeDesviacion
								ENDIF 

								IF THIS.nTipoConsumo > 1 AND THIS.lPorcLect_Usuario = .F. AND (THIS.nTipoConsumo <> 6)
									IF lnValido = 0
										IF(TEMPORAL.Consumo >= 0) AND (TEMPORAL.ConsumoFac = 0 ) AND (TEMPORAL.Id_MediEst > 0) AND (lnConsumoMinimo > 0)
											&&Add: 26-05-2023, By:ASF, Nota: Caso ConsumoFac < Consumo Sin Anormalidad
											lcError = THIS.ErrorMsg
											IF EMPTY(lcError)	
												lcError = "[Error] [ConsumoFacturado < Consumo] Corregir ConsumoFacturado"
											ENDIF
										ELSE
											lcError = ""
										ENDIF
									ENDIF
								ENDIF

								IF lnValido <> 0
									IF ! EMPTY(lcError)
										lcError = lcError + " - " +  THIS.ErrorMsg 
									ELSE
										lcError = THIS.ErrorMsg 
									ENDIF 
								ENDIF
							
								SELECT TEMPORAL
								REPLACE TEMPORAL.MqM WITH THIS.lMenorQueMinimo
								IF (THIS.lValidarMinimo = .F. AND THIS.lMenorQueMinimo = .T.)
									lcError = "" && No Filtramos el Consumo < ConsumoMin
								ENDIF

								IF(THIS.lVerAnormalidad2 = .F.)
									IF (TEMPORAL.ID_MediEs2 > 0)
										lcError = "" && Se borra el error generado ya queno se quiere ver.
									ENDIF
								ENDIF
								REPLACE TEMPORAL.Error WITH lcError
							ENDIF
						ENDIF
					ENDIF
				ENDIF
				
			ENDIF
		ENDSCAN

		SELECT(lnArea)
	ENDPROC

	*Modificado......: Ing. Alfonzo Salgado Flores
	*Razon...........: Migracion incompleta, el ALIAS() LECTURA NO  existe. ver copia anterior de fecha 25-07-2018
	*FechaModifiaco..: 26-07-2018
	PROCEDURE ConsumoSoc()
		PRIVATE lcSQL
	    IF TEMPORAL.LECTACT = 0
	    	SELECT SOCIOS
	    	SEEK TEMPORAL.ID_Socio
	        SELECT TEMPORAL
	        m.Consumo   = SOCIOS.Consumo
	        m.Media_Ant = .T.
	        && Colocar Lamedia        
	        RETURN 
	    ENDIF

		CREATE CURSOR LecSocio ;
		( 	Id_Genfact	N(10),	;	
			lectant		n(6),;
			lectact		n(6),;
			consumo		n(10),;
			Media_ANT   l,;
			id_mediEst	n(2) )
	   
		lcSQL = "SELECT * " +;
				"  FROM GENLECT " +;
				" WHERE ID_SOCIO = "  + oMySQL.Fox2SQL(TEMPORAL.ID_Socio)
		oMySQL.Ejecutar(lcSQL, "LECTURA", THIS.DataSession)
	    SELECT LECTURA
	   	SCAN ALL
			IF ID_GenFact <> TEMPORAL.ID_GenFACT
				SCATTER MEMVAR
			 	SELECT LecSocio
			 	APPEND BLANK
			 	GATHER MEMVAR
		 	ENDIF
	   	 	SELECT LECTURA
	   	ENDSCAN

	   	m.LECT2 = TEMPORAL.LECTACT   
	  	m.n  = 1
	   	m.SumConsumo = 0
	   
	   	SELECT LecSocio
	   	GO BOTTOM
	   	DO WHILE LecSocio.LectANT = 0.00  AND NOT BOF()
		   	m.SumConsumo = m.SumConsumo + LecSocio.Consumo
		   	m.n =m.n +1	   
		   	SELECT LecSocio       
		   	SKIP -1
	   	ENDDO   
	   	IF EOF()
	   		GO TOP
	   	ELSE
		   	IF LecSocio.LectANT <> 0.00 
			   	m.SumConsumo = m.SumConsumo + LecSocio.Consumo
			   	m.n =m.n +1	   	   
		   	ENDIF
	   	ENDIF
	   
	    m.LECT1 = LecSocio.LECTANT
	    m.DifConsumo = m.Lect2 - m.Lect1
	    IF m.DifConsumo < m.SumConsumo
	    	m.Consumo = 0    
	    ELSE
		    m.Razon = m.DifConsumo/m.n
		    m.Razon1 = m.Razon * (m.n-1) 
		    IF m.Razon1 < m.SumConsumo
		    	m.Consumo = m.DifConsumo - m.Razon1 
		    ELSE
		    	m.Consumo = m.DifConsumo - m.SumConsumo
		    ENDIF
		ENDIF
	    USE
		SELECT TEMPORAL
	ENDPROC

	*********************************************************
	* Método........: VerDesviacion
	* Descripción...: Descripión de VerDesviacion
	* Fecha.........: ---------(comparar con copia (30-06-2017)) para fer diff.
	* Diseñador.....: Ing. Cesar Corvera Murakami
	* Implementador.: Ing. Cesar Corvera Murakami -> Etapa 1 : Fecha no registrada
	* Implementador.: Ing. Alfonzo Salgado Flores -> Etapa 2 : 18-07-2018
	*				  Refactorizando el Metodo....
	*********************************************************
	PROCEDURE VerDesviacion(tcCurHistFac AS String)
		LOCAL lnArea, lcSQL, lnId_Socio, lnMedia, lnConsumo
		LOCAL lnLimMin, lnLimMax, lnFactor, lnPorceDesv, lcResult
		lcResult = "Sin Verificar"
		lnId_Socio = TEMPORAL.ID_Socio
		lnConsumo = TEMPORAL.Consumo

		lnId_CategSoc = 0
		lnLimMin = 0
		lnLimMax = 0

		&& Seleccionamos la Categoria del Socio 
		lnId_CategSoc = TEMPORAL.Id_Categ

		&& Obtenemos el consumo promedio del lnId_Socio
		lnMedia = THIS.CalcularMedia(lnId_Socio, tcCurHistFac)
		IF lnMedia = 0
			RETURN "" &&Error Fatal(ojo con los socios nuevos!!!)
		ENDIF

		&& Seleccionamos Rango de Consumos Segun Categoria lnId_CategSoc
		lcSQL = "SELECT  * " +;
				"  FROM CATECONS " +;
				" WHERE " +;
				"  		Id_Categ = " + oMySQL.Fox2SQL(lnId_CategSoc) +;
				"   AND  INICIO <= " + oMySQL.Fox2SQL(lnMedia) +;
				"   AND " + oMySQL.Fox2SQL(lnMedia) + " <= FIN "
		oMySQL.Ejecutar(lcSQL, "curRango", THIS.DataSession)

		&& Obetemos el lnFactor para el caluculo de desviaciones
		IF (curRango.Variacion = 0.0)
		  	lnFactor = 0.65
		  	DO CASE
		  		CASE lnMedia <= 39
		  			lnFactor = 0.65
		  		CASE 40 <= lnMedia AND lnMedia<= 499
			        lnFactor = 0.35
		    	CASE 500 <= lnMedia AND lnMedia<= 999
			        lnFactor = 0.20
		    	CASE 1000 <= lnMedia AND lnMedia<= 99999
			        lnFactor= 0.10
		  	ENDCASE
		ELSE
			lnFactor = curRango.Variacion
		ENDIF 

		&& Calculamos los limites de consumo Maximo y Minimo mas su Porcentaje de Desviacion
		lnLimMin = (lnMedia - (lnMedia * lnFactor))
		lnLimMax = (lnMedia + (lnMedia * lnFactor))
		lnPorceDesv = 0
		IF (lnMedia > 0)
			lnPorceDesv = (((lnConsumo/ lnMedia) - 1) * 100)
		ENDIF
		&& WAIT WINDOW NOWAIT "LimMin=" + STR (lnLimMin) + " LimMax=" + STR (lnLimMax)

		&& Retornamos la desviacion significativa y el porcetaje de variacion
		&& Si LimMin y LimMax == 0, Entonces es su primera lectura
		IF ((lnLimMin > 0) OR (lnLimMax > 0))
			DO CASE
				CASE lnConsumo < lnLimMin
					lcResult = " DS=CB("+ALLTRIM(STR(lnPorceDesv))+"%)"
			    CASE lnConsumo > lnLimMax
			        lcResult = " DS=CA("+ALLTRIM(STR(lnPorceDesv))+"%)"
			    OTHERWISE
			    	lcResult = " NORMAL("+ALLTRIM(STR(lnPorceDesv))+"%)"
			ENDCASE
		ENDIF 
		RETURN lcResult
	ENDPROC
	
	FUNCTION Existe(tcCobro AS String, tnZona AS Integer, tnRuta AS Integer ) AS Boolean
		LOCAL lnArea  
		lnArea = SELECT()
		lcSQL = "SELECT 1 AS Exito "+ ;
				"  FROM GENFACT "+ ;
				" WHERE ID_Zona = " + oMySQL.FOX2SQL(tnZona) + ;
				"   AND Ruta = " + oMySQL.FOX2SQL(tnRuta) + ;
				"   AND Cobro = " + oMySQL.FOX2SQL(tcCobro) 
		oMySQL.Ejecutar(lcSQL,"cExiste",THIS.DataSession)
		lbRetorno = cExiste.Exito = 1
		USE
		SELECT(lnArea)
		RETURN lbRetorno
	ENDFUNC 
	
	*********************************************************
	* Método........: UpdateFrec
	* Descripción...: Descripión de UpdateFrec
	* Fecha..........: 23-06-2018
	* Diseñador.....: Ing. Cesar Corvera
	* Implementador..: Ing. Mary Luz Mullisaca
	*********************************************************
	PROCEDURE UpdateFrec(tcCobroIni AS String, tcCobroFin AS String, tlTodo AS Logical, toThisForm AS Form)
		TRY
			lnArea = SELECT()
			IF tlTodo = .F.
			    lcCobroIni = tcCobroIni
			    lcCobroFin = tcCobroFin
			    *SET EXCLUSIVE OFF        
				lcSQL = " SELECT * "+ ;
			      		"	FROM GENLECT "+;
			     		"  WHERE ALLTRIM(COBRO) >= " + oMySQL.FOX2SQL(lcCobroIni) +;
			       		"	 AND ALLTRIM(COBRO) <= " + oMySQL.FOX2SQL(lcCobroFin) +;
			       		"	 AND NOT EMPTY(Cobro)" +;
			       		"	 AND LEN(ALLTRIM(COBRO)) = 7 "+;
			       		"	 AND Id_MediEst > 0 "
				oMySQL.Ejecutar(lcSQL, "cLecturas", THIS.DataSession)
			ELSE
			    lcSQL = " SELECT * "+;
			      		"	FROM GENLECT "+;
			     		"  WHERE Id_MediEst > 0 "
				oMySQL.Ejecutar(lcSQL, "cLecturas", THIS.DataSession)
			ENDIF

			lcSQL = " SELECT Id_MediEst, COUNT(*) AS Cantidad "+ ;
			  		"	FROM cLecturas "+;
			 		"  GROUP BY Id_MediEst "+;
			 		"  ORDER BY 2 DESC "
			oMySQL.EjecutarCursor(lcSQL, "cLecAgru", THIS.DataSession)
			
			lcSQL = " SELECT SUM(Cantidad) AS Total "+;
			  		"	FROM cLecAgru "
			oMySQL.EjecutarCursor(lcSQL, "cTotalAnor", THIS.DataSession)
			
			oMySQL.GetTablaIndexada("MEDIESTA","MEDIESTA","ID_Mediest, Nomb_Medie, Frecuencia", THIS.DataSession)

			*RESETEO DE TODAS LAS FRECUENCIAS A CERO
			REPLACE FRECUENCIA WITH 0 ALL 

			SELECT cLecAgru
			INDEX ON  Id_MediEst TO Id_MediEst ADDITIVE

			SCAN ALL
			    SELECT MEDIESTA
			    SEEK cLecAgru.Id_MediEst
			    IF FOUND()
			        REPLACE Frecuencia WITH ROUND((cLecAgru.Cantidad / cTotalAnor.Total) * 100 ,2)
					toThisForm.WAIT_WINDOW("Anormalidad "+ ALLTRIM(MEDIESTA.Nomb_Medie) +" Frecuencia "+ ALLTRIM(STR(Frecuencia,10,2)))
			    ENDIF
			ENDSCAN
			
			oMySQL.ActualizarDatosKeys("MEDIESTA", "MEDIESTA", "ID_Mediest", THIS.DataSession)
			
			USE IN SELECT("cLecAgru")
			USE IN SELECT("cLecturas")
			USE IN SELECT("cTotalAnor")
			USE IN SELECT("MEDIESTA")
			toThisForm.WAIT_WINDOW("Actualizacion de Frecuencias de Anormalidades, Finalizado Correctamente...")
		CATCH TO oException
			cMensaje = [  Procedure: GenLect.UpdateFrec()] + THIS._Enter +[  ]+ lcSQL
			oError.Guardar(oException, cMensaje)
			WAIT WINDOW oException.Message 
		ENDTRY
		SELECT(lnArea)
		RETURN 0
	ENDPROC

	*********************************************************
	* Método........: AplicarRegla
	* Return........: .F.
	* Descripción...: Descripción de AplicarRegla
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION AplicarRegla(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
						  tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, lnReglaAAplicar
		TRY
			THIS.ErrorMsg = ""
			lcSQL = "SELECT * FROM MEDIDOR WHERE ID_MEDIDOR = " + oMySQL.Fox2SQL(tnId_Medidor)
			oMySQL.Ejecutar(lcSQL, "MEDIDOR_", THIS.DataSession)
			IF RECCOUNT("MEDIDOR_") > 0
				THIS.nFinMedidor = MEDIDOR_.FINMEDIDOR
			ELSE
				THIS.nFinMedidor = 0
			ENDIF
			lnReglaAAplicar = THIS.Get_TipoReglaAAplicar(tnId_MediEst)
			IF  (tnId_MediEst = 0)
				THIS.ReglaNombre = "APLICAR LECTURA ACTUAL" 
            	THIS.Aplicar_ConsumoNormal(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
			ELSE
	          	DO CASE &&Falta relacionar con la Clase:ReglaLectura.[REGLA]
					CASE lnReglaAAplicar = 1
						THIS.ReglaNombre = "APLICAR LECTURA PENDIENTE"
						THIS.Aplicar_LecturaPendiente(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 2
						THIS.ReglaNombre = "APLICAR LECTURA ACTUAL" 
	                	THIS.Aplicar_LecturaActual(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 3
						THIS.ReglaNombre = "APLICAR FIN DE CICLO"
						THIS.Aplicar_FinDeCiclo(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 4
						THIS.ReglaNombre = "APLICAR CONSUMO PROMEDIO"
						THIS.Aplicar_ConsumoPromedio(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 5
						THIS.ReglaNombre = "APLICAR MEDIDOR VOLCADO"
						THIS.Aplicar_MedidorVolcado(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 6
						THIS.ReglaNombre = "APLICAR CONSUMO ASIGNADO"
						THIS.Aplicar_ConsumoAsignado(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 7
						THIS.ReglaNombre = "APLICAR AJUSTE LECTURA"
						&&THIS.Aplicar_AjusteLectura(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 8
						THIS.ReglaNombre = "APLICAR INSTALACION NUEVA"
						THIS.Aplicar_InstalacionNueva(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 9
						THIS.ReglaNombre = "APLICAR CAMBIO DE MEDIDOR"
						THIS.Aplicar_CambioDeMedidor(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
					CASE lnReglaAAplicar = 10
						THIS.ReglaNombre = "APLICAR REGULARIZACION BAJA TEMPORAL"
						THIS.Aplicar_RegularizacionBajaTemporal(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
				ENDCASE
			ENDIF
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = "  ProcedureInitial: GenLect.AplicarRegla()"
			oError.Guardar(loEx, lcLog)
		ENDTRY
		RETURN lnReglaAAplicar
	ENDFUNC
	
	*********************************************************
	* Método........: Get_TipoReglaAAplicar
	* Return........: MediEsta.Regla
	* Descripción...: Descripción de Get_TipoReglaAAplicar
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION Get_TipoReglaAAplicar(tnId_MediEst AS Integer) AS Integer
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, lnID
		lnArea = SELECT()
		lnID = 0
		SET DATASESSION TO THIS.DataSession &&Importante...
		TRY
			THIS.AnormalidadID = tnId_MediEst
			IF NOT USED("MEDIESTA")
				oMySQL.GetTablaIndexada("MEDIESTA", "MEDIESTA", "*", THIS.DataSession)
			ENDIF
			SELECT MEDIESTA
			SEEK tnId_MediEst
			IF(FOUND())
				lnID = MEDIESTA.Regla
				THIS.AnormalidadNombre = MEDIESTA.Nomb_MediE
			ELSE
				THIS.AnormalidadNombre = "Sin Anormalidad"
			ENDIF
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = "     Metodo..: GenLect.Get_TipoReglaAAplicar( tnId_MediEst : " + STR(tnId_MediEst,10) + ") "
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
		RETURN lnID
	ENDFUNC
	
	*********************************************************
	* Método........: Aplicar_ConsumoNormal
	* Descripción...: Descripión de Aplicar_ConsumoNormal
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_ConsumoNormal(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
								 	tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY
			THIS.nLectAct = tnLectAct
			THIS.nLectAnt = tnLectAnt
			THIS.nConsumo = tnLectAct - tnLectAnt
			THIS.nConsumoFac = THIS.nConsumo
			THIS.nMedia = tnMedia
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = [  Metodo...: GenLect.Aplicar_ConsumoNormal()] + THIS._Enter
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC
	

	*********************************************************
	* Método........: Aplicar_ConsumoPromedio
	* Descripción...: Descripión de Aplicar_ConsumoPromedio
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_ConsumoPromedio(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
									  tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY
			THIS.nLectAct = tnLectAnt
			THIS.nLectAnt = tnLectAnt
			THIS.nConsumo = 0
			THIS.nConsumoFac = tnMedia
			THIS.nMedia = tnMedia
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = [  Metodo...: GenLect.Aplicar_ConsumoPromedio()] 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC
	
	*********************************************************
	* Método........: Aplicar_LecturaPendiente
	* Descripción...: Descripión de Aplicar_LecturaPendiente
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_LecturaPendiente(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
									   tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY
			THIS.nLectAct = 0 &&No se coloca lectura por que esta en pendiente
			THIS.nLectAnt = tnLectAnt
			THIS.nConsumo = 0
			THIS.nConsumoFac = 0
			THIS.nMedia = tnMedia
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = [  Metodo...: GenLect.Aplicar_LecturaPendiente()] + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	*********************************************************
	* Método........: Aplicar_LecturaActual
	* Descripción...: Descripión de Aplicar_LecturaActual
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_LecturaActual(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
								 	tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY
			THIS.nLectAct = tnLectAct
			THIS.nLectAnt = tnLectAnt
			THIS.nConsumo = tnLectAct - tnLectAnt
			THIS.nConsumoFac = THIS.nConsumo
			THIS.nMedia = tnMedia
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = "  ProcedureInitial: GenLect.Aplicar_LecturaActual()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC
	
	*********************************************************
	* Método........: Aplicar_FinDeCiclo
	* Descripción...: Descripión de Aplicar_FinDeCiclo
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_FinDeCiclo(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
								 tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		LOCAL lnLectMax
		TRY
			lnArea = SELECT()
			THIS.nLectAct = tnLectAct
			THIS.nLectAnt = tnLectAnt
			THIS.nMedia = tnMedia
			
			IF THIS.nFinMedidor > 0
				lnLectMax = THIS.nFinMedidor && SIN ES 0 o Vacio enviar el error al formulario UI
				THIS.ErrorMsg = IIF(lnLectMax > 0 ,"","Medidor = "+ STR(tnId_Medidor,10) +;
									" : Fin Medidor No Definido ")
	            THIS.nConsumo = (lnLectMax - THIS.nLectAnt) + THIS.nLectAct + 1
				THIS.nConsumoFac = THIS.nConsumo
	        ENDIF
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = [ Metodo...: GenLect.Aplicar_FinDeCiclo()] + THIS._Enter
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC
	
	*********************************************************
	* Método........: Aplicar_MedidorVolcado
	* Descripción...: Descripión de Aplicar_MedidorVolcado
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_MedidorVolcado(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
									 tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		LOCAL lnLectMax
		TRY
			lnArea = SELECT()
			THIS.nLectAct = tnLectAct
			THIS.nLectAnt = tnLectAnt
			THIS.nMedia = tnMedia
			THIS.nConsumo = THIS.nLectAnt - THIS.nLectAct
			THIS.nConsumoFac = THIS.nConsumo
			IF THIS.nFinMedidor > 0
				lnLectMax = THIS.nFinMedidor && SIN ES 0 o Vacio enviar el error al formulario UI
				THIS.ErrorMsg = IIF(lnLectMax > 0 ,"","Medidor = "+ STR(tnId_Medidor,10) +;
									" : Fin Medidor No Definido ")
				IF (THIS.nConsumo < 0) && Caso 1 [Volcado en LimiteMax(99999)]
	            	THIS.nConsumo = (lnLectMax - THIS.nLectAct) + THIS.nLectAnt + 1
	            ELSE
	             	IF (THIS.nConsumo > 0) && Caso 2 [Volcado Antes del Limite]
	                	THIS.nConsumo = THIS.nLectAnt - THIS.nLectAct
	                ENDIF
	            ENDIF	
				THIS.nConsumoFac = THIS.nConsumo
	        ENDIF
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = [  ProcedureInitial: GenLect.Aplicar_MedidorVolcado()] + THIS._Enter +[  ]+ [VariableLog]
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC
	
	*********************************************************
	* Método........: Aplicar_ConsumoAsignado
	* Descripción...: Descripión de Aplicar_ConsumoAsignado
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_ConsumoAsignado(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
									 tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY &&falta implementar, se esta copiando de aplicar regla normal
			THIS.nLectAct = tnLectAct
			THIS.nLectAnt = tnLectAnt
			THIS.nConsumo = tnLectAct - tnLectAnt
			THIS.nConsumoFac = THIS.nConsumo
			THIS.nMedia = tnMedia
		CATCH TO loEx
			lcLog = [  ProcedureInitial: GenLect.Aplicar_ConsumoAsignado()] + THIS._Enter +[  ]+ [VariableLog]
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC
			
	*********************************************************
	* Método........: Aplicar_InstalacionNueva
	* Descripción...: Descripión de Aplicar_InstalacionNueva
	* Fecha.........: 02-05-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_InstalacionNueva(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
									   tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY 
			IF (tnLectAct < tnLectAnt) &&No Se Permite Negativo
				&&Queda pendiente si se va calcular el consumo negativo con ABS()
				THIS.nLectAct = tnLectAnt
				THIS.nLectAnt = tnLectAnt
				THIS.nConsumo = 0
				THIS.nConsumoFac = 0
			ELSE
				THIS.nLectAct = tnLectAct
				THIS.nLectAnt = tnLectAnt
				THIS.nConsumo = tnLectAct - tnLectAnt
				THIS.nConsumoFac = THIS.nConsumo
			ENDIF 
			THIS.nMedia = tnMedia
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.Aplicar_InstalacionNueva()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	********************************************************
	* Método........: CambioDeMedidor
	* Descripción...: Descripión de CambioDeMedidor
	* Fecha.........: 24-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_CambioDeMedidor(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
									   tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY 
			IF (tnLectAct < tnLectAnt) &&No Se Permite Negativo
				&&Queda pendiente si se va calcular el consumo negativo con ABS()
				THIS.nLectAct = tnLectAnt
				THIS.nLectAnt = tnLectAnt
				THIS.nConsumo = 0
				THIS.nConsumoFac = 0
			ELSE
				THIS.nLectAct = tnLectAct
				THIS.nLectAnt = tnLectAnt
				THIS.nConsumo = tnLectAct - tnLectAnt
				THIS.nConsumoFac = THIS.nConsumo
			ENDIF 
			THIS.nMedia = tnMedia
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.CambioDeMedidor()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	********************************************************
	* Método........: Aplicar_RegularizacionBajaTemporal
	* Descripción...: Descripión de Aplicar_RegularizacionBajaTemporal
	* Fecha.........: 24-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_RegularizacionBajaTemporal(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
									   tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY 
			IF (tnLectAct < tnLectAnt) &&No Se Permite Negativo
				&&Queda pendiente si se va calcular el consumo negativo con ABS()
				THIS.nLectAct = tnLectAnt
				THIS.nLectAnt = tnLectAnt
				THIS.nConsumo = 0
				THIS.nConsumoFac = 0
			ELSE
				THIS.nLectAct = tnLectAct
				THIS.nLectAnt = tnLectAnt
				THIS.nConsumo = tnLectAct - tnLectAnt
				THIS.nConsumoFac = THIS.nConsumo
			ENDIF 
			THIS.nMedia = tnMedia
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.Aplicar_RegularizacionBajaTemporal()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	*********************************************************
	* Método........: Aplicar_AnormalidadLectura
	* Descripción...: Descripión de Aplicar_AnormalidadLectura
	* Fecha.........: 04-06-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_AnormalidadLectura(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
									   tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY 
			IF (tnLectAct < tnLectAnt) &&No Se Permite Negativo
				&&Queda pendiente si se va calcular el consumo negativo con ABS()
				THIS.nLectAct = tnLectAnt
				THIS.nLectAnt = tnLectAnt
				THIS.nConsumo = 0
				THIS.nConsumoFac = 0
			ELSE
				THIS.nLectAct = tnLectAct
				THIS.nLectAnt = tnLectAnt
				THIS.nConsumo = tnLectAct - tnLectAnt
				THIS.nConsumoFac = THIS.nConsumo
			ENDIF 
			THIS.nMedia = tnMedia
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.Aplicar_InstalacionNueva()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	*********************************************************
	* Método........: Aplicar_SeValidaNo
	* Descripción...: Descripión de Aplicar_SeValidaNo
	* Fecha.........: 14-05-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE Aplicar_SeValidaNo(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
								 	tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer)
	&&BEGIN
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		TRY
			&&Queda pendiente si se va calcular el consumo negativo con ABS()
			THIS.nLectAct = tnLectAct
			THIS.nLectAnt = tnLectAnt
			THIS.nConsumo = tnLectAct - tnLectAnt
			THIS.nConsumoFac = THIS.nConsumo
			THIS.nMedia = tnMedia
		CATCH TO loEx
			THIS.ErrorMsg = loEx.Message
			lcLog = "  ProcedureInitial: GenLect.Aplicar_SeValidaNo()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
	ENDPROC

	*********************************************************
	* Método........: ValidarLectura
	*			      [MAIN]		
	* Return........: Integer
	* Descripción...: Descripción de ValidarLectura
	* Fecha.........: 12-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Migrado.......: De .Net 2008 (LecturaMovil)
	* Log...........: 06-06-2019, Nota: ConsumoFac comparar con Media para el caso de Promediados que no aplica la parte UI(SinValidar)
	*********************************************************
	FUNCTION ValidarLectura(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
							tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer,;
							tnId_Categ AS Integer, tnConsumoFac AS Integer, tnConsumoMinimo AS Integer) AS Integer
	&&BEGIN
		LOCAL lnResult
        lnResult = 0
        *, tlMostrarConsumoMenorFactorMinimo AS Boolean
        *THIS.lMostrarConsumoMenorFactorMinimo = tlMostrarConsumoMenorFactorMinimo
        IF NOT USED("CATECONS")
			oMySQL.GetTablaIndexada("CATECONS", "CATECONS", "*", THIS.DataSession)
		ENDIF
		IF NOT USED("CATEGORI")
		   	oMySQL.GetTablaIndexada("CATEGORI", "CATEGORI", "*", THIS.DataSession)
		ENDIF
		IF NOT USED("MEDIDOR")
			THIS.GetMedidores()
		ENDIF 



        THIS.cTipoConsumoNombre = "SI TIPO CONSUMO..."
        THIS.nMedia = tnMedia
        THIS.nLectAnt = tnLectAnt
        THIS.nErrorAdevertencia = 0
        THIS.nError = THIS.GetTipoComportamiento(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor)
        IF (THIS.nError != 0)
            RETURN THIS.nError && Error al identificar el comportamiento del medidor
        ENDIF
         
        &&#region Caso: LecturaAnterior >= 0 // LecturaActual = 0  MediEsta = ValorSeleccionado
        IF ((THIS.nLectAnt >= 0) AND (tnLectAct = 0) AND (tnId_MediEst > 0))
            lnResult = THIS.AnormalidadCorrecta(tnId_MediEst, THIS.oTipoConsumo.SinLectura, THIS.oTipoComportamiento.NoLecturable) 
            &&oMedidorInfo.tipoComportamiento)
            IF (lnResult = 0)
                THIS.oMedidorInfo.tipoComportamiento = THIS.oTipoComportamiento.NoLecturable
                THIS.oMedidorInfo.tipoConsumo = THIS.oTipoConsumo.SinLectura
                THIS.cTipoConsumoNombre = THIS.oTipoConsumo.GetNombre(THIS.oTipoConsumo.SinLectura)
                RETURN lnResult
            ELSE
                IF (THIS.oMedidorInfo.tipoConsumo = THIS.oTipoConsumo.ConsumoNegativo)
                    THIS.nTipoConsumo = THIS.oMedidorInfo.tipoConsumo
                ELSE
                	&&	PROCESO SUPER IMPORTANTE PARA TOMAR EL TIPO DE CONSUMO Y USARLO EN LOS SIGUIENTES PASOS...
                    THIS.nTipoConsumo =  THIS.GetTipoConsumo(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor, tnId_Categ)
                    THIS.cTipoConsumoNombre = THIS.oTipoConsumo.GetNombre(THIS.nTipoConsumo)
                ENDIF 

                lnResult = THIS.AnormalidadCorrecta(tnId_MediEst, THIS.nTipoConsumo,;
                									THIS.oMedidorInfo.tipoComportamiento )
                IF (lnResult <> 0)
                    THIS.ErrorMsg = "Anormalidad Seleccionada, No Valida!" + ;
                    				ALLTRIM(STR(THIS.nTipoConsumo,2)) + " != "+  STR(THIS.oMedidorInfo.tipoConsumo,2)
                    RETURN lnResult
                ENDIF
                RETURN lnResult
            ENDIF
        ENDIF 
        IF ((THIS.oMedidorInfo.tipoConsumo = THIS.oTipoConsumo.ConsumoNegativo) AND ;
        	 (tnLectAct == 0) AND (THIS.nLectAct > 0))
            THIS.nErrorAdevertencia = 1
            THIS.ErrorMsg = "LecturaActual = 0, Seguro que es una Lectura valida? o es un Predio que no se puede Lecturar? en ese caso debe Seleccionar Anormalidad Correcta"
        ENDIF

        IF (THIS.oMedidorInfo.tipoConsumo = THIS.oTipoConsumo.ConsumoNegativo)
            THIS.nTipoConsumo = THIS.oMedidorInfo.tipoConsumo
            &&Test     
            &&THIS.nTipoConsumo = THIS.GetTipoConsumo(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor, tnId_Categ)
        ELSE
        	&&	PROCESO SUPER IMPORTANTE PARA TOMAR EL TIPO DE CONSUMO Y USARLO EN LOS SIGUIENTES PASOS...
            THIS.nTipoConsumo = THIS.GetTipoConsumo(tnLectAnt, tnLectAct, tnConsumo, tnMedia, tnId_MediEst, tnId_Medidor, tnId_Categ)
        ENDIF
        
        THIS.cTipoConsumoNombre = THIS.oTipoConsumo.GetNombre(THIS.nTipoConsumo)

        IF (THIS.oMedidorInfo.tipoComportamiento = THIS.oTipoComportamiento.Lecturable)
        	&&Actualizamos MedidorInfo
            THIS.oMedidorInfo.tipoConsumo = THIS.nTipoConsumo
            THIS.oMedidorInfo.Consumo =  THIS.nConsumo
        ENDIF

        THIS.nDesviacionSignificativa = ((THIS.nTipoConsumo = THIS.oTipoConsumo.ConsumoBajo) OR;
        								 (THIS.nTipoConsumo = THIS.oTipoConsumo.ConsumoAlto))

		&&VERIFICAMOS SI TIENE REGLA (LECTURA PENDIENTE A FACTURAR)
		lnResult = THIS.EsCasoLecturaPendienteAFacturar(tnId_MediEst, THIS.nTipoConsumo, THIS.nLectAnt, THIS.nLectAct, THIS.nConsumo, THIS.nMedia, tnConsumoFac, tnConsumoMinimo)
		if (lnResult = 0)
			RETURN lnResult
		ENDIF 

        IF (tnId_MediEst = 0)
            IF (THIS.nTipoConsumo = THIS.oTipoConsumo.ConsumoNormal)
                lnResult = 0 && Correcto dado que Anormalidad no existe entonces consumo es normal
                && Add: 26-05-2023, By: ASF, Nota: ConsumoFac < Consumo
                IF(tnConsumo > 0) AND (tnConsumoFac < tnConsumo) AND (tnConsumoMinimo > 0)
                	lnResult = 5
                	THIS.ErrorMsg = "[ConsumoFacturado < Consumo] Corregir ConsumoFacturado"
                ENDIF
            ELSE
            	&&/result = tipoConsumo // existencia de consumo no normal que implica una anormalidad posible.
                && Caso Base :28-07-11
                IF ((THIS.nLectAnt = 0) AND (THIS.nLectAct = 0) AND;
                    (THIS.nTipoConsumo = THIS.oTipoConsumo.ConsumoCero))
                    lnResult = 2
                    THIS.ErrorMsg = "Ambas Lecturas Son [0] Revise el historico y/o Consulte con Administrador]"
                ELSE &&Agregado 27-07-11
                	IF ((tnConsumo <= THIS.nConsumoMinimo) AND;
                		(tnConsumo >= 0) AND (THIS.nMedia <= THIS.nConsumoMinimo))
                    	lnResult = 0 
                    	&& Si el cosumo es <= al consumo minimo se lo hace pasar sin anormalidad y con consumo normal
                	ELSE
	                    lnResult = 2
	                    THIS.nConsumo = THIS.oMedidorInfo.Consumo
	                    IF( THIS.nErrorAdevertencia == 0)
	                        THIS.ErrorMsg = "Incompatible Anormalidad, Seleccione Anoramalidad Valida"
	                    ENDIF
                    ENDIF
                ENDIF
            ENDIF
        ELSE
        	lnResult = THIS.EsCasoSinLectura_AplicarPromedio(tnId_MediEst, THIS.nTipoConsumo, THIS.nLectAnt, THIS.nLectAct, THIS.nConsumo, THIS.nMedia, tnConsumoFac, tnConsumoMinimo )
        	IF(lnResult <> 4)												 
         		IF(lnResult > 0)
         			&&5.- Verificamos si la anormalidad seleccionada es Correcta
            		lnResult = THIS.AnormalidadCorrecta(tnId_MediEst, THIS.nTipoConsumo, THIS.oMedidorInfo.tipoComportamiento )
            	ENDIF
            ENDIF
       	ENDIF

        RETURN lnResult
    ENDFUNC

	*********************************************************
	* Método........: GetTipoComportamiento
	* Return........: Integer
	* Descripción...: Descripción de GetTipoComportamiento
	* Fecha.........: 11-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Migrado.......: De .Net 2008 (LecturaMovil)
	*********************************************************
	FUNCTION GetTipoComportamiento(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
								   tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer) AS Integer
	&&BEGIN
		LOCAL lnResult, lnFinMedidor, lnQmaxMes, lnlnFactorAnterior,  lnlnFactorActual
		LOCAL lnDiflnFactores, lnlnFactorProximidadIzq, lnlnFactorProximidadDer
		LOCAL lnlnFactorLimiteConsumoNegativo, lnlnFactorLimiteConsumoPositivo
		LOCAL lnArea

		lnArea = SELECT()
        lnResult = 0
        lnFinMedidor = 0
        lnQmaxMes = 0.00 && lnQmaxMes = CaudalMes
        lnlnFactorAnterior = 0
        lnlnFactorActual = 0
        lnDiflnFactores = 0
        lnlnFactorProximidadIzq = 0
        lnlnFactorProximidadDer = 0
        lnlnFactorLimiteConsumoNegativo = 0
        lnlnFactorLimiteConsumoPositivo = 0

    	lcSQL = "SELECT * FROM MEDIDOR WHERE ID_MEDIDOR = " + oMySQL.Fox2SQL(tnId_Medidor)
		oMySQL.EjecutarCursor(lcSQL, "MEDIDOR_", THIS.DataSession)

		IF RECCOUNT("MEDIDOR_") > 0
			THIS.nFinMedidor = MEDIDOR_.FINMEDIDOR
			lnId_DiamMed = MEDIDOR_.ID_DIAMMED
		ELSE
			THIS.nFinMedidor = 0
			lnResult = -11 &&Error No Existe Registro
            THIS.ErrorMsg = "Medidor [No hay Registro] para ID_Medidor = " + STR(MEDIDOR_.Id_Medidor)
            RETURN lnResult
		ENDIF
		
		lcSQL = "SELECT * FROM DIAMACOM WHERE ID_DIAMACO = " + oMySQL.Fox2SQL(lnId_DiamMed)
        oMySQL.EjecutarCursor(lcSQL, "DIAMACOM_", THIS.DataSession)

        THIS.nLectAnt = tnLectAnt
        THIS.nLectAct = tnLectAct
        USE IN MEDIDOR_
        IF (lnResult < 0)
            RETURN lnResult
        ENDIF
        IF RECCOUNT("DIAMACOM_") > 0
            lnQmaxMes = DIAMACOM_.QmaxMes
        ELSE
            lnResult = -11
            THIS.ErrorMsg = "Diametro[No hay Registros]"
        ENDIF
        USE IN DIAMACOM_
        IF (lnResult < 0)
            RETURN lnResult
        ENDIF
        IF (lnQmaxMes == 0)
            THIS.ErrorMsg = "DiamAcom.QmaxMes[No Esta Definido o no hay registros]"
            RETURN -1
        ENDIF

        IF (THIS.nLectAct < 0) &&Valor Asigando a this.tnLectAct en Get_TipoConsumo()
            RETURN lnResult &&Caso improbable dado que nunca se colocara en el dispositivo un valor NEGATIVO
        ENDIF
        TRY
            lnlnFactorAnterior = THIS.nFinMedidor - THIS.nLectAnt
            lnlnFactorActual = THIS.nFinMedidor - THIS.nLectAct
            lnDifFactores = lnlnFactorAnterior - lnlnFactorActual
            lnlnFactorProximidadIzq = (lnDifFactores / THIS.nFinMedidor) * 100
            lnlnFactorProximidadDer = 100 - ABS(lnlnFactorProximidadIzq)
            lnlnFactorLimiteConsumoPositivo = ((lnQmaxMes / THIS.nFinMedidor) * 100)
            lnlnFactorLimiteConsumoNegativo = 100 - lnlnFactorLimiteConsumoPositivo

            THIS.oMedidorInfo.Consumo = THIS.nLectAct - THIS.nLectAnt && Temporal(Inicial)
            THIS.oMedidorInfo.DifFactores = INT(lnDifFactores)
            THIS.oMedidorInfo.FactorActual = INT(lnlnFactorActual)
            THIS.oMedidorInfo.FactorAnterior = INT(lnlnFactorAnterior)
            THIS.oMedidorInfo.FactorProximidadIzq = lnlnFactorProximidadIzq
            THIS.oMedidorInfo.FactorProximidadDer = lnlnFactorProximidadDer
            THIS.oMedidorInfo.FactorLimiteConsumoPositivo = lnlnFactorLimiteConsumoPositivo
            THIS.oMedidorInfo.FactorLimiteConsumoNegativo = lnlnFactorLimiteConsumoNegativo
            THIS.oMedidorInfo.FinMedidor = THIS.nFinMedidor
            THIS.oMedidorInfo.LectActual = THIS.nLectAct
            THIS.oMedidorInfo.LectAnterior = THIS.nLectAnt
            THIS.oMedidorInfo.tipoConsumo = THIS.oTipoConsumo.ConsumoNegativo
            THIS.oMedidorInfo.QmaxMes = lnQmaxMes

            lnResult = 0

            &&region Medidor Fin de Ciclo
            IF ((ABS(lnlnFactorProximidadIzq) >= lnlnFactorProximidadDer) AND ;
            	(ABS(lnlnFactorProximidadIzq) >= lnlnFactorLimiteConsumoNegativo) AND ;
                (lnlnFactorProximidadDer <= lnlnFactorLimiteConsumoPositivo) AND ;
                (lnlnFactorProximidadIzq < 0) AND (lnlnFactorProximidadDer >= 0))
                THIS.oMedidorInfo.tipoComportamiento = THIS.oTipoComportamiento.FinDeCiclo &&Es Medidor Fin de Ciclo 
                THIS.oMedidorInfo.Consumo = THIS.nFinMedidor - THIS.nLectAnt + THIS.nLectAct + 1
            ELSE 
            	 && Es Medidor Volcado en Limite [E_Volcado]
            	IF ( (lnlnFactorProximidadIzq > lnlnFactorProximidadDer) AND ;
            		 (lnlnFactorProximidadIzq >= lnlnFactorLimiteConsumoNegativo) AND ;
                     (lnlnFactorProximidadDer <= lnlnFactorLimiteConsumoPositivo) AND ;
                     (lnlnFactorProximidadIzq > 0) AND (lnlnFactorProximidadDer >= 0))
                	THIS.oMedidorInfo.tipoComportamiento = THIS.oTipoComportamiento.VolcadoEnLimite 
                	THIS.oMedidorInfo.Consumo = INT(THIS.nFinMedidor - THIS.nLectAct + THIS.nLectAnt + 1)
            	ELSE 
            		&&Es Medidor Volcado Antes del Limite
            		IF ((ABS(lnlnFactorProximidadIzq) <= lnlnFactorLimiteConsumoPositivo) AND ;
                     	(lnlnFactorProximidadDer >= lnlnFactorLimiteConsumoNegativo) AND ;
                     	(lnlnFactorProximidadIzq < 0) AND (lnlnFactorProximidadDer >= 0) )
	                	THIS.oMedidorInfo.tipoComportamiento = THIS.oTipoComportamiento.VolcadoAntesDeLimite
	                	THIS.oMedidorInfo.Consumo = INT(THIS.nLectAnt - THIS.nLectAct )
            		ELSE 
                 		&& Medidor con consumo REAL(LECTURABLE)
            			IF ((lnlnFactorProximidadIzq <= lnlnFactorLimiteConsumoPositivo) AND ;
                     	   (lnlnFactorProximidadDer >= lnlnFactorLimiteConsumoNegativo) AND ;
                 		   (lnlnFactorProximidadIzq >= 0) AND (lnlnFactorProximidadDer >= 0))
                			THIS.oMedidorInfo.tipoComportamiento = THIS.oTipoComportamiento.Lecturable 
                			THIS.oMedidorInfo.tipoConsumo = THIS.oTipoConsumo.ConsumoNormal && Paracialmente
                			THIS.oMedidorInfo.Consumo = THIS.nLectAct - THIS.nLectAnt
            			ELSE
            				&&region Medidor con Consumo DESCONOSIDO[IRREAL]
                			THIS.oMedidorInfo.tipoComportamiento = THIS.oTipoComportamiento.Irreal
                			THIS.oMedidorInfo.tipoConsumo = THIS.oTipoConsumo.SinLectura &&OJO
            			ENDIF
            		ENDIF
            	ENDIF
            ENDIF	

        CATCH TO ex 
            lnResult = -1
            THIS.ErrorMsg = "Error al Procesar [Tipo de Comportamiento]"
            oError.Guardar(ex, "GetTipoComportamiento()")
        ENDTRY
        SELECT(lnArea)
        RETURN lnResult
    ENDFUNC

    *********************************************************
	* Método........: GetTipoConsumo
	* Return........: Integer
	* Descripción...: Descripción de GetTipoConsumo
	* Fecha.........: 09-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Migrado.......: De .Net 2008 (LecturaMovil)
	*********************************************************
	FUNCTION GetTipoConsumo(tnLectAnt AS Integer, tnLectAct AS Integer, tnConsumo AS Integer,;
						    tnMedia AS Integer, tnId_MediEst AS Integer, tnId_Medidor AS Integer,;
						    tnId_Categ AS Integer) AS Integer
	&&BEGIN
		LOCAL lnConsumoHistorico, lnLimiteConsumoMIN, lnLimiteConsumoMAX
        lnConsumoHistorico = tnMedia
        IF (tnId_Categ > 0) &&Categoria si o si debe existir si no es un error fatal
            THIS.nConsumoMinimo = THIS.GetConsumoMinimo(tnId_Categ)
        ENDIF
        THIS.nPorcentajeDesviacion = 0
        lnLimiteConsumoMIN = 0
        lnLimiteConsumoMAX = 0
        THIS.Get_LimitesConsumo(tnId_Categ, lnConsumoHistorico, @lnLimiteConsumoMIN, @lnLimiteConsumoMAX)
        THIS.nLectAct = tnLectAct
        THIS.nConsumo = tnLectAct - tnLectAnt
        THIS.nMedia = lnConsumoHistorico
        IF (lnConsumoHistorico == 0)
            THIS.nPorcentajeDesviacion = 0 &&Por Teroria de Consumo Modelo
        ELSE
            THIS.nPorcentajeDesviacion = ((THIS.nConsumo  / lnConsumoHistorico) - 1) * 100
        ENDIF
        &&region INTENTIFICACION DE TIPO CONSUMO
        &&Caso Base
        IF ((tnLectAnt = 0) AND (tnLectAct = 0))
            RETURN THIS.oTipoConsumo.ConsumoCero
        ENDIF
        IF (THIS.nConsumo  = 0)
            RETURN THIS.oTipoConsumo.ConsumoCero
        ENDIF
        IF (tnLectAct <= 0) && No hubo lectura
            RETURN THIS.oTipoConsumo.SinLectura
        ENDIF
        &&Ver si no es Medidor Volcado(Caso Especial)->
        IF (THIS.nConsumo  < 0)
            RETURN THIS.oTipoConsumo.ConsumoNegativo && Medidor Llego al limete, o Falla en Sentido
        ENDIF

        IF (THIS.nConsumo  < lnLimiteConsumoMIN)
        	&&Add: 29-08-2018  -> Para no ver registros de de consumo bajo < Categ.ConsumoMin
        	IF THIS.lValidarMinimo = .T.
        		RETURN THIS.oTipoConsumo.ConsumoBajo && Forma General...
        		THIS.lMenorQueMinimo = .T.
        	ELSE
        		IF THIS.nConsumo <= THIS.nConsumoMinimo
        			THIS.lMenorQueMinimo = .T. && Banera para filtrar los consumos menor que el minimo(para analisis)
        		ELSE
        			THIS.lMenorQueMinimo = .F.
        		ENDIF
        		RETURN THIS.oTipoConsumo.ConsumoBajo
        	ENDIF
        ELSE 
        	IF (THIS.nConsumo  > lnLimiteConsumoMAX)
            	RETURN THIS.oTipoConsumo.ConsumoAlto &&Consumo Alto o Medidor Invertido
        	ELSE
            	RETURN THIS.oTipoConsumo.ConsumoNormal
            ENDIF
        ENDIF
    ENDFUNC

    *********************************************************
	* Método........: Get_LimitesConsumo
	* Return........: Integer
	* Descripción...: Descripción de Get_LimitesConsumo
	* Fecha.........: 10-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Migrado.......: De .Net 2008 (LecturaMovil)
	*********************************************************
	PROCEDURE Get_LimitesConsumo(tnId_Categ AS Integer, tnConsumoHistorico AS Integer,;
								 tnLimiteConsumoMIN AS Integer, tnLimiteConsumoMAX AS Integer)
	&&BEGIN
        &&Falta leer de la tableName las variaciones
        LOCAL lnFactor, lcSQL, lnArea, lnMedia
        lnArea = SELECT()
        lnFactor = 0.65
        lcSQL = ""
       	lnMedia = 0
        IF (THIS.lPorcLect_Usuario = .T. AND THIS.nPorcLECT > 0)
        	lnFactor = THIS.nPorcLECT
        ELSE
	        lcSQL = "SELECT  * " +;
	                "  FROM CATECONS " +;
	                " WHERE " + ;
				    "       Id_Categ = " + oMySQL.Fox2SQL(tnId_Categ)  +;
	                "   AND INICIO <= " + oMySQL.Fox2SQL(tnConsumoHistorico)  + ;
	                "   AND " + oMySQL.Fox2SQL(tnConsumoHistorico)  + " <= FIN "
	                
	        oMySQL.EjecutarCursor(lcSQL, "CATECONS_", THIS.DataSession)
	        IF (RECCOUNT("CATECONS_") > 0)
	            lnFactor = CATECONS_.Variacion
	            &&Test: Para medias ceros
	            IF tnConsumoHistorico = 0
	            	tnConsumoHistorico = CATECONS_.Fin
	            ENDIF
	        ELSE &&// por default si no hay
	            lnFactor = 0.65
	            IF (tnConsumoHistorico <= 39)
	                lnFactor = 0.65
	            ELSE
		            IF ((40 <= tnConsumoHistorico) AND (tnConsumoHistorico <= 499))
		                lnFactor = 0.35
		            ELSE
			            IF ((500 <= tnConsumoHistorico) AND (tnConsumoHistorico <= 999))
			                lnFactor = 0.20
			            ELSE
			            	IF ((1000 <= tnConsumoHistorico) AND (tnConsumoHistorico <= 99999))
			                	lnFactor = 0.10
			                ENDIF
			            ENDIF
		            ENDIF
	            ENDIF
	        ENDIF
	        USE IN CATECONS_
	    ENDIF
        lnMedia = tnConsumoHistorico
        IF(lnFactor > 1 ) &&Add: 07-05-2019, By: Ing. Alfonzo Salgado Flores, Nota: Para Que no de Limite Negativo...
        	tnLimiteConsumoMIN = INT((lnMedia * lnFactor) - lnMedia)
        ELSE
        	tnLimiteConsumoMIN = INT(lnMedia - (lnMedia * lnFactor))
        ENDIF
        tnLimiteConsumoMAX = INT(lnMedia + (lnMedia * lnFactor))
        SELECT(lnArea)
    ENDPROC
	
	*********************************************************
	* Método........: GetConsumoMinimo
	* Return........: Integer
	* Descripción...: Descripción de GetConsumoMinimo
	* Fecha.........: 12-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Migrado.......: De .Net 2008 (LecturaMovil)
	*********************************************************
	FUNCTION GetConsumoMinimo(tnId_Categ As Integer) AS Integer
        LOCAL lnArea, lnResult, lcSQL
        lnResult = 0
        lcSQL = ""
        lnArea = SELECT()
        lcSQL = " SELECT CONSUMOMIN " +;
                "   FROM CATEGORI " +;
                "  WHERE " +;
                "        ID_CATEG = " + oMySQL.Fox2SQL(tnId_Categ)
        oMySQL.EjecutarCursor(lcSQL, "curCATEGORI", THIS.DataSession)
        IF (RECCOUNT("curCATEGORI") > 0)
            lnResult = curCATEGORI.CONSUMOMIN
        ENDIF
        USE IN curCATEGORI
        SELECT(lnArea)
        RETURN lnResult
    ENDFUNC

    *********************************************************
	* Método........: AnormalidadCorrecta
	* Return........: Integer
	* Descripción...: Descripción de AnormalidadCorrecta
	* Fecha.........: 12-07-2018
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Migrado.......: De .Net 2008 (LecturaMovil)
	*********************************************************
    FUNCTION AnormalidadCorrecta(tnId_MediEst AS Integer, tnTipoConsumo AS Integer,;
    							 tnTipoComportamiento AS Integer) AS Integer
	&&BEGIN
        LOCAL lnResult, lnArea, lcSQL
        lnResult = 0
        lnArea = SELECT()
        lcSQL = "SELECT * FROM MEDIESTA " +;
        		" WHERE ID_MEDIEST = " + oMySQL.Fox2SQL(tnId_MediEst) +;
        		"   AND ID_TIPOCON = " + oMySQL.Fox2SQL(tnTipoConsumo)
        oMySQL.Ejecutar(lcSQL, "MEDIESTA_", THIS.DataSession)	
        IF (RECCOUNT("MEDIESTA_") > 0)        
            THIS.nRegla = MEDIESTA_.Regla
            USE IN MEDIESTA_
            IF( tnTipoComportamiento = THIS.oTipoComportamiento.FinDeCiclo)
                IF (THIS.nRegla <> THIS.oReglaLectura.FIN_DE_CICLO)
                    THIS.ErrorMsg = "La Anormalidad Selecionada no tiene como REGLA: Fin de Ciclo, Seleccione la anormalidad Correcta!"
                    RETURN 3
                ENDIF
            ENDIF

            IF ((tnTipoComportamiento = THIS.oTipoComportamiento.VolcadoAntesDeLimite) OR;
            	(tnTipoComportamiento = THIS.oTipoComportamiento.VolcadoEnLimite))
                IF (THIS.nRegla <> THIS.oReglaLectura.MEDIDOR_VOLCADO)
                    THIS.ErrorMsg = "La Anormalidad Selecionada no tiene como REGLA: Medidor Volcado, Seleccione la anormalidad Correcta!"
                    RETURN 4
                ENDIF
            ENDIF

            &&Se podria abrir en el eterno de el formulario para no consultar a cada rato. ojo
            lcSQL = "SELECT * FROM REGLALE_ " +;
            		" WHERE REGLALEC = " + oMySQL.Fox2SQL(THIS.nRegla)
            oMySQL.Ejecutar(lcSQL, "REGLALE_", THIS.DataSession)
            IF (RECCOUNT("REGLALE_") > 0)
             	&&VERIFICAMOS SI LA REGLA ES APLICABLE AL TIPOCONSUMO
             	lcCampo = THIS.oTipoConsumo.GetFieldName(tnTipoConsumo)
                lcCampo = "REGLALE_." + lcCampo
                llAplicable = &lcCampo
                IF (llAplicable = .T.)
                    lnResult = 0 &&Existe Anormalidad y su Tipo consumo es Valido
                    THIS.ErrorMsg = ""
                ELSE
                    lnResult = 2
                    THIS.ErrorMsg = "Regla[No Hay Regla Aplicable]"
                ENDIF
            ELSE
                lnResult = 2
                THIS.ErrorMsg = "Regla[No Hay Registros]"
            ENDIF
            USE IN REGLALE_
        ELSE
            lnResult = 2 && Existe Anormalidad pero  posiblemente su tipo consumo no es correcto
            THIS.ErrorMsg = "[TipoConsumo no Compatible con la Anormalidad]"
        ENDIF

        SELECT(lnArea)
        RETURN lnResult
    ENDFUNC

    *********************************************************
	* Método........: AnormalidadCorrecta
	* Return........: Integer
	* Descripción...: Descripción de AnormalidadCorrecta
	* Fecha.........: 07-12-2022
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
    FUNCTION EsCasoLecturaPendienteAFacturar(tnId_MediEst AS Integer, tnTipoConsumo AS Integer,;
    										  tnLectAnt AS Integer, tnLectAct AS Integer,;
    										  tnConsumo AS Integer, tnMedia AS Integer, tnConsumoFac AS Integer, tnConsumoMinimo AS Integer) AS Integer
	&&BEGIN
    	LOCAL loEx AS Exception
    	LOCAL lnResult, lnArea, lcSQL, lnTipoConsumoSistema
        lnResult = 3
        lnArea = SELECt()
        lcSQL = "SELECT ID_MediEst, ID_TipoCon, Regla " +;
        		"  FROM MEDIESTA " +;
        		" WHERE ID_MEDIEST = " + oMySQL.Fox2SQL(tnId_MediEst)
        oMySQL.Ejecutar(lcSQL, "MEDIESTA__", THIS.DataSession)	
        IF (RECCOUNT("MEDIESTA__") > 0)        
            THIS.nRegla = MEDIESTA__.Regla
        	lnTipoConsumoSistema = MEDIESTA__.ID_TipoCon
            USE IN MEDIESTA__
            IF(pGlobal.Id_NoGen = tnId_MediEst) &&tnTipoConsumo = Conumo Cero (Si o Si es..)
            	IF (THIS.nRegla = 11) && REGLA : LECTURA PENDIENTE A FACTURAR
            		lnResult = 0 
            	ENDIF 
            ENDIF
        ELSE
        	lnResult = 3
        	THIS.ErrorMsg = "[No Existe Anormalidad: ] " + STR(tnId_MediEst,5) 
		ENDIF    
    	SELECt(lnArea)
    	RETURN lnResult
    ENDFUNC

    *********************************************************
    * Método........: EsCasoSinLectura_AplicarPromedio
    * Return........: Integer 
    * Descripción...: Descripción de EsCasoSinLectura_AplicarPromedio
    * Fecha.........: 25-04-2019
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION EsCasoSinLectura_AplicarPromedio(tnId_MediEst AS Integer, tnTipoConsumo AS Integer,;
    										  tnLectAnt AS Integer, tnLectAct AS Integer,;
    										  tnConsumo AS Integer, tnMedia AS Integer, tnConsumoFac AS Integer, tnConsumoMinimo AS Integer) AS Integer
	&&BEGIN
    	LOCAL loEx AS Exception
    	LOCAL lnResult, lnArea, lcSQL, lnTipoConsumoSistema
        lnResult = 3
        lnArea = SELECt()
        lcSQL = "SELECT ID_MediEst, ID_TipoCon, Regla FROM MEDIESTA " +;
        		" WHERE ID_MEDIEST = " + oMySQL.Fox2SQL(tnId_MediEst)
        oMySQL.Ejecutar(lcSQL, "MEDIESTA__", THIS.DataSession)	
        IF (RECCOUNT("MEDIESTA__") > 0)        
            THIS.nRegla = MEDIESTA__.Regla
        	lnTipoConsumoSistema = MEDIESTA__.ID_TipoCon
            USE IN MEDIESTA__
            IF((tnLectAct = tnLectAnt) AND (tnConsumo = 0)) &&tnTipoConsumo = Conumo Cero (Si o Si es..)
            	IF (( lnTipoConsumoSistema = THIS.oTipoConsumo.SinLectura) AND (  THIS.nRegla = THIS.oReglaLectura.CONSUMO_PROMEDIO))
            		&& Verificamos si TEMPORAL.ConsumoFac == TEMPORAL.Media (Para ver si se esta aplicando la regla) IMPORTANTE...
            		IF (tnConsumoMinimo > 0) AND (tnConsumoFac <= tnConsumoMinimo) AND (tnMedia <= tnConsumoMinimo)
						&&REPLACE TEMPORAL.ConsumoFac WITH CATEGORI.ConsumoMin
						THIS.ErrorMsg = ""
						lnResult = 0
					ELSE 
	            		IF(tnConsumoFac <> tnMedia)
	            			lnResult = 4
	            			THIS.ErrorMsg = "[ConsumoFac Invalido]"
	            		ELSE
	            			lnResult = 0
	            		ENDIF
	            	ENDIF
            	ELSE
            		lnResult = 2
            		THIS.ErrorMsg = "[TipoConsumo no Compatible con la Anormalidad]"
            	ENDIF
            ELSE
            	lnResult = 2
            	THIS.ErrorMsg = "[TipoConsumo no Compatible con la Anormalidad]"
            ENDIF
        ELSE
        	lnResult = 3
        	THIS.ErrorMsg = "[No Existe Anormalidad: ] " + STR(tnId_MediEst,5) 
		ENDIF    
    	SELECt(lnArea)
    	RETURN lnResult
    ENDFUNC  
    
    *********************************************************
	* Método........: EsInstalacionNueva
	* Return........: Boolean
	* Descripción...: Descripción de EsInstalacionNueva
	* Fecha.........: 30-04-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Modificado....: 12-11-2020
	*********************************************************
								*tnId_MediEst AS Integer, tnID_Socio AS Integer,tcZonaRuta AS String, tdf_GenLect AS Date ) AS Integer
	FUNCTION EsInstalacionNueva(tnId_MediEst AS Integer, tnID_Socio AS Integer, tcCobro AS String, tcError AS String) AS Integer
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, lnResult, llEsNuevaIns, lnCantidadLecturas
		lnArea = SELECT()
		llEsNuevaIns = .F.
		lnCantidadLecturas = -1
		tcError = ""
		TRY
			llTieneUnicoInstalam = THIS.TieneUnicoInstalam(tnID_Socio)
			lnCantidadLecturas = THIS.CantidadLecturas(tcCobro, tnID_Socio)
			IF(lnCantidadLecturas >= 0)
				llEsNuevaIns = (lnCantidadLecturas <= pGlobal.MesesNuevo)
				IF (llEsNuevaIns = .F.)
					llEsNuevaIns = llTieneUnicoInstalam
				ENDIF
			ELSE
				tcError = "Error al Consultar Cantidad"
			ENDIF

			IF(pGlobal.ID_Nuevo > 0)   
            	IF (pGlobal.ID_Nuevo == tnId_MediEst)
            		lnResult = 0
            		THIS.ErrorMsg = "[Valido][Instalación Nueva]"
		    		IF (llEsNuevaIns == .F.)
		    			lnResult = 1
		    			tcError = "[Error][No Tiene Instalación Nueva el Asocciado]"
		    		ENDIF
            	ELSE
            		&&Verificamos si no esta en la lista de los cambios en _SOCIMEDI
            		IF (llEsNuevaIns == .T.)
		    			lnResult = 1
		    			tcError = "[Error][Tiene Instalación Nueva el Asocciado]"
		    		ELSE
		    			lnResult = 2 &&Ignorar en los siguientes invocaciones.
		    		ENDIF
            	ENDIF
            ELSE
            	lnResult = 3 &&Ignorar en los siguientes invocaciones.
            	*tcError = "[Informativo] Instalación Nueva no tiene Valor en ParaLect"
            ENDIF

		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.EsInstalacionNueva() "
			tcError = "Error Try/Catch"
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
		RETURN lnResult
	ENDFUNC

    *********************************************************
    * Método........: EsCambioDeMedidor
    * Return........: Integer 
    * Descripción...: Descripción de EsCambioDeMedidor
    * Fecha.........: 12-11-2020
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION EsCambioDeMedidor(tnId_MediEst AS Integer, tnID_Socio AS Integer, tcError AS String) AS Integer
	&&BEGIN
    	LOCAL lnResult, lnArea, lcSQL
    	lnArea = SELECT()
        lnResult = 0
        tcError = ""
        IF (TYPE("pGlobal.ID_Cambio") != 'U')        
            IF(pGlobal.ID_Cambio > 0)     
				lcSQL = " SELECT I.* " +;
			   			"   FROM _SOCIMEDI I " +;
			   			"  WHERE I.ID_Socio = " + oMySQL.FOX2SQL(tnID_Socio) 
			   			
	    		oMySQL.EjecutarCursor(lcSQL, "cExiste_", THIS.DataSession)
            	IF (pGlobal.ID_Cambio == tnId_MediEst)
            		lnResult = 0
            		tcError = "[Valido][Cambio de Medidor]"
		    		IF (RECCOUNT("cExiste_") = 0)
		    			lnResult = 1
		    			tcError = "[Error][No Tiene Cambio de Medidor el Asocciado]"
		    		ENDIF
            	ELSE
            		&&Verificamos si no esta en la lista de los cambios en _SOCIMEDI
            		IF (RECCOUNT("cExiste_") > 0)
		    			lnResult = 1
		    			tcError = "[Error][Tiene Cambio de Medidor el Asocciado]"
		    		ELSE
		    			lnResult = 2 &&Ignorar en los siguientes invocaciones.
		    		ENDIF
            	ENDIF
            ELSE
            	lnResult = 3 &&Ignorar en los siguientes invocaciones.
            	*tcError = "[Informativo] Cambio de Medidor no tiene Valor en ParaLect"
            ENDIF
        ELSE
        	lnResult = 4 &&Ignorar en los siguientes invocaciones.
        	tcError = "[Informativo] Cambio de Medidor no existe en ParaLect "
		ENDIF    
		USE IN SELECT("cExiste_")
		SELECT(lnArea)
    	RETURN lnResult
    ENDFUNC

     *********************************************************
    * Método........: EsRegularizacionBajaTemporal
    * Return........: Integer 
    * Descripción...: Descripción de EsRegularizacionBajaTemporal
    * Fecha.........: 12-11-2020
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION EsRegularizacionBajaTemporal(tnId_MediEst AS Integer, tnID_Socio AS Integer, tcError AS String) AS Integer
	&&BEGIN
    	LOCAL lnResult, lnArea, lcSQL
        lnResult = 0
        lnArea = SELECT()
        tcError = ""
        IF INLIST(tnID_Socio, 14834, 14833)
        	*SET STEP ON 
        ENDIF
        IF (TYPE("pGlobal.ID_Regula") != 'U')        
            IF(pGlobal.ID_Regula > 0)
				lcSQL = " SELECT I.* " +;
			   			"   FROM _INSTALAM2 I " +;
			   			"  WHERE I.ID_Socio = " + oMySQL.FOX2SQL(tnID_Socio)
	    		oMySQL.EjecutarCursor(lcSQL, "cExiste_", THIS.DataSession)
            	IF (pGlobal.ID_Regula == tnId_MediEst)
            		lnResult = 0
            		tcError = "[Valido][Regularización Baja Temporal]"
		    		IF (RECCOUNT("cExiste_") = 0)
		    			lnResult = 1
		    			tcError = "[Error][No Tiene Regularización Baja Temporal el Asocciado]"
		    		ENDIF
            	ELSE
            		&&Verificamos si no esta en la lista de los cambios en _SOCIMEDI
            		IF (RECCOUNT("cExiste_") > 0)
		    			lnResult = 1
		    			tcError = "[Error][Tiene Regularización Baja Temporal el Asocciado"
		    		ELSE
		    			lnResult = 2 &&Ignorar en los siguientes invocaciones.
		    		ENDIF
            	ENDIF
            ELSE
            	lnResult = 3 &&Ignorar en los siguientes invocaciones.
            	*tcError = "[Informativo] Regularizacion x Baja Temporal no tiene Valor en ParaLect"
            ENDIF
        ELSE
        	lnResult = 4 &&Ignorar en los siguientes invocaciones.
        	tcError = "[Informativo] Regularizacion x Baja Temporal no existe en ParaLect "
		ENDIF   
		USE IN SELECT("cExiste_") 
		SELECT(lnArea)
    	RETURN lnResult
    ENDFUNC

    *********************************************************
    * Método........: EsInstalacionNueva2
    * Return........: Integer 
    * Descripción...: Descripción de EsInstalacionNueva2
    * Fecha.........: 12-11-2020
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION EsInstalacionNueva2(tnId_MediEst AS Integer) AS Integer
	&&BEGIN
    	LOCAL lnResult
        lnResult = 0
        IF (TYPE("pGlobal.ID_Nuevo") != 'U')        
            IF(pGlobal.ID_Nuevo > 0)
            	IF (pGlobal.ID_Nuevo == tnId_MediEst)
            		lnResult = 0
            		THIS.ErrorMsg = "[Valido][Instalación Nuevo]"
            	ELSE
            		lnResult = 1 &&Ignorar en los siguientes invocaciones.
            	ENDIF
            ELSE
            	lnResult = 2 &&Ignorar en los siguientes invocaciones.
            	THIS.ErrorMsg = "[Informativo] Instalación Nuevo no tiene Valor en ParaLect"
            ENDIF
        ELSE
        	lnResult = 3 &&Ignorar en los siguientes invocaciones.
        	THIS.ErrorMsg = "[Informativo] Instalación Nuevo no existe en ParaLect "
		ENDIF    
    	RETURN lnResult
    ENDFUNC

    *********************************************************
    * Método........: GetMedidores
    * Descripción...: Descripión de GetMedidores
    * Fecha.........: 30-07-2018
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE GetMedidores()
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, lcListaMedidores
    	TRY
    		&&lcListaMedidores = THIS.GetListaID_Medidores()
    		lcSQL = "SELECT * " +;
    				"  FROM MEDIDOR " +;
    				" WHERE ID_MEDIDOR IN " +;
    				"					 (SELECT ID_MEDIDOR " +;
    				"					    FROM GENLECT " +;
    				" 					   WHERE ID_GENFACT = " + oMySQL.Fox2SQL(GENFACT.ID_GENFACT) + ")"

    		oMySQL.Ejecutar(lcSQL, "MEDIDOR", THIS.DataSession)
    		IF NOT USED("DIAMACOM")
				oMySQL.GetTablaIndexada("DIAMACOM", "DIAMACOM", "*", THIS.DataSession)
				&&Esto lo Usara para el Cursor DIAMACOM_
			ENDIF
    	CATCH TO loEx
    		lcLog = [  ProcedureInitial...: GenLect.GetMedidores()] + THIS._Enter +[  ]+ [VariableLog]
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    ENDPROC
    
    *********************************************************
    * Método........: GetListaID_Medidores()
    * Descripción...: Descripión de GetListaID_Medidores()
    * Fecha.........: 30-07-2018
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION GetListaID_Medidores() AS String
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, lcLista
    	lnArea = SELECT()
    	TRY
    		lcLista = ""
    		IF USED("TEMPORAL")
    			SELECT * FROM TEMPORAL INTO CURSOR curTEMPORAL
    			SELECT curTEMPORAL
    			SCAN ALL
    				lcLista = lcLista + ALLTRIM(STR(curTEMPORAL.ID_MEDIDOR,10)) + ", "
    			ENDSCAN
    			lcLista = SUBSTR(lcLista,1, LEN(lcLista)-2)
    			lcLista = "(" + lcLista + ")"
    			USE IN curTEMPORAL
    		ENDIF
    		
    	CATCH TO loEx
    		lcLog = [  ProcedureInitial: GenLect.GetListaID_Medidores()()] 
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    	RETURN lcLista
    ENDFUNC
         
    *********************************************************
    * Método........: GetLecturaSocio
    * Descripción...: Descripión de GetLecturaSocio
    * Fecha.........: 22-08-2018
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE GetLecturaSocio(tnID_GenFact AS Integer, tnID_Socio AS Integer)
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL
    	lnArea = SELECt()
		TRY
			lcSQL = "SELECT ID_GenFact, ID_Socio, LectAnt, LectAct, Consumo, ConsumoFac, " +;
					"		ID_MediEst" +;
					"  FROM GENLECT " + ;
					" WHERE ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact) +;
					"   AND ID_SOCIO = " + oMySQL.Fox2SQL(tnID_Socio)
			oMySQL.Ejecutar(lcSQL, "curLecturaSocio", THIS.DataSession)
		CATCH TO loEx
			lcLog = [  ProcedureInitial: AjusLect.ActualizarLecturas()] + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
    ENDPROC
    
    *********************************************************
    * Método........: CargarGenLectN
    * Descripci?...: Descripi? de CargarGenLectN
    * Fecha.........: 21-08-2018
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE CargarGenLectN(tnID_GenFact AS Integer)
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, lcStackTrace
    	lnArea = SELECT()
    	TRY
    		lcSQL = "SELECT * " +;
    				"  FROM GENLECTN " +;
    				" WHERE ID_GenFact = " + oMySQL.Fox2SQL(tnID_GenFact)
    		oMySQL.Ejecutar(lcSQL, "GenLectN_", THIS.DataSession)
    		INDEX ON ID_Socio TAG ID_Socio ADDITIVE
    		SELECT TEMPORAL
    		SCAN ALL
    			REPLACE TEMPORAL.AnorLect WITH THIS.oAnorLect.GetCantRec(TEMPORAL.ID_GenFact, TEMPORAL.ID_Socio)
    			REPLACE TEMPORAL.AjusLect WITH THIS.oAjusLect.GetCantRec(TEMPORAL.ID_GenFact, TEMPORAL.ID_Socio)
    			SELECT GenLectN_
    			SEEK TEMPORAL.ID_Socio
    			IF FOUND()
    				REPLACE TEMPORAL.Hora WITH GenLectN_.Hora
    				REPLACE TEMPORAL.ID_MediEs2 WITH GenLectN_.ID_MediEs2
    			ENDIF
    		ENDSCAN
    		SELECT TEMPORAL 
			GO TOP
    	CATCH TO loEx
            lcLog = [	Metodo...: GenLect.CargarGenLectN() ] + THIS._Enter 
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    ENDPROC

    *********************************************************
    * Método........: CargarFechasLecturacion
    * Descripción...: Descripión de CargarFechasLecturacion
    * Fecha.........: 25-09-2018
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE CargarFechasLecturacion(tnID_GenFact AS Integer, toFechaIni AS Object, toFechaFin AS Object,;
    								  toTipoLecturacion AS Object)
	&&BEGIN
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, lcCobroAnt
    	LOCAL ldFechaIni, ldFechaFin, lcGenLectN
    	lnArea = SELECT()
    	TRY
    		lcSQL = "SELECT N.ID_GENFACT, MIN(N.FECHA) AS F_LECTURA " +;
					"  FROM GENLECTN N, GENFACT G " +;
					" WHERE N.ID_GENFACT = G.ID_GENFACT " +;
					"   AND G.ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact) +;
					" GROUP BY N.ID_GENFACT " 
			lcGenLectN = oMySQL.CrearTemporal(lcSQL);

    		lcSQL = " SELECT G.ID_GENFACT, G.ID_ZONA, G.RUTA, G.COBRO, 00000000 AS DIASCONSUM, " +;
		            "        G.USRFECHA, N.F_LECTURA, G.F_GENLECT, G.F_GENFACT " +;
		     		"   FROM GENFACT G LEFT JOIN " + lcGenLectN + " N ON (G.ID_GENFACT = N.ID_GENFACT ) " +;
		     		"  WHERE G.ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact)
		    oMySQL.Ejecutar(lcSQL, "curGenLect_", THIS.DataSession)

		    lcCobroAnt = oUtil.AAMMANT(curGenLect_.Cobro)
		    lcSQL = " SELECT N.ID_GENFACT, MIN(N.FECHA) AS F_LECTURA" +;
		     		"   FROM GENLECTN N, GENFACT G  " +; 
		     		"  WHERE N.ID_GENFACT = G.ID_GENFACT " +;
		     		"	 AND G.ID_ZONA = " + oMySQL.Fox2SQL(curGenLect_.ID_ZONA) +;
		     		"    AND G.RUTA = " + oMySQL.Fox2SQL(curGenLect_.Ruta) +;
		     		"	 AND G.COBRO = " + oMySQL.Fox2SQL(lcCobroAnt) +;
		     		" GROUP BY N.ID_GENFACT " 
		    lcGenLectN_Ant = oMySQL.CrearTemporal(lcSQL);

		     toFechaFin.Value = curGenLect_.F_GENLECT
		    IF !ISNULL(curGenLect_.F_LECTURA)
		    	toFechaFin.Value = curGenLect_.F_LECTURA 
		    	toTipoLecturacion.Value = 2
		    ELSE
		    	toTipoLecturacion.Value = 1 && Normal
		    ENDIF

		    lcSQL = " SELECT G.ID_GENFACT, G.ID_ZONA, G.RUTA, G.COBRO, G.GENERADO, " +;
		            "        G.USRFECHA, N.F_LECTURA, G.F_GENLECT, G.F_GENFACT " +;
		     		"   FROM GENFACT G LEFT JOIN " + lcGenLectN_Ant + " N ON (G.ID_GENFACT = N.ID_GENFACT ) " +;
		     		"  WHERE " +;
		     		"	  	 G.ID_ZONA = " + oMySQL.Fox2SQL(curGenLect_.ID_ZONA) +;
		     		"    AND G.RUTA = " + oMySQL.Fox2SQL(curGenLect_.Ruta) +;
		     		"	 AND G.COBRO = " + oMySQL.Fox2SQL(lcCobroAnt)
		    oMySQL.Ejecutar(lcSQL, "curGenLectN_Ant", THIS.DataSession)

		    toTipoLecturacion.Value = 1 && Manual
		    IF RECCOUNT("curGenLectN_Ant") > 0
			    toFechaIni.Value = curGenLectN_Ant.F_GENLECT + 1
			    IF !ISNULL(curGenLectN_Ant.F_LECTURA)
			    	toFechaIni.Value = curGenLectN_Ant.F_LECTURA + 1
			    	toTipoLecturacion.Value = 2
			    ENDIF
			ELSE
				toFechaIni.Value = toFechaFin.Value - 30
			ENDIF
		    

		    *USE IN curGenLect_
		    *USE IN curGenLectN_Ant
    
    	CATCH TO loEx
    		lcLog = [  ProcedureInitial: GenLect.CargarFechasLecturacion()]
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    ENDPROC
    
    *********************************************************
    * Método........: VerificarUbicacionSocios
    * Descripcin...: Descripión de VerificarUbicacionSocios
    * Fecha.........: 16-10-2018
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE VerificarUbicacionSocios(tnID_GenFact AS Integer)
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL
    	SET DATASESSION TO THIS.DataSession &&Importante...
		lnArea = SELECT()
    	TRY
    		IF NOT USED("curNoUbicado")
    		 	CREATE CURSOR curNoUbicado (ID_GENFACT N(10) ,;
	    		 						  ID_SOCIO  N(10) ,;    		 						  
	                                      CodUbi_Pla C(9) ,;
	                                      CodUbi_Soc C(9) ,;
	                                      ID_Medidor N(10) ,;
	                                      Id_Categ N(2) )
			ENDIF
			lcSQL = "SELECT DISTINCT G.ID_GENFACT, G.ID_SOCIO, " +;
					" 		G.COD_SOCIO AS CodUbi_Pla, S.COD_SOCIO AS CodUbi_Soc, " +;
					" 		S.ID_Medidor, S.Id_Categ " +;
					"  FROM GENLECT G, SOCIOS S " +;
					" WHERE G.ID_SOCIO = S.ID_SOCIO " +;
					"   AND SUBSTR(G.COD_SOCIO,1,4) <> SUBSTR(S.COD_SOCIO,1,4) " +;
   			   		"   AND G.ID_GenFact = " + oMySQL.Fox2SQL(tnID_GenFact)
   			oMySQL.Ejecutar(lcSQL, "curNoUbic_", THIS.DataSession)
   			IF RECCOUNT("curNoUbic_") > 0
	   			SELECT curNoUbicado
	   			APPEND FROM DBF('curNoUbic_')
	   		ENDIF
	   		USE IN curNoUbic_
    	CATCH TO loEx
    		lcLog = [  ProcedureInitial: GenLect.VerificarUbicacionSocios()] 
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    ENDPROC
    
    *********************************************************
    * Método........: MostrarSociosCambiadoUbicacion
    * Descripción...: Descripión de MostrarSociosCambiadoUbicacion
    * Fecha.........: 16-10-2018
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE MostrarSociosCambiadoUbicacion()
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, loGridBrowse
    	SET DATASESSION TO THIS.DataSession &&Importante...
		lnArea = SELECT()
    	TRY
    		IF USED("curNoUbicado")
    			SELECT curNoUbicado
    			GO TOP
    		ENDIF
    		loGridBrowse = CREATEOBJECT("GridBrowse")
            loGridBrowse.SetCursorName("curNoUbicado")
            loGridBrowse.AddCol("ID_GENFACT", "Orden Inst.", 80)
            loGridBrowse.AddCol("ID_Socio", "Cliente", 50)
            loGridBrowse.AddCol("CodUbi_Pla", "Cod.Ubic. Planilla", 120)
            loGridBrowse.AddCol("CodUbi_Soc", "Cod.Ubic. Socio", 120)
            loGridBrowse.AddCol("Id_Medidor", "Medidor", 70)
            loGridBrowse.AddCol("Id_Categ", "Categoria", 50)
            loForm = CreateObject("Browse", @loGridBrowse, "curNoUbicado")
            loForm.WindowType = 1 
            loForm.titulo.txtTitulo.Caption = "Socios con Ubicación Diferente a Planilla y/o Sin Medidor"
            loForm.titulo.txt2.Caption = loForm.titulo.txtTitulo.Caption
            loForm.Show()
    
    	CATCH TO loEx
    		lcLog = [  ProcedureInitial: GenLect.MostrarSociosCambiadoUbicacion()] + THIS._Enter +[  ]+ [VariableLog]
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    ENDPROC

    *********************************************************
    * Método........: ResetearCabioUbicacion
    * Descripción...: Descripión de ResetearCabioUbicacion
    * Fecha.........: DD-MM-YYYY
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE ResetearCabioUbicacion()
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL
    	SET DATASESSION TO THIS.DataSession &&Importante...
		lnArea = SELECT()
    	TRY
    		IF USED("curNoUbicado")
    			USE IN curNoUbicado
    		ENDIF 
    	CATCH TO loEx
    		lcLog = [  ProcedureInitial: GenLect.ResetearCabioUbicacion()] + THIS._Enter +[  ]+ [VariableLog]
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    ENDPROC
    
 	*********************************************************
 	* Método........: ExisteCliente
 	* Return........: Boolean
 	* Descripción...: Descripción de ExisteCliente
 	* Fecha.........: 17-10-2018
 	* Diseñador.....: Ing. Alfonzo Salgado Flores
 	* Implementador.: Ing. Alfonzo Salgado Flores
 	*********************************************************
    FUNCTION ExisteCliente(tnID_GenFact AS Integer, tnID_Socio AS Integer) AS Boolean  
    	LOCAL lnArea, lcSQL, llResult
		lnArea = SELECT()
		llResult = .F.
		lcSQL = "SELECT 1 AS Exito "+ ;
				"  FROM GENLECT "+ ;
				" WHERE ID_GenFact = " + oMySQL.FOX2SQL(tnID_GenFact) + ;
				"   AND ID_Socio = " + oMySQL.FOX2SQL(tnID_Socio)
		oMySQL.Ejecutar(lcSQL,"curExiste",THIS.DataSession)
		IF RECCOUNT("curExiste") > 0
			llResult = curExiste.Exito = 1
		ENDIF
		USE IN curExiste
		SELECT(lnArea)
		RETURN llResult
	ENDFUNC 

	*********************************************************
	* Metodo........: [MAIN] ValidacionFinal
	* Return........: Boolean
	* Descripcion...: Descripión de ValidacionFinal
	* Fecha.........: 08-01-2019
	* Disenador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION ValidacionFinal(tnID_GenFact AS Integer) AS Boolean
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, llValidoTodos
		llValidoTodos = .T.
		SET DATASESSION TO THIS.DataSession &&Importante...
		lnArea = SELECT()
		THIS.lErrorPendientes = .F.
		THIS.lErrorConsumoNegativos = .F.
		TRY
			USE IN SELECT("MEDIESTAP")
			USE IN SELECT("curLectErrados")
			oMySQL.GetTablaIndexada("MEDIESTA", "MEDIESTAP", "*", THIS.DataSession)
		 	CREATE CURSOR curLectErrados (ID_GENFACT N(10),;
    		 						  ID_SOCIO  N(10),;
                                      COD_SOCIO C(9),;
                                      LECTANT N(6),;
                                      LECTACT N(6),;
                                      CONSUMO N(6),;
                                      CONSUMOFAC N(6),;
                                      ID_MEDIEST N(3),;
                                      ERROR C(250) )

			lcSQL = "SELECT ID_GENFACT, ID_SOCIO " +;
					"  FROM AJUSLECT " +;
					" WHERE ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact) 
			oMySQL.Ejecutar(lcSQL, "curAjusLect_", THIS.DataSession)

			lcSQL = "SELECT ID_GENFACT, ID_SOCIO, COD_SOCIO, LECTANT, LECTACT, CONSUMO, CONSUMOFAC, ID_MEDIEST, " +;
					" 		'ADVERTENCIA!! [Lectura Pendiente][Se Generara Su Factura con estos datos, si no lo arregla o en Generacion: Ticke(No Generar Lecturas Pendientes)]' AS ERROR " +;
					"  FROM TEMPORAL " +;
					" WHERE ID_GENFACT =  " + oMySQL.Fox2SQL(tnID_GenFact) +;
					"   AND ID_MEDIEST IN (SELECT ID_MEDIEST FROM MEDIESTAP WHERE REGLA = 1)" +;
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curAjusLect_)"
			oMySQL.EjecutarCursor(lcSQL, "curLecErrorPendiente", THIS.DataSession)

			lcSQL = "SELECT ID_GENFACT, ID_SOCIO, COD_SOCIO, LECTANT, LECTACT, CONSUMO, CONSUMOFAC, ID_MEDIEST, " +;
					" 		'ERROR FATAL!! [Cosumo Negativo]' AS ERROR " +;
					"  FROM TEMPORAL " +;
					" WHERE ID_GENFACT =  " + oMySQL.Fox2SQL(tnID_GenFact) +;
					"   AND CONSUMO < 0 " +;
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curLecErrorPendiente)" +;
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curAjusLect_)"
			oMySQL.EjecutarCursor(lcSQL, "curLecErrorConsumoNegativo", THIS.DataSession)

			lcSQL = "SELECT ID_GENFACT, ID_SOCIO, COD_SOCIO, LECTANT, LECTACT, CONSUMO, CONSUMOFAC, ID_MEDIEST, " +;
					" 		'ERROR FATAL!!' AS ERROR " +;
					"  FROM TEMPORAL " +;
					" WHERE ID_GENFACT =  " + oMySQL.Fox2SQL(tnID_GenFact) +;
					"   AND (LECTACT > LECTANT) " +;
					"   AND (LECTACT - LECTANT) != Consumo " +;
					"   AND ID_MEDIEST = 0 " +;   &&Add: 08-07-2021, By: ASF, Nota: Si es Volcado o FinCiclo omitimos siempre que anormalidad sea valido
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curLecErrorPendiente)" +;
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curLecErrorConsumoNegativo) " +;
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curAjusLect_)"
			oMySQL.EjecutarCursor(lcSQL, "curLecErrorFatal", THIS.DataSession)
			
			llValidoTodos = RECCOUNT("curLecErrorFatal") = 0

			lcSQL = "SELECT ID_GENFACT, ID_SOCIO, COD_SOCIO, LECTANT, LECTACT, CONSUMO, CONSUMOFAC, ID_MEDIEST, " +;
					" 		'ADVERTENCIA!!' AS ERROR " +;
					"  FROM TEMPORAL " +;
					" WHERE ID_GENFACT =  " + oMySQL.Fox2SQL(tnID_GenFact) +;
					"   AND (LECTACT < LECTANT) " +;
					"   AND (LECTANT - LECTACT) != Consumo " +;
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curLecErrorPendiente)" +;
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curLecErrorConsumoNegativo) " +;
					"   AND ID_SOCIO NOT IN (SELECT ID_SOCIO FROM curAjusLect_)" 
			oMySQL.EjecutarCursor(lcSQL, "curLecAdvertencia", THIS.DataSession)

			IF RECCOUNT("curLecErrorPendiente") > 0
				THIS.lErrorPendientes = .T.  &&Add: 90-08-2019, Ing. Alfonzo Salgado Flores, Nota: Para Control de Validar Planilla
	   			SELECT curLectErrados
	   			APPEND FROM DBF('curLecErrorPendiente')
	   		ENDIF

	   		IF RECCOUNT("curLecErrorConsumoNegativo") > 0
	   			THIS.lErrorConsumoNegativos = .T. &&Add: 90-08-2019, Ing. Alfonzo Salgado Flores, Nota: Para Control de Validar Planilla
	   			SELECT curLectErrados
	   			APPEND FROM DBF('curLecErrorConsumoNegativo')
	   		ENDIF

			IF RECCOUNT("curLecErrorFatal") > 0
	   			SELECT curLectErrados
	   			APPEND FROM DBF('curLecErrorFatal')
	   		ENDIF

	   		IF RECCOUNT("curLecAdvertencia") > 0
	   			SELECT curLectErrados
	   			APPEND FROM DBF('curLecAdvertencia')
	   		ENDIF

	   		USE IN SELECT("curLecErrorPendiente")
	   		USE IN SELECT("curLecErrorConsumoNegativo")
	   		USE IN SELECT("curLecErrorFatal")
	   		USE IN SELECT("curLecAdvertencia")
	   		USE IN SELECT("curAjusLect_")

		CATCH TO loEx
			lcLog = " ProcedureInitial: GenLect.ValidacionFinal()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
		RETURN llValidoTodos
	ENDFUNC
	

	*********************************************************
    * Metodo........: MostrarLecturasMalValidados
    * Descripcion...: Descripcion de MostrarLecturasMalValidados
    * Fecha.........: 08-01-2019
    * Disenador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE MostrarLecturasMalValidados()
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, loGridBrowse
    	SET DATASESSION TO THIS.DataSession &&Importante...
		lnArea = SELECT()
    	TRY
    		IF USED("curLectErrados")
    			SELECT curLectErrados
    			GO TOP
    		ENDIF

    		loGridBrowse = CREATEOBJECT("GridBrowse")
            loGridBrowse.SetCursorName("curLectErrados")
            loGridBrowse.AddCol("ID_GENFACT", "Planilla", 55)
            loGridBrowse.AddCol("ID_SOCIO", "Cliente", 50)
            loGridBrowse.AddCol("COD_SOCIO", "Cod.Ubic.", 65)
            loGridBrowse.AddCol("LECTANT", "Lect.Ant.", 55)
            loGridBrowse.AddCol("LECTACT", "Lect.Act.", 55)
            loGridBrowse.AddCol("CONSUMO", "Consumo", 70)
            loGridBrowse.AddCol("CONSUMOFAC", "ConsumoFac", 80)
            loGridBrowse.AddCol("ID_MEDIEST", "Anormalidad", 70)
            loGridBrowse.AddCol("ERROR", "Mensaje(Error/Advertencia)", 850)
            loForm = CreateObject("Browse", @loGridBrowse, "curLectErrados")
            loForm.WindowType = 1 
            loForm.titulo.txtTitulo.Caption = "Lecturas y/o Consumos con Errores!!!"
            loForm.titulo.txt2.Caption = loForm.titulo.txtTitulo.Caption
            loForm.Show()
    
    	CATCH TO loEx
    		lcLog = [  ProcedureInitial: GenLect.MostrarLecturasMalValidados()] + THIS._Enter
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    ENDPROC

    *********************************************************
	* Metodo........: ValidacionFinalAjusLect
	* Return........: Boolean
	* Descripcion...: Descripión de ValidacionFinalAjusLect
	* Fecha.........: 16-07-2019
	* Disenador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION ValidacionFinalAjusLect(tnID_GenFact AS Integer) AS Boolean
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, llValidoTodos
		llValidoTodos = .T.
		SET DATASESSION TO THIS.DataSession &&Importante...
		lnArea = SELECT()
		TRY
			IF NOT USED("curLectErrados")
    		 	CREATE CURSOR curLectErrados (ID_GENFACT N(10),;
	    		 						  ID_SOCIO  N(10),;
	                                      COD_SOCIO C(9),;
	                                      LECTANT N(6),;
	                                      LECTACT N(6),;
	                                      CONSUMO N(6),;
	                                      CONSUMOFAC N(6),;
	                                      ID_MEDIEST N(3),;
	                                      ERROR C(150) )
			ENDIF
			lcSQL = "SELECT ID_GENFACT, ID_SOCIO, COD_SOCIO, LECTANT, LECTACT, CONSUMO, CONSUMOFAC," +;
					" 		ID_MEDIEST,  ACCION, ES_AJUSLEC, " +;
					"		'Ajuste Lectura['+ALLTRIM(STR(AJUSLECT,10))+'] ['+" +;
					"		IIF(ACCION=1,'Ajuste de Consumo','Corrección de Lectura')  +'] [Por Ejecutar]' AS ERROR " +;
					"  FROM AJUSLECT " +;
					" WHERE ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact) +;
					"   AND ACCION IN (1,2) " +;
					"   AND ES_AJUSLEC = 1 "
			oMySQL.Ejecutar(lcSQL, "_AjusLectPorEjecutar", THIS.DataSession)

			llValidoTodos = (RECCOUNT("_AjusLectPorEjecutar") = 0)
			IF RECCOUNT("_AjusLectPorEjecutar") > 0
	   			SELECT curLectErrados
	   			APPEND FROM DBF('_AjusLectPorEjecutar')
	   		ENDIF
	   		USE IN SELECT("_AjusLectPorEjecutar")
	   		USE IN SELECT("curAjusLect_")
		CATCH TO loEx
			lcLog = " ProcedureInitial: GenLect.ValidacionFinal()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
		RETURN llValidoTodos
	ENDFUNC

    *********************************************************
    * Método........: CantidadLecturas
    * Return........: Integer
    * Descripción...: Descripción de CantidadLecturas
    * Fecha.........: 30-04-2019
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION CantidadLecturas(tcCobro AS String, tnId_Socio AS Integer) AS Integer
		LOCAL loEx AS Exception, lcLog AS String
		LOCAL lnArea, lnResult
		lnArea = SELECT()
		lnResult = 0
		TRY 
			IF USED("_HISTLECT")
				*!* TODO:Ver si se puede crear un Cursor solo con los datos que se precisa para no hacer esfuerzo a la Tabla
				*!* "   AND COD_SOCIO = " + oMySQL.Fox2SQL(tcCod_Socio) 
				*!* Add: 21-09-2019, By: Ing. Alfonzo SF, Nota: "   AND COBRO <= " + oMySQL.Fox2SQL(tcCobro) .
				*!* Importante por que en ocaciones crean 2 planillas el mismo dia de la misma zonaruta Ej.: 2019-09 y 2019-10
				lcSQL = "SELECT ID_SOCIO, Count(*) AS Cantidad "+;
						"  FROM _HISTLECT "+;
						" WHERE ID_SOCIO = " + oMySQL.Fox2SQL(tnId_Socio) +;
						"   AND COBRO <= " + oMySQL.Fox2SQL(tcCobro) +;
						" GROUP BY ID_SOCIO"
				oMySQL.EjecutarCursor(lcSQL, "curNuevaInsta", THIS.DataSession)
			   	IF RECCOUNT("curNuevaInsta") > 0
			   		lnResult = curNuevaInsta.Cantidad
				ENDIF
			ELSE
				lcLog ="   ProcedureInitial: GenLect.CantidadLecturas()"
				oError.GuardarLog("_HISTLECT_NOT_FOUND", "NO HAY CURSOR _HISTLECT")
			ENDIF
		CATCH TO loEx
			lnResult = -1
			lcLog ="   ProcedureInitial: GenLect.CantidadLecturas()"
			oError.Guardar(loEx, lcLog)
		ENDTRY
		USE IN SELECT("curNuevaInsta")
		SELECT(lnArea)
		RETURN lnResult
	ENDFUNC

	*********************************************************
    * Método........: CantidadFacturas
    * Return........: Integer
    * Descripción...: Descripción de CantidadFacturas
    * Fecha.........: 30-04-2019
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION CantidadFacturas(tcCobro AS String, tnId_Socio AS Integer, tcCod_Socio AS String) AS Integer
		LOCAL loEx AS Exception, lcLog AS String
		LOCAL lnArea, lnResult
		lnArea = SELECT()
		lnResult = 0
		TRY 
			lcSQL = "SELECT ID_SOCIO, COUNT(*) AS Cantidad "+;
					"  FROM FACTURA "+;
					" WHERE ID_SOCIO = " + oMySQL.Fox2SQL(tnId_Socio) +;
					"   AND SUBSTR(COBRO,1,1) != 'C' " +;  &&+ oMySQL.Fox2SQL(tcCobro) 
					" GROUP BY ID_SOCIO"
			oMySQL.Ejecutar(lcSQL, "curNuevaInsta", THIS.DataSession)
		   	IF RECCOUNT("curNuevaInsta") > 0
		   		lnResult = curNuevaInsta.Cantidad
			ENDIF
		CATCH TO loEx
			lnResult = -1
			lcLog ="   ProcedureInitial: GenLect.CantidadFacturas()"
			oError.Guardar(loEx, lcLog)
		ENDTRY
		USE IN SELECT("curNuevaInsta")
		SELECT(lnArea)
		RETURN lnResult
	ENDFUNC

	*********************************************************
	* Método........: TieneUnicoInstalam
	* Return........: Boolean
	* Descripción...: Descripción de TieneUnicoInstalam
	* Fecha.........: 14-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION TieneUnicoInstalam(tnID_Socio AS Integer) AS Boolean
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, llResult
		lnArea = SELECT()
		llResult = .F.
		TRY
			IF USED("_INSTALAM")
				lcSQL = "SELECT * "+;
						"  FROM _INSTALAM " +;
						" WHERE ID_SOCIO = " + oMySQL.FOX2SQL(tnID_Socio) 
				oMySQL.EjecutarCursor(lcSQL, "cINSTALAM", THIS.DataSession)
				IF USED("cINSTALAM")
					IF (RECCOUNT("cINSTALAM") > 0)
						llResult = (cINSTALAM.NuevaIns == .T.)
					ENDIF
				ENDIF
			ENDIF
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.TieneUnicoInstalam()"
			oError.Guardar(loEx, lcLog)
		ENDTRY
		USE IN SELECT("cINSTALAM")
		SELECT(lnArea)
		RETURN llResult
	ENDFUNC
	

	*********************************************************
	* Método........: CrearCursorInstalacionesNuevas
	* Descripción...: CrearCursorInstalacionesNuevas para verificar si hay lecturas que no de deben validar.
	* Fecha.........: 13-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE CrearCursorInstalacionesNuevas(tnID_GenFact AS Integer, tcZonaRuta AS String, tdFechaLect AS Date, tcCobro AS String)
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, lcCobroFin, lnCantCli, lnNroMeses, lnRecCount, lcTop, ldFechaIni, ldFechaFin, lcOrderBy
		lnArea = SELECT()
		TRY
			IF (THIS.lCrearCursorInstalacionesNuevas = .T.)
				THIS.lCrearCursorInstalacionesNuevas = .F.

				ldFechaFin = tdFechaLect - pGlobal.DiasInstal
				ldFechaIni = tdFechaLect - 31
				lcCobroFin =  oUtil.AAMMANT(tcCobro)
				lcSQL = " SELECT I.ID_INSTALA, I.ID_Socio, I.COD_SOCIO, I.LectIni AS LectAnt, I.Id_Medidor," +;
			 			"		 I.F_INSTALA, F_ACTIVA, I.F_TRABAJO, I.F_FACTURAR, I.NuevaIns " +;
	    				"   FROM INSTALAM I " +;
	    				"  WHERE I.F_Facturar >= " + oMySQL.Fox2SQL(ldFechaIni) +;
	    				"    AND I.F_Facturar <= " + oMySQL.Fox2SQL(ldFechaFin) +;
	    		   		"    AND I.Es_Instala = 2 " +;
	    		   		"    AND I.NuevaIns = " + oMySQL.Fox2SQL(.T.) +;
	    		   		"    AND SUBSTR(I.Cod_Socio,1,4) = " + oMySQL.FOX2SQL(tcZonaRuta) +;
	    		   		"    AND I.Id_Medidor > 0 " +;
	    		   		"  ORDER BY I.COD_SOCIO "
				oMySQL.Ejecutar(lcSQL, "_INSTALAM", THIS.DataSession)
				*STRTOFILE(lcSQL, oError.PathLOGs + "\_INSTALAM_NUEVOS.TXT")
				lcSQL = "SELECT ID_SOCIO " +;
						"  FROM GENLECT " +;
						" WHERE COBRO = " + oMySQL.FOX2SQL(tcCobro) +; 
						"    AND ID_GENFACT = " + oMySQL.FOX2SQL(tnID_GenFact) 
				lcListSociosInstal = oMySQL.CrearTemporal(lcSQL)
				lcSQL = "SELECT COUNT(*) AS Cantidad FROM " + lcListSociosInstal
				oMySQL.Ejecutar(lcSQL, "curCantLect", THIS.DataSession)
				lnCantCli = 1
				IF NOT ISNULL (curCantLect.Cantidad)
					lnCantCli = curCantLect.Cantidad
				ENDIF

				lnNroMeses = 36
				lnRecCount = lnNroMeses * lnCantCli
				IF lnRecCount > 0
					lcTop = oMySQL.Fox2SQL(lnRecCount)
				ELSE
					lcTop =  "1"
				ENDIF
				lcOrderBy = ""
				IF(oMySQL.Tipo = 0)
					lcOrderBy = " ORDER BY L.ID_SOCIO, L.COD_SOCIO DESC "
				ENDIF 
				loHoraIni = TIME(1)
				lcCobroIni = THIS.GetAAMMANT(lcCobroFin, lnNroMeses)
				lcSQL = "SELECT TOP (" + lcTop + ") L.* " +;
					 	"  FROM GENLECT L " +;
						" WHERE " +;
						"       L.Cobro >= " + oMySQL.Fox2SQL(lcCobroIni) +;
				 		"   AND L.Cobro <= " + oMySQL.Fox2SQL(lcCobroFin) +;
				 		"	AND	L.ID_SOCIO IN (SELECT ID_SOCIO FROM " + lcListSociosInstal + " ) " +;
				 		" " + lcOrderBy				           
            	loHoraIni = TIME(1)
				oMySQL.Ejecutar(lcSQL, "_HISTLECT", THIS.DataSession)				
				loHoraFin = TIME(1)
				*IF NOT EMPTY(tcTime2)
					*tcTime2 = oError.GetTimeProcess(loHoraIni, loHoraFin)
				*ENDIF
			ENDIF
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.CrearCursorInstalacionesNuevas()"+ THIS._Enter  + " SQL >> " + THIS._Enter + lcSQL
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC
	
	*********************************************************
	* Método........: CrearCursorCambioMedidores
	* Descripción...: CrearCursorCambioMedidores para verificar si hay lecturas que no de deben validar.
	* Fecha.........: 13-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE CrearCursorCambioMedidores(tnID_GenFact AS Integer, tcZonaRuta AS String, tdFechaLect AS Date, tcCobro AS String)
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, lcCobroFin, lnCantCli, lnNroMeses, lnRecCount, lcTop, ldFechaIni, ldFechaFin, lcOrderBy
		lnArea = SELECT()
		TRY
			IF (THIS.lCrearCursorCambioMedidores = .T.)
				THIS.lCrearCursorInstalacionesNuevas = .F.

				ldFechaFin = tdFechaLect - pGlobal.DiasInstal
				ldFechaIni = tdFechaLect - 31
				lcSQL = " SELECT I.ID_SOCIMED, I.ID_Socio, I.COD_SOCIO, I.LectIni AS LectAnt, I.F_SociMed, " +;
						  oMySQL.Fox2SQL(ldFechaIni) + " AS FechaAct, I.F_Trabajo, I.F_Facturar " +;
						"   FROM SOCIMEDI I " +;
						"  WHERE I.F_Facturar >= " + oMySQL.Fox2SQL(ldFechaIni) +;
						"    AND I.F_Facturar <= " + oMySQL.Fox2SQL(ldFechaFin) +;
						"    AND I.Es_SociMed = 2" +;
						"    AND SUBSTR(I.Cod_Socio,1,4) = " + oMySQL.FOX2SQL(tcZonaRuta) +;
						"  ORDER BY I.COD_SOCIO"
				oMySQL.Ejecutar(lcSQL, "_SOCIMEDI", THIS.DataSession)

			ENDIF
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.CrearCursorCambioMedidores()"+ THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC

	*********************************************************
	* Método........: CrearCursorRegularizacionBajaTemporal
	* Descripción...: CrearCursorRegularizacionBajaTemporal para verificar si hay lecturas que no de deben validar.
	* Fecha.........: 13-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE CrearCursorRegularizacionBajaTemporal(tnID_GenFact AS Integer, tcZonaRuta AS String, tdFechaLect AS Date,	 tcCobro AS String)
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL, ldFechaIni, ldFechaFin
		lnArea = SELECT()
		TRY
			IF (THIS.lCrearCursorRegularizacionBajaTemporal = .T.)
				THIS.lCrearCursorRegularizacionBajaTemporal = .F.

				ldFechaFin = tdFechaLect - pGlobal.DiasInstal
				ldFechaIni = tdFechaLect - 31
				lcSQL = " SELECT I.ID_INSTALA, I.ID_Socio, I.COD_SOCIO, I.LectIni AS LectAnt, I.Id_Medidor," +;
			 			"		 I.F_INSTALA, F_ACTIVA, I.F_TRABAJO, I.F_FACTURAR, I.NuevaIns " +;
	    				"   FROM INSTALAM I " +;
	    				"  WHERE I.F_Facturar >= " + oMySQL.Fox2SQL(ldFechaIni) +;
	    				"    AND I.F_Facturar <= " + oMySQL.Fox2SQL(ldFechaFin) +;
	    		   		"    AND I.Es_Instala = 2" +;
	    		   		"    AND I.TIPOINSTAL = 1" +;
	    		   		"    AND I.NuevaIns = " + oMySQL.Fox2SQL(.F.) +;
	    		   		"    AND SUBSTR(I.Cod_Socio,1,4) = " + oMySQL.FOX2SQL(tcZonaRuta) +;
	    		   		"    AND I.Id_Medidor = 0" +;
	    		   		"  ORDER BY I.COD_SOCIO"
	    		*oError.GuardarLog("_INSTALAM2", lcSQL)
				oMySQL.Ejecutar(lcSQL, "_INSTALAM2", THIS.DataSession)
			ENDIF
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.CrearCursorRegularizacionBajaTemporal()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC

	*********************************************************
    * Método........: CantidadSociosEnPlanilla
    * Return........: Integer
    * Descripción...: Descripción de CantidadSociosEnPlanilla
    * Fecha.........: 27-06-2019
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION CantidadSociosEnPlanilla(tnID_GenFact AS Integer) AS Integer
		LOCAL loEx AS Exception, lcLog AS String
		LOCAL lnArea, lnResult
		lnArea = SELECT()
		lnResult = 0
		TRY 
			lcSQL = "SELECT ID_GENFACT, Count(*) AS Cantidad " +;
					"  FROM GENLECT " +;
					" WHERE ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact) +;
					" GROUP BY ID_GENFACT"
			oMySQL.Ejecutar(lcSQL, "curCantidad", THIS.DataSession)
		   	IF RECCOUNT("curCantidad") > 0
		   		lnResult = curCantidad.Cantidad
			ENDIF
		CATCH TO loEx
			lnResult = -1
			lcLog = [  ProcedureInitial: GenLect.CantidadSociosEnPlanilla()] 
			oError.Guardar(loEx, lcLog)
		ENDTRY
		USE IN SELECT("curCantidad")
		SELECT(lnArea)
		RETURN lnResult
	ENDFUNC
	
	*********************************************************
	* Método........: GetErrorMsgBy
	* Return........: String
	* Descripción...: Descripción de GetErrorMsgBy
	* Fecha.........: 06-05-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION GetErrorMsgBy(tnRegla AS Integer) AS String
		LOCAL lcTipoConsumoNombre, lcError 
		lcTipoConsumoNombre = ""
		lcError = ""

		IF(tnRegla == THIS.oReglaLectura.INSTALACION_NUEVA)
			lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
			lcError = "[V][" + lcTipoConsumoNombre + "] [Instalación Nueva]"
		ELSE
			IF(tnRegla == THIS.oReglaLectura.CAMBIO_DE_MEDIDOR)
				lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
				lcError = "[V][" + lcTipoConsumoNombre + "] [Cambio de Medidor]"
			ELSE
				IF(tnRegla == THIS.oReglaLectura.REGULARIZACION_BAJA_TEMPORAL)
					lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)
					lcError = "[V][" + lcTipoConsumoNombre + "] [Regularización Baja Temporal]"
				ELSE
					lcError = THIS.ErrorMsg
				ENDIF 
			ENDIF 
		ENDIF
		RETURN lcError

	ENDFUNC
	
	*********************************************************
	* Método........: SeValida
	* Return........: Boolean
	* Descripción...: Descripción de SeValida
	* Fecha.........: 07-05-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Log...........:
	*				 Fecha:13-05-2019, By:Ing. Alfonzo Salgado Flores, Nota: Add Parametro tnID_Categ
	*********************************************************
	FUNCTION SeValida(tnMedia AS Integer, tnConsumo AS Integer, tnID_Categ AS Integer) AS Boolean
		LOCAL lnArea, lnValorRef, llSeValida
		lnArea = SELECT()
		IF NOT USED("CATECONS")
			oMySQL.GetTablaIndexada("CATECONS", "CATECONS", "*", THIS.DataSession)
		ENDIF
		IF NOT EMPTY(tnID_Categ)
			SELECT * FROM CATECONS ;
			 WHERE ID_Categ = tnID_Categ;
			   AND Inicio = 0 ;
			  INTO CURSOR _CATECONS
			IF (RECCOUNT('_CATECONS') > 0)
				lnValorRef = _CATECONS.Fin
			ENDIF
		ENDIF
		IF(tnConsumo > 0)
			llSeValida = THIS.oMediaConsumo.SeValida(tnMedia, tnConsumo, lnValorRef)
		ELSE
			llSeValida = .T.
		ENDIF
		SELECT(lnArea)
		RETURN llSeValida
	ENDFUNC

	*********************************************************
    * Método........: CargarAAuditoria_Anor_Ajus
    * Descripcion...: Descripcion de CargarAAuditoria_Anor_Ajus
    * Fecha.........: 29-05-2019
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE CargarAAuditoria_Anor_Ajus(tcCursor AS String)
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, lcStackTrace
    	lnArea = SELECT()
    	TRY
    		&&Asumimos que TEMPORAL TENGA LAS COLUMNAS ID_GENFACT, ID_SOCIO
    		SELECT TEMPORAL
    		SCAN ALL
    			REPLACE TEMPORAL.AnorLect WITH THIS.oAnorLect.GetCantRec(TEMPORAL.ID_GenFact, TEMPORAL.ID_Socio)
    			REPLACE TEMPORAL.AjusLect WITH THIS.oAjusLect.GetCantRec(TEMPORAL.ID_GenFact, TEMPORAL.ID_Socio)
    		ENDSCAN
    		SELECT TEMPORAL 
			GO TOP
    	CATCH TO loEx
            lcLog = "	Metodo...: GenLect.CargarAAuditoria_Anor_Ajus() "
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    ENDPROC

    *********************************************************
    * Método........: GetGenFactCronologia
    * Descripción...: Descripión de GetGenFactCronologia
    * Fecha.........: 26-06-2019
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE GetGenFactCronologia(tdFechaFacturar AS Date, tcZonaRuta AS String)
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, lnDIa
    	LOCAL ldFechaIni, ldF_Facturar, lcCobroModelo
    	lnArea = SELECT()
    	TRY
			CREATE CURSOR HISTLECT( ;
				ID_GENFACT N(10),;
				COBRO C(8),;
				ID_ZONA N(4),;
				RUTA N(4),;
				F_CREACION D(8),;
				F_GENLECT D(8),;
				F_GENFACT D(8),;
				FECHAINI D(8),;
				FECHAFIN D(8),;
				G_GENLECT L,;
				GENERADO L,;
				PERTENECE L;
			)

			&& Caso Especial para los dias Domingos
			lnDia = DOW(tdFechaFacturar - 1)
			lnFactor = 1
			IF(lnDia = 1)
				lnFactor = 2
			ENDIF

			lcSQL = " SELECT TOP(1) G.ID_GENFACT, G.COBRO " +;
					"   FROM GENFACT G " +;
					"  WHERE STRTRAN(STR(G.Id_Zona, 2) + STR(G.Ruta, 2), ' ', '0') = " + oMySQL.Fox2SQL(tcZonaRuta) +;
					"  ORDER BY G.ID_GENFACT DESC " 
			oMySQL.Ejecutar(lcSQL, "curCobroUltimo", THIS.DataSession)
			IF RECCOUNT("curCobroUltimo") > 0 
				lcCobroSigTemp = oUtil.SgteAAMM(curCobroUltimo.Cobro)
			ELSE
				lcCobroSigTemp = THIS.GetCobroSig(pGlobal.Fecha)
			ENDIF
			ldF_Facturar = tdFechaFacturar
			lcSQL = " SELECT TOP(1) G.ID_GENFACT, G.COBRO, G.ID_ZONA, G.RUTA, " +; 
					"        G.USRFECHA AS F_CREACION, G.F_GENLECT, G.F_GENFACT, " +;
					"		 CTOD('//') AS FechaIni, CTOD('//') AS FechaFin, " +;
					"		 G.G_GENLECT, G.GENERADO, " + oMySQL.Fox2SQL(.F.) + " AS PERTENECE " +;
					"   FROM GENFACT G " +;
					"  WHERE STRTRAN(STR(G.Id_Zona, 2) + STR(G.Ruta, 2), ' ', '0') = " + oMySQL.Fox2SQL(tcZonaRuta) +;
					"    AND G.USRFECHA  >= " + oMySQL.Fox2SQL(ldF_Facturar - lnFactor ) +;
					"    AND COBRO <> " + oMySQL.Fox2SQL(lcCobroSigTemp)  +;
					"  ORDER BY G.ID_GENFACT DESC " 
			oMySQL.Ejecutar(lcSQL, "curGenFact", THIS.DataSession)			
			IF RECCOUNT("curGenFact") > 0
				*WAIT WINDOW "bloque 1" NOWAIT
				SELECT("curGenFact")
				GO TOP 
				lcSQL = " SELECT TOP(4) G.ID_GENFACT, G.COBRO, G.ID_ZONA, G.RUTA, " +; 
						"        G.USRFECHA AS F_CREACION, G.F_GENLECT, G.F_GENFACT, " +;
						"		 CTOD('//') AS FechaIni, CTOD('//') AS FechaFin, " +;
						"		 G.G_GENLECT, G.GENERADO, " + oMySQL.Fox2SQL(.F.) + " AS PERTENECE " +;
						"   FROM GENFACT G " +;
						"  WHERE STRTRAN(STR(G.Id_Zona, 2) + STR(G.Ruta, 2), ' ', '0') = " + oMySQL.Fox2SQL(tcZonaRuta) +;
						"    AND G.COBRO <= " + oMySQL.Fox2SQL(curGenFact.Cobro)+;
						"  ORDER BY G.ID_GENFACT DESC " 
				oMySQL.Ejecutar(lcSQL, "curHistLect", THIS.DataSession)
				SELECT ("HISTLECT")
				APPEND FROM DBF("curHistLect")
				GO TOP
				SCAN ALL
					*wait window "PROCESANDO ." + HISTLECT.Cobro
					ldFechaIni = THIS.GetFechaAntByCursor(HISTLECT.Cobro)
					SELECT HISTLECT
					IF NOT EMPTY(ldFechaIni)
						*WAIT WINDOW "Fecha Valida  :" + DTOC(ldFechaIni)
						REPLACE HISTLECT.FechaIni WITH ldFechaIni
						REPLACE HISTLECT.FechaFin WITH HISTLECT.F_GenLect
						IF((HISTLECT.FechaIni <= ldF_Facturar ) AND ( ldF_Facturar <= HISTLECT.FechaFin))
							REPLACE HISTLECT.PERTENECE WITH .T.
						ENDIF
					ELSE
						*WAIT WINDOW "Fecha NOT Valida :" + DTOC(HISTLECT.F_GenLect)
						REPLACE HISTLECT.FechaIni WITH HISTLECT.F_GenLect - 30
						REPLACE HISTLECT.FechaFin WITH HISTLECT.F_GenLect
						IF((HISTLECT.FechaIni <= ldF_Facturar ) AND ( ldF_Facturar <= HISTLECT.FechaFin))
							REPLACE HISTLECT.PERTENECE WITH .T.
						ENDIF
					ENDIF
				ENDSCAN
			ELSE
				*WAIT WINDOW "bloque 2" NOWAIT
				lcSQL = " SELECT TOP(1) G.ID_GENFACT, G.COBRO, G.ID_ZONA, G.RUTA, " +; 
						"        G.USRFECHA AS F_CREACION, G.F_GENLECT, G.F_GENFACT, " +;
						"		 CTOD('//') AS FechaIni, CTOD('//') AS FechaFin, " +;
						"		 G.G_GENLECT, G.GENERADO, " + oMySQL.Fox2SQL(.F.) + " AS PERTENECE " +;
						"   FROM GENFACT G " +;
						"  WHERE STRTRAN(STR(G.Id_Zona, 2) + STR(G.Ruta, 2), ' ', '0') = " + oMySQL.Fox2SQL(tcZonaRuta) +;
						"    AND COBRO = " + oMySQL.Fox2SQL(lcCobroSigTemp)  +;
						"  ORDER BY G.ID_GENFACT DESC "
				oMySQL.Ejecutar(lcSQL, "curGenFactActual", THIS.DataSession)
				IF(RECCOUNT("curGenFactActual") > 0) 
					*Es cuando ya la planilla GenFact de lcCobroSigTemp ah sido creado
					*Lo cual quiere decir que la operadora de SOCIMEDI esta metiendo ya en proceso de lecturacion.. OJO...
					WAIT WINDOW "bloque curGenFactActual.RecCount > 0 " 
					ldFechaIni = THIS.GetFechaAntGenFact(lcCobroSigTemp, tcZonaRuta  )
					SELECT ("HISTLECT")
					APPEND FROM DBF("curGenFactActual")
					GO TOP
					IF NOT EMPTY(ldFechaIni)
						REPLACE HISTLECT.FechaIni WITH ldFechaIni
						IF (HISTLECT.Generado = .F.) AND (HISTLECT.F_Creacion < HISTLECT.F_GenLect)
							REPLACE HISTLECT.FechaFin WITH HISTLECT.F_Creacion
						ELSE
							REPLACE HISTLECT.FechaFin WITH HISTLECT.F_GENLECT 
						ENDIF
						IF((HISTLECT.FechaIni <= ldF_Facturar ) AND ( ldF_Facturar <= HISTLECT.FechaFin))
							REPLACE HISTLECT.PERTENECE WITH .T.
						ENDIF
					ELSE
						REPLACE HISTLECT.FechaIni WITH HISTLECT.F_GenLect - 30
						REPLACE HISTLECT.FechaFin WITH HISTLECT.F_GenLect
						IF((HISTLECT.FechaIni <= ldF_Facturar ) AND ( ldF_Facturar <= HISTLECT.FechaFin))
							REPLACE HISTLECT.PERTENECE WITH .T.
						ENDIF
					ENDIF
				ELSE
					*Es cuando la planilla de lcCobroSigTemp aun no ah sido creado ni obtenido lecturas... 
					*Se hace un modelo con las fechas ini y fin aproximados.....
				 	lcSQL = " SELECT TOP(1) G.ID_GENFACT, G.COBRO, G.ID_ZONA, G.RUTA, " +; 
							"        G.USRFECHA AS F_CREACION, G.F_GENLECT, G.F_GENFACT, " +;
							"		 CTOD('//') AS FechaIni, CTOD('//') AS FechaFin, " +;
							"		 G.G_GENLECT, G.GENERADO, " + oMySQL.Fox2SQL(.F.) + " AS PERTENECE " +;
							"   FROM GENFACT G " +;
							"  WHERE STRTRAN(STR(G.Id_Zona, 2) + STR(G.Ruta, 2), ' ', '0') = " + oMySQL.Fox2SQL(tcZonaRuta) +;
							"    AND G.USRFECHA  < " + oMySQL.Fox2SQL(ldF_Facturar) +;
							"    AND COBRO <> " + oMySQL.Fox2SQL(lcCobroSigTemp)  +;
							"  ORDER BY G.ID_GENFACT DESC " 
					oMySQL.Ejecutar(lcSQL, "curGenFactModelo", THIS.DataSession)
					lcCobroModelo = lcCobroSigTemp
					SELECT ("HISTLECT")
					APPEND FROM DBF("curGenFactModelo")
					GO TOP
					REPLACE HISTLECT.FechaIni WITH HISTLECT.F_GENLECT + 1
					REPLACE HISTLECT.FechaFin WITH HISTLECT.FechaIni + 30
					REPLACE HISTLECT.F_GENLECT WITH CTOD('//')
					REPLACE HISTLECT.F_GENFACT WITH CTOD('//')
					REPLACE HISTLECT.F_CREACION WITH CTOD('//')
					REPLACE HISTLECT.G_GENLECT WITH .F.
					REPLACE HISTLECT.GENERADO WITH .F.
					REPLACE HISTLECT.ID_GENFACT WITH 0
					REPLACE HISTLECT.Cobro WITH lcCobroModelo
					IF((HISTLECT.FechaIni <= ldF_Facturar ) AND ( ldF_Facturar <= HISTLECT.FechaFin))
						REPLACE HISTLECT.PERTENECE WITH .T.
					ENDIF
				ENDIF
			ENDIF
    	CATCH TO loEx
    		lcLog = "  ProcedureInitial: GenLect.GetGenFactCronologia()" + THIS._Enter 
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    ENDPROC
    
		
	*********************************************************
	* Método........: GetFechaAntByCursor
	* Return........: Date
	* Descripción...: Obtiene la F_GenLect anterior a tcCobro y de la tcZonaRuta
	* Fecha.........: 26-06-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION GetFechaAntByCursor(tcCobro AS String) AS Date
		LOCAL lcSQL, lcCobro, ldFecha, lnArea
		lnArea = SELECT()
		ldFecha = CTOD('//')
		lcSQL = " SELECT TOP (1) ID_GENFACT, F_GENLECT " +;
				"   FROM curHistLect " +;
				"  WHERE COBRO < " + oMySQL.Fox2SQL(tcCobro) +;
				"  ORDER BY ID_GENFACT DESC " 
		oMySQL.EjecutarCursor(lcSQL, "__curFecha", THIS.DataSession)
		IF RECCOUNT("__curFecha") > 0
			ldFecha = __curFecha.F_GENLECT + 1
		ENDIF
		USE IN SELECT("__curFecha")
		SELECT(lnArea)
		RETURN ldFecha
	ENDFUNC

	*********************************************************
	* Método........: GetFechaAntGenFact
	* Return........: Date
	* Descripción...: Obtiene la F_GenLect anterior a tcCobro y de la tcZonaRuta
	* Fecha.........: 26-06-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION GetFechaAntGenFact(tcCobro AS String, tcZonaRuta AS String ) AS Date
		LOCAL lcSQL, lcCobro, ldFecha, lnArea
		lnArea = SELECT()
		ldFecha = CTOD('//')
		lcSQL = " SELECT TOP (1) G.ID_GENFACT, G.COBRO, G.USRFECHA, G.F_GENFACT,  G.F_GENLECT " +;
				"   FROM GENFACT G" +;
				"  WHERE STRTRAN(STR(G.Id_Zona, 2) + STR(G.Ruta, 2), ' ', '0') = " + oMySQL.Fox2SQL(tcZonaRuta)  +;
				"    AND G.COBRO < " + oMySQL.Fox2SQL(tcCobro) +;
				"  ORDER BY G.ID_GENFACT DESC " 
		oMySQL.Ejecutar(lcSQL, "__curFecha", THIS.DataSession)
		IF RECCOUNT("__curFecha") > 0
			ldFecha = __curFecha.F_GENLECT + 1
		ENDIF
		USE IN SELECT("__curFecha")
		SELECT(lnArea)
		RETURN ldFecha
	ENDFUNC

	*********************************************************
	* Método........: GetCobroSig
	* Return........: String Cobro(AAAA-MM)
	* Descripción...: Obtiene la F_GenLect anterior a tcCobro y de la tcZonaRuta
	* Fecha.........: 26-06-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	FUNCTION GetCobroSig(tdFecha AS Date ) AS String
		IF EMPTY(tdFecha) 
	   		RETURN '       ' 
	  	ELSE
	  		IF (MONTH(tdFecha) + 1 <= 12)
		   		RETURN STRTRAN(STR(YEAR(tdFecha), 4) + '-' + STR(MONTH(tdFecha) + 1, 2), ' ', '0')
		   	ELSE
		   		RETURN STRTRAN(STR(YEAR(tdFecha) + 1, 4)+'-'+STR(1,2),' ','0')
		   	ENDIF
		ENDIF
	ENDFUNC

	*********************************************************
	* Método........: ObtenerPlanillaLectura
	* Descripción...: Descripión de ObtenerPlanillaLectura
	* Fecha.........: 03-07-2019
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE ObtenerPlanillaLectura(tnID_GenFact AS Integer, tcZonaRuta AS String, tlVerPromedio AS Boolean)
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		lnArea = SELECT()
		TRY
			SET DATASESSION TO THIS.DataSession &&Importante..
			CREATE CURSOR TEMPORAL ;
					       (ID_GenFact N(10),;
					        Cobro      C(7),;
					        ID_Socio   N(10),;
					        Cod_Socio  C(10),;
					        Detalle    C(70),;
					        LectAnt    N(6),;
					        LectAct    N(6),;
					        LectValida N(6),;
					        Consumo    N(6),;
					        ConsumoFac N(6),;
					        ConsumoDeb N(6),;
					        Media      N(6),;
					        Media_ant  L,;
					        Fecha      D,;
					        Variacion  N(10,2),;
					        Error      C(250),;
					        ID_Categ   N(3),;
					        ID_Medidor N(10),;
					        Id_MediEst N(3),;
					        ID_MediEs2 N(3),;
					        MqM		   L,;
					        Hora	   C(8),;
					        AnorLect   N(2),;
					        AjusLect   N(2))

			*INDEX ON ID_GenFact  TAG ID_GenFact  ADDITIVE
			INDEX ON ID_Socio  TAG ID_Socio  ADDITIVE
			INDEX ON Cod_Socio TAG Cod_Socio ADDITIVE
			INDEX ON ID_Categ TAG ID_Categ ADDITIVE
			INDEX ON ID_Medidor TAG ID_Medidor ADDITIVE
			CREATE CURSOR cReporte(VerPromedio L)
			SELECT CReporte
			APPEND BLANK 
			REPLACE VerPromedio WITH tlVerPromedio
			lcSQL = "SELECT L.ID_GenFact, L.Cobro, L.ID_SOCIO, L.COD_SOCIO, '    		' AS Detalle, " +;
					"		L.LectAnt, L.LectAct, L.LectAct AS LectValida," +;
					"       L.Consumo, L.ConsumoFac, L.ConsumoDeb, L.Media, L.Media_Ant, " +;
					"		CTOD('//') AS Fecha, 000000.00 AS Variacion, " +;					
					"		'                 ' AS Error, "+;
					"		L.Id_Categ, L.ID_Medidor, L.Id_MediEst, " +;
					" 		000 AS ID_MediEs2, "+oMySQL.Fox2SQL(.F.) +" AS MqM, "+;
					"		 '        ' AS HORA, 000000 AS AnorLect, 000000 AS AjusLect   " +;
					"  FROM GENLECT L" +;
					" WHERE ID_GENFACT =  " + oMySQL.Fox2SQL(tnID_GenFact)
			oMySQL.Ejecutar(lcSQL, "_TEMP", THIS.DataSession)
			lcCurSocios = oMySQL.CrearTemporal(lcSQL)
			SELECT TEMPORAL
			APPEND FROM DBF("_TEMP")
			lcSQL = "SELECT * FROM SOCIOPER WHERE ID_SOCIO IN(SELECT ID_SOCIO FROM " + lcCurSocios + ")"
			oMySQL.Ejecutar(lcSQL, "SOCIOPER", THIS.DataSession)
			INDEX ON ID_Socio TAG ID_Socio ADDITIVE

			oMySQL.GetTablaIndexada("CATEGORI", "CATEGORI", "*", THIS.DataSession)
			oMySQL.GetTablaIndexada("MARCMEDI", "MARCMEDI", "*", THIS.DataSession)

			lcSQL = "SELECT * FROM MEDIDOR WHERE ID_MEDIDOR IN(SELECT ID_MEDIDOR FROM " + lcCurSocios + ")"
			oMySQL.Ejecutar(lcSQL, "MEDIDOR", THIS.DataSession)
			SELECT MEDIDOR
			SET RELATION TO ID_MarcMed INTO MARCMEDI ADDITIVE
			INDEX ON Id_Socio TAG Id_Socio ADDITIVE

			lcSQL = " SELECT S.Id_Socio, C.* "+;
					"	FROM CORTE C, SOCIOS S "+;
					"  WHERE C.Id_Corte = S.Id_Corte "+;
					"	 AND SUBSTR(S.Cod_Socio,1,4) = "+ oMySQL.FOX2SQL(tcZonaRuta)
			oMySQL.Ejecutar(lcSQL,"CORTE", THIS.DataSession)
			INDEX ON Id_Socio TAG Id_Socio ADDITIVE
			
			SELECT TEMPORAL
			SET RELATION TO ID_Socio INTO SOCIOPER   ADDITIVE
			SET RELATION TO ID_Socio INTO CORTE   ADDITIVE
			SET RELATION TO ID_Socio INTO MEDIDOR ADDITIVE
			SET RELATION TO ID_Categ INTO CATEGORI ADDITIVE
			*SET RELATION TO ID_GenFact INTO GENFACT ADDITIVE
			SET ORDER TO COD_Socio

		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.ObtenerPlanillaLectura()"
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC

	
	*********************************************************
	* Método........: AuditoriaAnormalidadesEnPlanilla
	* Descripción...: Descripión de AuditoriaAnormalidadesEnPlanilla
	* Fecha.........: 01-01-2014
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE AuditoriaAnormalidadesEnPlanilla(tcCobro AS String)
	&&BEGIN
		LOCAL lnArea, lcCobroAnt, lcListaClientesID, lcLookTabla
		LOCAL loEx AS Exception, lcLog AS String
		LOCAL ldFechaIni, lcRecTotal, lcRecNo
		lnArea = SELECT()		
		TRY
			SELECT TEMPORAL
			lcRecTotal = ALLTRIM(STR(RECCOUNT("TEMPORAL"),10))
		 	SELECT TEMPORAL
		 	SET ORDER TO 
		 	GO TOP
		 	SCAN ALL
		 		lcRecNo = ALLTRIM(STR(RECNO("TEMPORAL"),10))
		 		WAIT WINDOW "Procesando Socio : " + ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) +" - " + TEMPORAL.Cod_Socio + CHR(13) +;
							"Registro: " + lcRecNo + " de " + lcRecTotal	 NOWAIT 
			  	THIS.ObtenerIndiceAnormalidad(TEMPORAL.ID_SOCIO, tcCobro)
			  	m.tecla = INKEY()
                IF m.tecla = 27 
                    IF THIS.Parent.EsSi("Advertencia...","Desea Cancelar, Auditoria de Anormalidades")
    	                UNLOCK ALL
                        EXIT
                    ENDIF
                ENDIF        
		 	ENDSCAN
		 	SELECT TEMPORAL
		 	TRY
		 		INDEX ON ID_Socio TAG ID_Socio ADDITIVE
				INDEX ON ID_ESMODA TAG ID_ESMODA ADDITIVE
				INDEX ON CANTANOR TAG CANTANOR ADDITIVE
				INDEX ON INDICEUSO TAG INDICEUSO ADDITIVE
				INDEX ON COD_Socio TAG COD_Socio ADDITIVE	
		 	CATCH
		 	ENDTRY
		 	SET ORDER TO INDICEUSO DESC
		 	GO TOP
		CATCH TO loEx
			lcLog = "  Procedure: GenLect.ObtenerLecturas()" + THIS._Enter 
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC

	*********************************************************
	* Método........: [MAIN]SetAnormalidadLey1294
	* Descripción...: Descripión de SetAnormalidadLey1294
	* Fecha.........: 14-04-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	* Nota......... : Cambiado el nombres chek copia 17-07-2018.
	*********************************************************
	PROCEDURE SetAnormalidadLey1294(tnID_GenFact AS Integer, tcCobro AS String, tdF_GenLect As Date,;
								   tlPorcLect_Usuario AS Boolean, tlValidarMinimo AS Boolean,;
								   tlVerAnormalidad2 AS Boolean, tlValidarAnorAjus AS Boolean,;
								   tlMostrarConsumoMenorFactorMinimo AS Boolean)
	&&BEGIN
		PRIVATE lcTipoConsumoNombre, lnTipoConsumo
		LOCAL lnArea, lnValido, lcError, lnId_MediEst
		LOCAL lnId_MediEsS, lnConsumo, lnConsumoFac, lnLectAct
		LOCAL llAnorLect, llAjusLect

		lnArea = SELECT()

		THIS.lMostrarConsumoMenorFactorMinimo = tlMostrarConsumoMenorFactorMinimo		
		THIS.lPorcLect_Usuario = tlPorcLect_Usuario
		THIS.lValidarMinimo = tlValidarMinimo
		THIS.lVerAnormalidad2 = tlVerAnormalidad2

	    THIS.nPorcLECT = 0.5 && Hay que cambiar por la variacion de consumo
	    IF USED("GENFACT")
	    	THIS.nPorcLECT = GENFACT.PorcLect
	    ENDIF

		THIS.ErrorMsg = ""
		lcFechaAnterior = oMySQL.FOX2SQL(pGlobal.Fecha-365)
		lcCurHistFact = THIS.DO_HISTOFACT(tdF_GenLect, 0)

		TRY
			IF(oMySQL.Tipo = 0)
				USE (&lcCurHistFact) IN 0 SHARED
			ENDIF
		CATCH TO loEx
			THIS.ErrorMsg = "Historico de Facturas no Disponible" + CHR(13) +;
							"Consulte Administrador" + CHR(13) + ;
							"Error : " + loEx.Message
		ENDTRY

		IF(NOT EMPTY(THIS.ErrorMsg))
			RETURN
		ENDIF

	  	IF NOT USED("MEDIESTA")
	  		oMySQL.GetTablaIndexada("MEDIESTA","MEDIESTA","*",THIS.DataSession)
	  	ENDIF
	  	IF NOT USED("CATECONS")
	  		oMySQL.GetTablaIndexada("CATECONS","CATECONS","*",THIS.DataSession)
	  	ENDIF
	  	lcSQL = "SELECT * FROM GENLECT WHERE ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact)
	  	oMySQL.Ejecutar(lcSQL, "_TEMPORAL", THIS.DataSession)
		SELECT _TEMPORAL
		SCAN ALL
			
			SELECT _TEMPORAL
			lcTipoConsumoNombre = ""
			lcVariacion = "00.00%"
			lcError = ""
			THIS.lMenorQueMinimo = .F.
			WAIT WINDOW "PROCESANDO..: " + _TEMPORAL.Cod_Socio NOWAIT
			lnValido = THIS.ValidarLectura(_TEMPORAL.LectAnt, _TEMPORAL.LectAct, _TEMPORAL.Consumo,;
										   _TEMPORAL.Media, _TEMPORAL.Id_MediEst, ;
										   _TEMPORAL.Id_Medidor, _TEMPORAL.Id_Categ, _TEMPORAL.ConsumoFac)
			lcVariacion = ALLTRIM(STR(THIS.nPorcentajeDesviacion,10,2)) 			
			SELECT _TEMPORAL
				lcTipoConsumoNombre = THIS.oMedidorInfo.GetTipoConsumo(THIS.nTipoConsumo)	
				lcError = "[" + lcTipoConsumoNombre + "]"
				DO CASE
			     	CASE THIS.nTipoConsumo = 1 &&Consumo Normal
			        	lnId_MediEst = 46   
			        CASE THIS.nTipoConsumo = 2 &&Consumo Bajo
			        	lnId_MediEst = 47   
			        CASE THIS.nTipoConsumo = 3 &&Consumo Alto
			        	lnId_MediEst = 48   
			        CASE THIS.nTipoConsumo = 4 &&Consumo Cero
			        	lnId_MediEst = 49   
			        CASE THIS.nTipoConsumo = 5 &&Consumo Negativo
			        	lnId_MediEst = 50
			        	&&TODO:Que hacer para el Volcado y Fin de Ciclo
			        OTHERWISE
			        	lnId_MediEst = 0
		     	ENDCASE
				&&REPLACE TEMPORAL.Error WITH lcError
				lcSQL = "UPDATE GENLECT " +;
						"   SET ID_MEDIEST = " + oMySQL.Fox2SQL(lnId_MediEst) +;
						" WHERE ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact) +;
						"   AND ID_SOCIO = " + oMySQL.Fox2SQL(_TEMPORAL.ID_SOCIO)
				oMySQL.Ejecutar(lcSQL, "", THIS.DataSession)
			
		ENDSCAN
		lcSQL = "UPDATE GENFACT " +;
				"   SET G_GENLECT = " + oMySQL.Fox2SQL(.T.) +;
				" WHERE ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact) 
	  	oMySQL.Ejecutar(lcSQL, "", THIS.DataSession)
		SELECT(lnArea)
	ENDPROC

	*********************************************************
	* Método........: MostrarInstalacionesNuevas
	* Descripción...: Descripión de MostrarInstalacionesNuevas
	* Fecha.........: 30-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE MostrarInstalacionesNuevas()
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		lnArea = SELECT()
		TRY
			IF(USED("_INSTALAM"))
				SELECT _INSTALAM
				GO TOP
				loGridBrowse = CREATEOBJECT("GridBrowse")
		        loGridBrowse.SetCursorName("_INSTALAM")
		        loGridBrowse.AddCol("ID_INSTALA", "Nº Instal", 70)
				loGridBrowse.AddCol("ID_SOCIO", "Cod. Fijo", 70)
				loGridBrowse.AddCol("COD_SOCIO", "Cod. Ubicación", 90)
				loGridBrowse.AddCol("LECTANT", "Lect. Ant", 60)
				loGridBrowse.AddCol("ID_MEDIDOR", "ID Medidor", 60)
				loGridBrowse.AddCol("F_INSTALA", "F. Instalación", 60)
				loGridBrowse.AddCol("F_ACTIVA", "F. Activacion", 60)
				loGridBrowse.AddCol("F_TRABAJO", "F. Trabajo", 60)
				loGridBrowse.AddCol("F_FACTURAR", "F. Facturación", 60)
				loGridBrowse.AddCol("NUEVAINS", "Nueva Ins.", 45)
				loFormHist = CreateObject("Browse", @loGridBrowse, "_INSTALAM")
				loFormHist.WindowType = 1
				loFormHist.Name = "_INSTALAM"
				loFormHist.titulo.txtTitulo.Caption = "Lista de Nuevas Instalaciones " 
				loFormHist.titulo.txt2.Caption = "Lista de Nuevas Instalaciones" 
				loFormHist.Show()
			ENDIF
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.MostrarInstalacionesNuevas()"+ THIS._Enter
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC

	*********************************************************
	* Método........: MostrarBajasTemporales
	* Descripción...: Descripión de MostrarBajasTemporales
	* Fecha.........: 30-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE MostrarBajasTemporales()
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		lnArea = SELECT()
		TRY
			IF(USED("_INSTALAM2"))
				SELECT _INSTALAM2
				GO TOP
				loGridBrowse = CREATEOBJECT("GridBrowse")
		        loGridBrowse.SetCursorName("_INSTALAM2")
		        loGridBrowse.AddCol("ID_INSTALA", "Nº Instal", 70)
				loGridBrowse.AddCol("ID_SOCIO", "Cod. Fijo", 70)
				loGridBrowse.AddCol("COD_SOCIO", "Cod. Ubicación", 90)
				loGridBrowse.AddCol("LECTANT", "Lect. Ant", 60)
				loGridBrowse.AddCol("ID_MEDIDOR", "ID Medidor", 60)
				loGridBrowse.AddCol("F_INSTALA", "F. Instalación", 60)
				loGridBrowse.AddCol("F_ACTIVA", "F. Activacion", 60)
				loGridBrowse.AddCol("F_TRABAJO", "F. Trabajo", 60)
				loGridBrowse.AddCol("F_FACTURAR", "F. Facturación", 60)
				loGridBrowse.AddCol("NUEVAINS", "Nueva Ins.", 45)
				loFormHist = CreateObject("Browse", @loGridBrowse, "_INSTALAM2")
				loFormHist.WindowType = 1
				loFormHist.Name = "_INSTALAM2"
				loFormHist.titulo.txtTitulo.Caption = "Lista de Bajas Temporales " 
				loFormHist.titulo.txt2.Caption = "Lista de Bajas Temporales" 
				loFormHist.Show()
			ENDIF
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.MostrarBajasTemporales()"+ THIS._Enter
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC
	
	*********************************************************
	* Método........: MostrarCambiosDeMedidores
	* Descripción...: Descripión de MostrarCambiosDeMedidores
	* Fecha.........: 30-11-2020
	* Diseñador.....: Ing. Alfonzo Salgado Flores
	* Implementador.: Ing. Alfonzo Salgado Flores
	*********************************************************
	PROCEDURE MostrarCambiosDeMedidores()
		LOCAL loEx AS Exception
		LOCAL lcLog, lnArea, lcSQL
		lnArea = SELECT()
		TRY
			IF(USED("_SOCIMEDI"))
				SELECT _SOCIMEDI
				GO TOP
				loGridBrowse = CREATEOBJECT("GridBrowse")
		        loGridBrowse.SetCursorName("_SOCIMEDI")
		        loGridBrowse.AddCol("ID_SOCIMED", "Nº Instal", 70)
				loGridBrowse.AddCol("ID_SOCIO", "Cod. Fijo", 70)
				loGridBrowse.AddCol("COD_SOCIO", "Cod. Ubicación", 90)
				loGridBrowse.AddCol("LECTANT", "Lect. Ant", 60)				
				loGridBrowse.AddCol("F_SOCIMED", "F. Registro", 60)
				loGridBrowse.AddCol("FECHAACT", "F. Inicial", 60)
				loGridBrowse.AddCol("F_TRABAJO", "F. Trabajo", 60)
				loGridBrowse.AddCol("F_FACTURAR", "F. Facturación", 60)
				loFormHist = CreateObject("Browse", @loGridBrowse, "_SOCIMEDI")
				loFormHist.WindowType = 1
				loFormHist.Name = "_SOCIMEDI"
				loFormHist.titulo.txtTitulo.Caption = "Lista de Cambios de Medidores " 
				loFormHist.titulo.txt2.Caption = "Lista de Cambios de Medidores " 
				loFormHist.Show()
			ENDIF
		CATCH TO loEx
			lcLog = "  ProcedureInitial: GenLect.MostrarCambiosDeMedidores()"+ THIS._Enter
			oError.Guardar(loEx, lcLog)
		ENDTRY
		SELECT(lnArea)
	ENDPROC

	PROCEDURE GetAjusLectBy(tnID_GenFact AS Integer)
        LOCAL lcSQL, lnArea
        lnArea = SELECT()
        lcSQL = "SELECT A.* " +;
		        "  FROM AJUSLECT A " +;
		        " WHERE " + ;
		        "       A.ES_AJUSLEC = 2 " +;
		        "   AND A.ACCION IN (1,2) " +;
		        "   AND A.ID_GENFACT = " + oMySQL.Fox2SQL(tnID_GenFact)

        oMySQL.Ejecutar(lcSQL, "cAJUSLECT", THIS.DataSession)
        INDEX ON ID_Socio TAG ID_Socio ADDITIVE
        SELECT(lnArea)
    ENDPROC

    *********************************************************
    * Método........: ValidarAjusLect
    * Return........: Integer
    * Descripción...: Descripión de ValidarAjusLect
    * Fecha.........: 13-01-2021
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION ValidarAjusLect(tnID_GenFact AS Integer)
    	LOCAL loEx AS Exception
    	LOCAL lcLog, lnArea, lcSQL, llValido, lnResult
    	LOCAL lnID_GenFact, lnID_Socio
    	lnArea = SELECT()
    	llValido = .F.
    	lnResult = 0
    	TRY
    		SELECT TEMPORAL
    		SET ORDER TO ID_SOCIO
			THIS.GetAjusLectBy(tnID_GenFact)
			SELECT cAJUSLECT
			SCAN ALL
				lnID_GenFact = cAJUSLECT.ID_GENFACT
				lnID_Socio = cAJUSLECT.ID_SOCIO
				llValido = THIS.EsValidoAjusLect(lnID_Socio)
				IF !llValido
					&&Corregir en la BaseDatos y Temporal en funcion a los Datos de AjusLect.
					lnResult = lnResult + 1
					THIS.oAjusLect.ActualizarLecturas(lnID_GenFact, lnID_Socio ,;
													  cAJUSLECT.LectAnt, cAJUSLECT.LectAct ,;
													  cAJUSLECT.Consumo, cAJUSLECT.Id_MediEst,;
													  cAJUSLECT.ConsumoFac, cAJUSLECT.Id_MediEs2)
					THIS.ActualizarLecturasInTemporal(lnID_GenFact, lnId_Socio)
				ENDIF
				SELECT cAJUSLECT
			ENDSCAN
			SELECT TEMPORAL
    		SET ORDER TO Cod_Socio
    	CATCH TO loEx
    		lcLog = "  ProcedureInitial: GenLect.ValidarAjusLect()"+ THIS._Enter 
    		oError.Guardar(loEx, lcLog)
    	ENDTRY
    	SELECT(lnArea)
    	RETURN lnResult
    ENDFUNC
    
    *********************************************************
    * Método........: EsValidoAjusLect
    * Return........: Boolean
    * Descripción...: Descripción de EsValidoAjusLect
    * Fecha.........: 13-01-2021
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    FUNCTION EsValidoAjusLect(tnID_Socio AS Integer)
    	LOCAL lnArea, llValido
    	lnArea = SELECT()
    	SELECT TEMPORAL 
    	SEEK tnId_Socio
    	IF FOUND()
	    	llValido = 	(TEMPORAL.LectAnt = cAJUSLECT.LectAnt) ;
	                    AND (TEMPORAL.LectAct = cAJUSLECT.LectAct) ;
	                    AND (TEMPORAL.Consumo = cAJUSLECT.Consumo) ;
	                    AND (TEMPORAL.ConsumoFac = cAJUSLECT.ConsumoFac)
        ENDIF
    	SELECT(lnArea)
    	RETURN llValido
    ENDFUNC
    
    *********************************************************
    * Método........: ActualizarLecturasInTemporal
    * Descripción...: Descripión de ActualizarLecturasInTemporal
    * Fecha.........: 13-01-2021
    * Diseñador.....: Ing. Alfonzo Salgado Flores
    * Implementador.: Ing. Alfonzo Salgado Flores
    *********************************************************
    PROCEDURE ActualizarLecturasInTemporal(tnID_GenFact AS Integer, tnID_Socio AS Integer)
    	SELECT TEMPORAL
		UPDATE TEMPORAL;
		   SET  LectAnt = cAJUSLECT.LectAnt ;
		       ,LectAct = cAJUSLECT.LectAct ;
			   ,Consumo = cAJUSLECT.Consumo ;
			   ,ConsumoFac = cAJUSLECT.ConsumoFac;
			   ,ID_MediEst = cAJUSLECT.ID_MediEs2;
			   ,Error = '';
	     WHERE ID_GenFact = tnID_GenFact ;
	       AND ID_Socio = tnID_Socio 
    ENDPROC
    
    *********************************************************
	* Método........: [SOPORTE] ReasignarConsumoFacturado
	 * Descripción...: Descripión de ReasignarConsumoFacturado
	 * Fecha.........: 18-07-2018
	 * Diseñador.....: Ing. Alfonzo Salgado Flores
	 * Implementador.: Ing. Alfonzo Salgado Flores
 	 * Nota......... : Cambiado el nombres chek copia 17-07-2018.
	 *********************************************************
	PROCEDURE ReasignarConsumoFacturado(tnID_GenFact AS Integer, tcCobro AS String, tnId_Socio AS Integer)
	&&BEGIN
		LOCAL lnArea, lnConsumoMinimo, lnFila, lcRecTotal
		lnArea = SELECT()
	  	IF NOT USED("CATEGORI")
			oMySQL.GetTablaIndexada("CATEGORI", "CATEGORI", "*", THIS.DataSession)
		ENDIF
	  	&&CICLO PARA VALIDAR TODAS LAS LECTURAS DEL CURSOR TEMPORAL...
	  	lnFila = 0
	  	lcRecTotal = TRANSFORM(RECCOUNT("TEMPORAL"))
		SELECT TEMPORAL
		SCAN ALL
			lnFila = lnFila + 1
			lcRecNo =  ALLTRIM(STR(lnFila))  &&ALLTRIM(STR(RECNO("TEMPORAL"),10))
	 		WAIT WINDOW "Validando [ASOCIADO]..: " + ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) + " - " + TEMPORAL.Cod_Socio + CHR(13) +;
						"Registro..............: " + lcRecNo + " de " + lcRecTotal NOWAIT 
			IF NOT EMPTY(tnId_Socio)
				IF (TEMPORAL.ID_SOCIO = tnId_Socio)
					SET STEP ON
				ENDIF
			ENDIF
			
			lnConsumoMinimo = 0
			SELECT CATEGORI
			SEEK TEMPORAL.ID_CATEG
			IF FOUND()
				lnConsumoMinimo = CATEGORI.ConsumoMin
			ENDIF 

			SELECT TEMPORAL
			REPLACE ConsumoFac WITH IIF(TEMPORAL.Consumo < lnConsumoMinimo, lnConsumoMinimo, TEMPORAL.Consumo)

			SELECT TEMPORAL
		ENDSCAN
		SELECT(lnArea)
	ENDPROC

ENDDEFINE