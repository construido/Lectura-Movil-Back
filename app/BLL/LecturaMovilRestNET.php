<?php

namespace App\BLL;
use GuzzleHttp\Client;

class LecturaMovilRestNET
{
    public $cIPEmpresa = "201.222.126.62"; //TODO: Consultar de EMPRESA.ServerIP
    public $cEndPointBase = "/ServicioLecturaMovil/LecturaMovil"; 
    public $cURLBase = "";

    function __construct($tnEmpresa) 
    {
        //$tnEmpresa es la empresa ID el cual cuando se logea el Lecturador debe manda cada vez que use esta clase para sacar de la DB el IP de la emprea 
        //al cual vamos a acceder para leer y guardar...
        $this->cIPEmpresa = "201.222.126.62";
        $this->cURLBase = "http://" . $this->cIPEmpresa . $this->cEndPointBase ;
    }

    public function verificarConexionRestNET($request)
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
    }
}