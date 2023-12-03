*********************************************************
* Método........: ID_MediEst.Valid
* Descripción...: Descripión de ID_MediEst.Valid
* Fecha.........: 05-05-2017
* Diseñador.....: Ing. Alfonzo Salgado Flores
* Implementador.: Ing. Alfonzo Salgado Flores
* Log...........: 
*				  FechaIni:01-03-2019
*				  FechaFin:30-06-2019 
* Log...........: Add: EsCambioDeMedidor() y EsRegularizacionBajaTemporal()
*				  FechaIni:12-11-2020
*				  FechaFin:17-11-2020
*********************************************************

LOCAL lnKey,lnTiene, lnId_MediEst
LOCAL lnLectAntNew, lnLectActNew, lnId_MediEstNew
LOCAL lnConsumoNew,  lnConsumoFac,llLectAct_Celda
LOCAL lnAccion, lnID_GenFact, lnID_Socio, lcCod_Socio
LOCAL llSeValida, loTiempoIni
LOCAL lcTipoConsumoNombre, lcError, lcCobro
LOCAL lnEsInstalacionNueva, lnEsCambioMedidor, lnRegulaBajaTemp, lcErrorEstado, lcErrorEstado1, lcErrorEstado2

lcErrorEstado = ""
lcErrorEstado1 = ""
lcErrorEstado2 = ""
lnLectAntNew = 0
lnLectActNew = 0
lnId_MediEstNew = 0
lnEsInstalacionNueva = 3
loTiempoIni = TIME(1)
lcCobro = THISFORM.COBRO.Value
IF DELETED()
 	RETURN .T.
ENDIF

IF THISFORM.GENERADO.Value 
	RETURN .T.
ENDIF

IF THISFORM.nEstado = -3
	RETURN .T.
ENDIF

llLectAct_Celda = .F.
IF (THISFORM.lectact_key)	
	THISFORM.lectact_key = .f.	&& Biene de LECTACT
	llLectAct_Celda = .T. &&Quiere decir que la Celda LectAct sufrio cambios (nuevo valor) y si o si hay que validar.
ENDIF

lnTiene = THISFORM.oGenLect.TieneFactura(TEMPORAL.ID_Socio, THISFORM.Cobro.Value) &&Clase
IF lnTiene 
	RETURN .T.
ENDIF 

lnKey = LASTKEY()
lnAccion = 0
lnID_GenFact = TEMPORAL.ID_GenFact
lnID_Socio = TEMPORAL.ID_Socio

lnAccion = THISFORM.oAjusLect.ExisteAjusLect(lnID_GenFact, lnID_Socio)
IF(INLIST(lnAccion,1,2)) &&Ejecutado
	IF(lnKey = 13)						
		KEYBOARD '{RIGHTARROW}' CLEAR
		KEYBOARD '{DNARROW}'										
		KEYBOARD '{RIGHTARROW}'
		&&KEYBOARD '{LEFTARROW}' 
	ELSE 
		RETURN 1
	ENDIF
ENDIF

IF (THISFORM.IngresarLecturasLibre.Value = .T.)
	lnId_MediEst = TEMPORAL.Id_MediEst
	IF lnId_MediEst <> 0
		IF lnId_MediEst = -1
			lnId_MediEst = THISFORM.BusqSqlID("Busqueda de Estados Medidores...", ;
		                60,12,"Seleccione...", ;
		                "SELECT STR(D.ID_MediEst,3)+D.Nomb_Medie  AS Nombre, D.ID_MediEst AS ID" +  ;
		                "  FROM MEDIESTA D "+;
		                "  INTO CURSOR SQL", ;
		                "SELECT STR(D.ID_MediEst,3)+D.Nomb_Medie AS Nombre, D.ID_MediEst AS ID" +  ;
		                "  FROM MEDIESTA D " +  ;
		                "  WHERE UPPER(STR(D.ID_MediEst,3)+D.Nomb_Medie) LIKE  m.Cons" +  ;
		                "  INTO CURSOR SQL")
		ENDIF
		SELECT MEDIESTA
		INDEX ON ID_MEDIEST TAG ID_MEDIEST ADDITIVE
		SELECT TEMPORAL
		IF NOT THISFORM.MDConsul(lnId_MediEst, 1, "MEDIESTA")
			REPLACE TEMPORAL.Id_MediEst WITH 0
		  	RETURN 0
		ENDIF
		REPLACE TEMPORAL.Id_MediEst WITH lnId_MediEst
	ENDIF
	IF(lnKey = 13)						
		KEYBOARD '{RIGHTARROW}' CLEAR
		KEYBOARD '{DNARROW}'										
		KEYBOARD '{RIGHTARROW}'
		&&KEYBOARD '{LEFTARROW}' 
	ELSE 
		RETURN 1
	ENDIF
ELSE
	SET STEP ON 
	&& [Validar Lecturas al Momento de Digitar ] 
	IF(THISFORM.SetStepOn.Value)
		SET STEP ON
	ENDIF 
	IF (THISFORM.ValidarAlDigitar.Value = .F.)
		IF(lnKey = 13)						
			KEYBOARD '{RIGHTARROW}' CLEAR
			KEYBOARD '{DNARROW}'										
			KEYBOARD '{RIGHTARROW}'
			&&KEYBOARD '{LEFTARROW}' 
		ELSE 
			RETURN 1
		ENDIF
	ELSE	
		lnId_MediEst = TEMPORAL.Id_MediEst
		IF lnId_MediEst <> 0
			IF lnId_MediEst = -1
				lnId_MediEst = THISFORM.BusqSqlID("Busqueda de Estados Medidores...", ;
			                60,12,"Seleccione...", ;
			                "SELECT STR(D.ID_MediEst,3)+D.Nomb_Medie  AS Nombre, D.ID_MediEst AS ID" +  ;
			                "  FROM MEDIESTA D "+;
			                "  INTO CURSOR SQL", ;
			                "SELECT STR(D.ID_MediEst,3)+D.Nomb_Medie AS Nombre, D.ID_MediEst AS ID" +  ;
			                "  FROM MEDIESTA D " +  ;
			                "  WHERE UPPER(STR(D.ID_MediEst,3)+D.Nomb_Medie) LIKE  m.Cons" +  ;
			                "  INTO CURSOR SQL")
			ENDIF
			SELECT MEDIESTA
			INDEX ON ID_MEDIEST TAG ID_MEDIEST ADDITIVE
			SELECT TEMPORAL
			IF NOT THISFORM.MDConsul(lnId_MediEst, 1, "MEDIESTA")
				REPLACE TEMPORAL.Id_MediEst WITH 0
			  	RETURN 0
			ENDIF
			REPLACE TEMPORAL.Id_MediEst WITH lnId_MediEst
		ENDIF
				
		lnKey=LASTKEY()
		_Enter = CHR(13) + CHR(10)
		lnID_GenFact = TEMPORAL.ID_GenFact
		lnID_Socio = TEMPORAL.ID_Socio
		lcCod_Socio = TEMPORAL.Cod_Socio
		lnLectAnt = TEMPORAL.LectAnt
		lnLectAct = TEMPORAL.LectAct
		lnConsumo = lnLectAct - lnLectAnt
		lnConsumoFac = TEMPORAL.ConsumoFac
		lnMedia = TEMPORAL.Media
		lnId_Medidor = TEMPORAL.Id_Medidor
		lnId_Categ = TEMPORAL.Id_Categ

		lnId_MediEst = IIF(TEMPORAL.Id_MediEs2 > 0 , TEMPORAL.Id_MediEs2, TEMPORAL.Id_MediEst)

		lnValido = 0

		IF INLIST(lnKey,4,5,19,24,9)    
			llSeValida = THISFORM.oGenLect.SeValida(lnMedia, lnConsumo, lnId_Categ)
			IF (llSeValida = .F.)
				RETURN
			ENDIF
			lnValido = THISFORM.oGenLect.ValidarLectura(lnLectAnt, lnLectAct, lnConsumo, lnMedia,;
			 											lnId_MediEst, lnId_Medidor, lnId_Categ, lnConsumoFac)
			lnEsInstalacionNueva = THISFORM.oGenLect.EsInstalacionNueva(lnId_MediEst, lnId_Socio, lcCobro, @lcErrorEstado)
			IF INLIST(lnEsInstalacionNueva, 0, 1)
				&&Aqui no se hace nada ya que efectivamente es Instalacion Nueva.
				SELECT TEMPORAL
				IF (lnEsNuevaInstalacion == 1)
					REPLACE TEMPORAL.Error WITH lcErrorEstado
				ENDIF
			ELSE
				lnEsCambioMedidor = THISFORM.oGenLect.EsCambioDeMedidor(lnId_MediEst, lnID_Socio, @lcErrorEstado)
				SELECT TEMPORAL
				IF !EMPTY(lcErrorEstado)
					REPLACE TEMPORAL.Error WITH lcErrorEstado
				ENDIF
				IF INLIST(lnEsCambioMedidor, 0, 1)	
					RETURN 1
				ELSE
					lnRegulaBajaTemp = THISFORM.oGenLect.EsRegularizacionBajaTemporal(lnId_MediEst, lnID_Socio, @lcErrorEstado)
					SELECT TEMPORAL
					IF !EMPTY(lcErrorEstado)
						REPLACE TEMPORAL.Error WITH lcErrorEstado
					ENDIF
					IF INLIST(lnRegulaBajaTemp, 0, 1)	
						RETURN 1
					ELSE
						IF lnValido <> 0 
							&& HAY ERRORES
							lcMsg = "Tipo Consumo = " + THISFORM.oGenLect.cTipoConsumoNombre + CHR(13) + CHR(10) +;
									"Error : " + THISFORM.oGenLect.ErrorMsg				
							WAIT WINDOW lcMsg
							RETURN 0 && si o si debe digitar una anormalidad valida para salir 
									 && del campo y procesar los cambios correctos.
						ELSE
							RETURN && Salimos, no hay errores.
						ENDIF
					ENDIF
				ENDIF	
				
			ENDIF
		ELSE
			IF (lnKey <> 13)
				&&oError.GuardarLog("ENTER","SALIENDO LNKEY <> 13")
				RETURN 0 && No hubo cambios en las lecturas
			ENDIF 
		ENDIF

		IF ((THISFORM.nEstado > 0) AND (NOT EOF()) AND  (lnKey = 13	))
			llSeValida = THISFORM.oGenLect.SeValida(lnMedia, lnConsumo, lnId_Categ)
			IF (llSeValida = .F.)
				IF(THISFORM.AccederAEstado.Value = .T.)
					KEYBOARD '{RIGHTARROW}' CLEAR
					KEYBOARD '{LEFTARROW}' 
					KEYBOARD '{LEFTARROW}' 
					KEYBOARD '{DNARROW}'
					THISFORM.GridTemporal.Refresh()
					THISFORM.SetValueCompoTime("TiempoProceso", loTiempoIni)
					RETURN 1
				ELSE
					KEYBOARD '{RIGHTARROW}' CLEAR
					KEYBOARD '{LEFTARROW}' 
					KEYBOARD '{DNARROW}'
					KEYBOARD '{LEFTARROW}' 
					THISFORM.GridTemporal.Refresh()
					RETURN 1
				ENDIF
			ELSE			
			    IF (lnId_MediEst == 0)
					lnValido = THISFORM.oGenLect.ValidarLectura( lnLectAnt, lnLectAct, lnConsumo, lnMedia,;
																 lnId_MediEst, lnId_Medidor, lnId_Categ, lnConsumoFac)
					lnEsInstalacionNueva = THISFORM.oGenLect.EsInstalacionNueva(lnId_MediEst, lnId_Socio, lcCobro, @lcErrorEstado)
					lnEsCambioMedidor = THISFORM.oGenLect.EsCambioDeMedidor(lnId_MediEst, lnID_Socio, @lcErrorEstado1)
					lnRegulaBajaTemp = THISFORM.oGenLect.EsRegularizacionBajaTemporal(lnId_MediEst, lnID_Socio, @lcErrorEstado2)

					IF !INLIST(lnEsInstalacionNueva, 0) AND !INLIST(lnEsCambioMedidor, 0) AND !INLIST(lnRegulaBajaTemp, 0)
						IF lnValido <> 0 
							&& HAY ERRORES
							lcMsg = "Tipo Consumo = " + THISFORM.oGenLect.cTipoConsumoNombre + CHR(13) + CHR(10) +;
									"Error : " + THISFORM.oGenLect.ErrorMsg				
							WAIT WINDOW lcMsg
							RETURN 0 && si o si debe digitar una anormalidad valida para salir
							 		 && del campo y procesar los cambios correctos.
						ENDIF
					ELSE
						lcErrorCombinado = lcErrorEstado + lcErrorEstado1 + lcErrorEstado2
						IF !EMPTY(lcErrorCombinado)
							REPLACE TEMPORAL.Error WITH lcErrorCombinado
						ENDIF
					ENDIF
					
					llEsNuevaCambioBaja = (lnEsInstalacionNueva == 0 ) OR (lnEsCambioMedidor == 0) OR (lnRegulaBajaTemp == 0)
					IF (((THISFORM.oGenLect.nTipoConsumo = 1 ) AND (lnValido = 0)) OR (llEsNuevaCambioBaja == .T.)) &&Consumo Valido
						&&Validadmos la Regla que corresponda
						THISFORM.oGenLect.AplicarRegla(lnLectAnt, lnLectAct, lnConsumo, lnMedia, lnId_MediEst, lnId_Medidor)
						SELECT TEMPORAL
						REPLACE TEMPORAL.LectAnt WITH THISFORM.oGenLect.nLectAnt		
						REPLACE TEMPORAL.LectAct WITH THISFORM.oGenLect.nLectAct
						REPLACE TEMPORAL.Consumo WITH THISFORM.oGenLect.nConsumo
						REPLACE TEMPORAL.ConsumoFac WITH THISFORM.oGenLect.nConsumoFac
						IF (THISFORM.oGenLect.nTipoConsumo > 1)
							lcTipoConsumoNombre = THISFORM.oGenLect.oMedidorInfo.GetTipoConsumo(THISFORM.oGenLect.nTipoConsumo)	
							IF !EMPTY(THISFORM.oGenLect.ErrorMsg)
								lcError = "[" + lcTipoConsumoNombre + "]" + THISFORM.oGenLect.ErrorMsg
							ELSE
								lcError = "[Informativo][" + lcTipoConsumoNombre + "]"
							ENDIF
						ELSE
							lcError = ""
						ENDIF
						SELECT TEMPORAL
						REPLACE TEMPORAL.Error WITH lcError
						IF(THISFORM.ACcederAEstado.Value = .T.)
							KEYBOARD '{RIGHTARROW}' CLEAR
							KEYBOARD '{LEFTARROW}' 
							KEYBOARD '{LEFTARROW}' 
							KEYBOARD '{DNARROW}'
							THISFORM.GridTemporal.Refresh()
							RETURN 1
						ELSE
							KEYBOARD '{RIGHTARROW}' CLEAR
							KEYBOARD '{LEFTARROW}' 
							KEYBOARD '{DNARROW}'
							RETURN 1 &&Salimos sin preguntar, las lecturas y consumos son validos con us anormlaidad
						ENDIF
					ENDIF 			
				ENDIF 
				THISFORM.oGenLect.AplicarRegla(lnLectAnt, lnLectAct, lnConsumo, lnMedia, lnId_MediEst, lnId_Medidor)
				lnId_MediEstNew = THISFORM.oGenLect.AnormalidadID
				lcRegla = ALLTRIM(THISFORM.oGenLect.ReglaNombre)
				lcAnormalidadNombre = ALLTRIM(SUBSTR(THISFORM.oGenLect.AnormalidadNombre,1,25))					
				lcMsg = "Esta seguro de Aplicar?" + _Enter +;
						"Anormalidad = " + STR(lnId_MediEstNew,5) + " - " + lcAnormalidadNombre  + _Enter +;
						"Solución = " + lcRegla + _Enter +;
						"Socio = " + ALLTRIM(STR(TEMPORAL.ID_SOCIO,10)) + " - " + ALLTRIM(TEMPORAL.COD_SOCIO) + _Enter +;
						"Mididor = " + ALLTRIM(STR(TEMPORAL.ID_MEDIDOR,10)) + _Enter +;
						"FinMedidor = " + ALLTRIM(STR(THISFORM.oGenLect.nFinMedidor,10)) + _Enter +;
						"Media = " + ALLTRIM(STR(lnMedia ,10)) + _Enter +;
			 			"L. Ant = " + ALLTRIM(STR(THISFORM.oGenLect.nLectAnt,10)) + _Enter +;
						"L. Act = " + ALLTRIM(STR(THISFORM.oGenLect.nLectAct,10)) + _Enter +;
						"Consumo = " + ALLTRIM(STR(THISFORM.oGenLect.nConsumo,10)) + _Enter +;
						"ConsumoFac	= " + ALLTRIM(STR(THISFORM.oGenLect.nConsumoFac,10)) + _Enter 

				IF THISFORM.EsSI('Validación de Lectura.', lcMsg) AND (THISFORM.oGenLect.nFinMedidor > 0)
					REPLACE TEMPORAL.LectAnt WITH THISFORM.oGenLect.nLectAnt		
					REPLACE TEMPORAL.LectAct WITH THISFORM.oGenLect.nLectAct
					REPLACE TEMPORAL.Consumo WITH THISFORM.oGenLect.nConsumo
					REPLACE TEMPORAL.ConsumoFac WITH THISFORM.oGenLect.nConsumoFac
					IF (THISFORM.ID_MediEst_Old <> lnId_MediEst) &&Hubo Cambio de Estado
						lnValido = THISFORM.oGenLect.ValidarLectura(TEMPORAL.LectAnt, TEMPORAL.LectAct, TEMPORAL.Consumo,; 
																		lnMedia, lnId_MediEst, lnId_Medidor, lnId_Categ, lnConsumoFac)
					ENDIF 
					SELECT TEMPORAL
					lnEsInstalacionNueva = THISFORM.oGenLect.EsInstalacionNueva(lnId_MediEst, lnId_Socio, lcCobro, @lcErrorEstado)
					IF INLIST(lnEsInstalacionNueva, 0, 1)
						lcError = THISFORM.oGenLect.GetErrorMsgBy(8) &&Parametro: 8 = APLICAR INSTALACION NUEVA
						SELECT TEMPORAL
						REPLACE TEMPORAL.Variacion WITH THISFORM.oGenLect.nPorcentajeDesviacion
						SELECT TEMPORAL
						IF (lnEsNuevaInstalacion == 0)
							REPLACE TEMPORAL.Error WITH lcError
						ELSE
							REPLACE TEMPORAL.Error WITH lcErrorEstado
						ENDIF								
					ENDIF
					IF(THISFORM.AccederAEstado.Value = .T.)
						KEYBOARD '{RIGHTARROW}' CLEAR
						KEYBOARD '{LEFTARROW}' 
						KEYBOARD '{LEFTARROW}' 
						KEYBOARD '{DNARROW}'
						IF !INLIST(lnEsInstalacionNueva,0, 1)
							lcTipoConsumoNombre = THISFORM.oGenLect.oMedidorInfo.GetTipoConsumo(THISFORM.oGenLect.nTipoConsumo)	
							IF !EMPTY(THISFORM.oGenLect.ErrorMsg)
								lcError = "[" + lcTipoConsumoNombre + "]" + THISFORM.oGenLect.ErrorMsg
							ELSE
								lcError = "[Informativo][" + lcTipoConsumoNombre + "]"
							ENDIF
							REPLACE TEMPORAL.Error WITH lcError
						ENDIF
						THISFORM.GridTemporal.Refresh()
						THISFORM.SetValueCompoTime("TiempoProceso", loTiempoIni)
						RETURN 1
					ELSE
						KEYBOARD '{RIGHTARROW}' CLEAR
						KEYBOARD '{LEFTARROW}' 
						KEYBOARD '{DNARROW}'
						KEYBOARD '{LEFTARROW}' 
						THISFORM.GridTemporal.Refresh()
					ENDIF
					lcTipoConsumoNombre = THISFORM.oGenLect.oMedidorInfo.GetTipoConsumo(THISFORM.oGenLect.nTipoConsumo)	
					lcError = "[" + lcTipoConsumoNombre + "]" + THISFORM.oGenLect.ErrorMsg
					REPLACE TEMPORAL.Error WITH lcError
				ELSE
					&& Rolback
					IF (THISFORM.oGenLect.nFinMedidor <= 0)
						THISFORM.Wait_Window("Medidor " + ALLTRIM(STR(lnId_Medidor ,10)) + " no tiene FinMedidor, debe Definirlo!!!")
					ENDIF 
					REPLACE TEMPORAL.Id_MediEst WITH THISFORM.Id_MediEst_Old		
				ENDIF

				IF NOT EMPTY(THISFORM.oGenLect.ErrorMsg)
					THISFORM.Wait_Window(THISFORM.oGenLect.ErrorMsg)
				ENDIF
			ENDIF
		ENDIF
		IF THISFORM.nEstado > 0 AND NOT EOF()
			IF TEMPORAL.CONSUMO < 0
				THISFORM.Wait_Window("No Puede ser negativo el Consumo, Verifique Datos.!",3)
				RETURN 0
			ENDIF 
		ENDIF 	
	ENDIF 
ENDIF

THISFORM.SetValueCompoTime("TiempoProceso", loTiempoIni)