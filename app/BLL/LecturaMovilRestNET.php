<?php

namespace App\BLL;

use App\DAL\EmpresaDAL;
use App\DAL\SincronizarDAL;

use GuzzleHttp\Client;
use SimpleXMLElement;
use Exception;

class LecturaMovilRestNET
{
    public $loClient;
    public $cEmpresa = "";
    public $cURLBase = "";
    public $loUserAccess;
    public $cEndPointBase = "/WSServicioMovil/WMovil.asmx";

    function __construct($tnEmpresa) 
    {
        set_time_limit(240);
        $this->loClient = new Client();
        $this->cEmpresa = $this->datosEmpresa();
        $this->cURLBase = "http://" . trim($this->cEmpresa[0]->ServerIP) . trim($this->cEndPointBase);
        $this->loUserAccess = $this->WMAutenticar(trim($this->cEmpresa[0]->LoginEmpresa), trim($this->cEmpresa[0]->PasswordEmpresa));
    }

    public function datosEmpresa(){
        $loEmpresa = new EmpresaDAL;
        $loEmpresa = $loEmpresa->obtenerDatosEmpresa();
        return $loEmpresa;
    }

    public function convetirXMLaJSON($xml){
        $xmlStr = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $xml);
        $xml    = new SimpleXMLElement($xmlStr);
        $json   = json_encode($xml);
        $json   = json_decode($json, true);
        return $json;
    }

    public function WMAutenticar($login, $password){
        $lcURL      = $this->cURLBase . "/WMAutenticar?login=".$login."&password=".$password;
        $loResponse = $this->loClient->get($lcURL);
        $lnStatus   = $loResponse->getStatusCode();
        $loContents = $loResponse->getBody()->getContents();
        $loContents =  $this->convetirXMLaJSON($loContents);
        $loContents = $loContents['diffgrdiffgram']['NewDataSet']['Table1']['useracces'];
        return $loContents;
    }

    public function WMGet_Lecturas_Pendientes(){
        $urlEncode = urlencode($this->loUserAccess);
        $lcURL      = $this->cURLBase . "/WMGet_Lecturas_Pendientes?useraccess=".$urlEncode."&id_plomero=".$this->cEmpresa[0]->Plomero;
        $loResponse = $this->loClient->get($lcURL);
        $lnStatus   = $loResponse->getStatusCode();
        $loContents = $loResponse->getBody()->getContents();
        $loContents = $this->convetirXMLaJSON($loContents);
        $loContents = $loContents['diffgrdiffgram']['NewDataSet']['Table'];

        $array = [];
        if(isset($loContents['id_genfact'])) {
            array_push($array, $loContents);
        }else {
            $array = $loContents;
        }

        return $array;
    }

    public function WMSincronizacionBDListDemo($request){
        $lcURL = $this->cURLBase . "/WMSincronizacionBDListDemo?tcLogin=".$this->cEmpresa[0]->LoginEmpresa
                ."&tcPassword=".$this->cEmpresa[0]->PasswordEmpresa
                ."&tcAccesUser=".$this->loUserAccess
                ."&tnPlomero=".$this->cEmpresa[0]->Plomero
                    ."&tnGeneracionFactura=".(isset($request->GeneracionFactura[0]) ? $request->GeneracionFactura[0] : 0)
                    ."&tnGeneracionFactura1=".(isset($request->GeneracionFactura[1]) ? $request->GeneracionFactura[1] : 0)
                    ."&tnGeneracionFactura3=".(isset($request->GeneracionFactura[2]) ? $request->GeneracionFactura[2] : 0);
        
        $loResponse = $this->loClient->get($lcURL);
        $lnStatus = $loResponse->getStatusCode();

        $lnStatus = ($lnStatus == 200) ? 1 : 0;
        return $lnStatus;
    }

    public function WMSincronizarCaS($request){
        try {
            $loSincronizar = new SincronizarDAL;
            $datos['TRAYECT'] = $loSincronizar->Get_Trayectoria($request->Plomero, $request->DataBaseAlias); //TRAYECTORIA
            $datos['GENFACT'] = $loSincronizar->Get_GeneracionFactura($request->Plomero, $request->DataBaseAlias); //GENERACIONFACTURA
            $datos['GENLECT'] = $loSincronizar->Get_GeneracionLectura($request->Plomero, $request->DataBaseAlias); //GENERACIONLECTURA
            $datos['GENLECTN'] = $loSincronizar->Get_GeneracionLecturaMovil($request->Plomero, $request->DataBaseAlias); //GENERACIONLECTURAMOVIL
            $datos['MODGENLE'] = $loSincronizar->Get_ModificacionGeneracionLectura($request->Plomero, $request->DataBaseAlias); //MODIFICACIONGENERACIONLECTURA
            $datos['GENLECTM'] = []; //$loSincronizar->Get_ModificacionGeneracionLectura($request->Plomero, $request->DataBaseAlias); //GENERACIONLECTURAMODIFICADO - sin uso
            $datos['SOCIOSCORTE'] = []; //$loSincronizar->Get_ModificacionGeneracionLectura($request->Plomero, $request->DataBaseAlias); //SOCIOSCORTE
            $datos = json_encode($datos);
            $datos = base64_encode($datos);
    
            $lcURL = $this->cURLBase . "/WMSincronizarJsonCaS?tcPaqueteJsonBase64=".$datos;
            $loResponse = $this->loClient->get($lcURL);
            $lnStatus = $loResponse->getStatusCode();
            
            $lnStatus = ($lnStatus == 200) ? 1 : 0;

        } catch (Exception $th) {
            $lnStatus =  $th->getMessage();
        }
        return $lnStatus;
    }

    /*public function verificarConexionRestNET($request)
    {
        $loClient = new Client();
	    $lcURL = $this->cURLBase . "/VerificarConexion";
	    $laParams = ['tcMensaje' => $request->tcMensaje ];
	    $loResponse = $loClient->post($lcURL,  ['query'=>$laParams]);
        $lnStatus = $loResponse->getStatusCode(); 
        $loContents = null;
        if ($lnStatus === 200)
        { 
            $loContents = json_decode($loResponse->getBody()->getContents());
        }         
        return $loContents;
    }

    public function guardarLecturaNubeToEmpresa($request)
    {
        //EndPoint = http://201.222.126.62/ServicioLecturaMovil/LecturaMovil/GuardarLecturaNubeToEmpresa?tnGeneracionFactura=19032&tnCliente=32&tnPlomero=55
        $loClient = new Client();
	    $lcURL = $this->cURLBase . "/GuardarLecturaNubeToEmpresa";
	    $laParams = ['tnGeneracionFactura' => $request->tnGeneracionFactura,
                     'tnCliente' => $request->tnCliente,
                     'tnPlomero' => $request->tnPlomero];

	    $loResponse = $loClient->post($lcURL,  ['query'=>$laParams]);
        $lnStatus = $loResponse->getStatusCode(); 
        $loContents = null;
        if ($lnStatus === 200)
        { 
            $loContents = json_decode($loResponse->getBody()->getContents());
            //$loContents.values == 1 <<< Exito            
        }         
        return $loContents;
    }*/
}