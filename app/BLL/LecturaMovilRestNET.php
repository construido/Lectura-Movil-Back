<?php

namespace App\BLL;

use App\DAL\EmpresaDAL;
use GuzzleHttp\Client;
use SimpleXMLElement;

class LecturaMovilRestNET
{
    public $cEmpresa = ""; //"201.222.126.62"; //TODO: Consultar de EMPRESA.ServerIP
    public $cEndPointBase = "/WSServicioMovil/WMovil.asmx"; //"/ServicioLecturaMovil/LecturaMovil"; 
    public $cURLBase = "";
    public $loClient;
    public $loUserAccess;

    function __construct($tnEmpresa) 
    {
        //$tnEmpresa es la empresa ID el cual cuando se logea el Lecturador debe manda cada vez que use esta clase para sacar de la DB el IP de la emprea 
        //al cual vamos a acceder para leer y guardar...
        set_time_limit(240);
        $this->loClient = new Client();
        $this->cEmpresa = $this->datosEmpresa(); //"201.222.126.62";
        $this->cURLBase = "http://" . $this->cEmpresa[0]->ServerIP . $this->cEndPointBase;
        $this->loUserAccess = $this->WMAutenticar($this->cEmpresa[0]->LoginEmpresa, $this->cEmpresa[0]->PasswordEmpresa);
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
        $lcURL      = $this->cURLBase . "/WMGet_Lecturas_Pendientes?useraccess=".$this->loUserAccess."&id_plomero=".$this->cEmpresa[0]->Plomero;
        $loResponse = $this->loClient->get($lcURL);
        $lnStatus   = $loResponse->getStatusCode();
        $loContents = $loResponse->getBody()->getContents();
        return $this->convetirXMLaJSON($loContents);
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