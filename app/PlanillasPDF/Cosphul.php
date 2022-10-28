<?php

namespace App\PlanillasPDF; //BLL

use App\Models\Usuario;
use App\Models\ParaQR;

use App\DAL\ParametrosGeneralesDAL;
use App\DAL\MedidorAnormalidadDAL;
use App\DAL\HistoricoFacturaDAL;
use App\DAL\FacturaDetalleDAL;
use App\DAL\MensajeClienteDAL;
use App\DAL\ClienteDAL;
use App\DAL\FacturaDAL;

use App\Http\Controllers\UsuarioController;

use Tymon\JWTAuth\Facades\JWTAuth;

use DB;

use PDF;
use File;
use QrCode;

class Cosphul //ImprimirBLL
{
    public function GetFacturaCosphul($Cliente, $CodigoUbicacion, $Lectura_Actual, $Lectura_Anterior, $Consumo, $Cobro, $GeneracionFactura, $DataBaseAlias, $EmpresaNombre){
        $ParaQR = ParaQR::on($DataBaseAlias)->get();
        $URL    = trim($ParaQR[0]->Url);
        $ComerceID = trim($ParaQR[0]->ComerceID);
        $QR     = $URL.$ComerceID.'/'.$Cliente;

        $Usuario = new UsuarioController;
        $Usuario = $Usuario->obtenerLecturador(JWTAuth::user()->Usuario, $DataBaseAlias);

        if($Usuario)
            $Lecturador = 'Emitido por: ' . $Usuario[0]->Nombre.' '.$Usuario[0]->Apellidos.' Fecha: '.date("Y-m-d").' '.date("H:i:s");
        else
            $Lecturador = '';

        $laCliente = new ClienteDAL;
        $laCliente = $laCliente->GetDatosCliente($Cliente, $DataBaseAlias);
        
        $laFactura = new FacturaDAL;
        $laFacturaCliente = $laFactura->GetDatosFacturaSocio($GeneracionFactura, $Cliente, $DataBaseAlias); // TODO : se le aumento GeneracionFactura
        $FechaEmision = $laFacturaCliente[0]->FechaEmision;
        $Corte        = $laFacturaCliente[0]->Corte;
        $FechaCorte   = $laFactura->GetFechaCorte($FechaEmision, $Corte, $DataBaseAlias);

        $anormalidad =  new MedidorAnormalidadDAL;
        $anormalidad = $anormalidad->obtenerMensajeAnormalidad($laFacturaCliente[0]->MedidorAnormalidad, $DataBaseAlias);
        $mensajeAnormalidad;
        if(isset($anormalidad[0]->EtiquetaAdvertencia))
            $mensajeAnormalidad = $anormalidad[0]->EtiquetaAdvertencia;
        else
            $mensajeAnormalidad = '';

        $laHistoricoFactura = new HistoricoFacturaDAL;
        $laHistorial        = $laHistoricoFactura->TraerHistorial($Cliente, $DataBaseAlias);
        $FechaAnterior      = $laHistoricoFactura->GetFechaLecturaAnterior($Cliente, $DataBaseAlias);
        if (isset($laHistorial[0]->M3)) $ConsumoAnterior = $laHistorial[0]->M3;
        else $ConsumoAnterior = 0;

        $FechaActual = date('Y-m-d');
        $datetime1 = date_create($FechaActual);
        $datetime2 = date_create($FechaAnterior[0]->FechaLectura);
        $contador = date_diff($datetime1, $datetime2);
        $differenceFormat = '%a';

        $laFacturaDetalle = new FacturaDetalleDAL;
        $laFacturaDetalle = $laFacturaDetalle->GetDetalleFactura($Cliente, $DataBaseAlias);
        $laParametrosGenerales = new ParametrosGeneralesDAL;
        $laParametrosGenerales = $laParametrosGenerales->GetAlldt($DataBaseAlias);
        $contar = count($laFacturaDetalle);

        $Historico      = '';
        $ImporteFactura = 0;
        $TotalDeuda     = 0;
        $NumeroMeses    = 0;
        $DeudaAtrasada  = 0;
        $NFactura       = 0;
        $Indice         = 0;
        
        for ($i=0; $i <= 11; $i++) {
            if($NFactura == 0){
                
                $Historial = '<tr> <td align="left" style="padding: 0;">'.$laFacturaCliente[0]->Cobro.'<span style="float:right;">'.$laFacturaCliente[0]->MedidorAnormalidad.'</span></td>';
                $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 10px;">'.(round($laFacturaCliente[0]->Consumo, 0)).'</td>';
                $Historial = $Historial . '<td align="right" style="padding: 0;">'.$laFacturaCliente[0]->MontoTotal.'</td>';
                $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 20px;">'.($laFacturaCliente[0]->Estado == 1 ? "Pendiente" : "Pagado").'</td>';
                
                if ($i < $contar) {
                    $Historial = $Historial . '<td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 20px; padding-right: 0; font-size: 13px;">'.$laFacturaDetalle[$i]->NombreServicio.' </td>';
                    $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 53px;"> '.$laFacturaDetalle[$i]->MontoPago.' </td> </tr>';
    
                    $ImporteFactura = $ImporteFactura + $laFacturaDetalle[$i]->MontoPago;
                }else{
                    $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                    $Historial = $Historial . '<td align="left" style="padding: 0;"> </td> </tr>';
                }

                $NFactura = $NFactura + 1;
            }else{

                if(isset($laHistorial[$Indice]->M3)){
                    $FechaPago = strtotime($laHistorial[$Indice]->FechaPago);
                    $Historial = '<tr> <td align="left" style="padding: 0;">'.$laHistorial[$Indice]->Mes.'<span style="float:right;">'.$laHistorial[$Indice]->MedidorAnormalidad.'</span></td>';
                    $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 10px;">'.(round($laHistorial[$Indice]->M3, 0)).'</td>';
                    $Historial = $Historial . '<td align="right" style="padding: 0;">'.$laHistorial[$Indice]->MontoFactura.'</td>';
                    $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 20px;">'.($laHistorial[$Indice]->Estado == "Impaga" ? "Pendiente" : date("d/m/Y", $FechaPago)).'</td>';

                    if($laHistorial[$Indice]->Estado == 'Impaga'){
                        $TotalDeuda = $TotalDeuda + $laHistorial[$Indice]->MontoFactura;
                        $NumeroMeses = $NumeroMeses + 1;
                    }

                    $Indice = $Indice + 1;

                    if ($i < $contar) {
                        $Historial = $Historial . '<td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 20px; padding-right: 0; font-size: 13px;">'.$laFacturaDetalle[$i]->NombreServicio.'</td>';
                        $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 55px;">'.$laFacturaDetalle[$i]->MontoPago.'</td> </tr>';
                        $ImporteFactura = $ImporteFactura + $laFacturaDetalle[$i]->MontoPago;
                    }else{
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td> </tr>';
                    }
                }
                else{
                    $Historial = $Historial . '<td align="right" style="padding: 0; color:#FFFFFF;""> PRUEBA </td>';
                    $Historial = $Historial . '<td align="left" style="padding: 0; color:#FFFFFF;""> PRUEBA </td>';

                    $Indice = $Indice + 1;

                    if ($i < $contar) {
                        $Historial = $Historial . '<td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 20px; padding-right: 0; font-size: 13px;">'.$laFacturaDetalle[$i]->NombreServicio.'</td>';
                        $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 55px;">'.$laFacturaDetalle[$i]->MontoPago.'</td> </tr>';
                        $ImporteFactura = $ImporteFactura + $laFacturaDetalle[$i]->MontoPago;
                    }else{
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td> </tr>';
                    }
                }
                
            }

            $Historico = $Historico . $Historial;
        }

        $DeudaAtrasada = $TotalDeuda;
        $TotalDeuda = $TotalDeuda + $laFacturaCliente[0]->MontoTotal;

        $mes = ["ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO", "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"][date("n") - 1];
        $timestamp = strtotime($Cobro);
        $newDate = $mes.'-'.date("Y", $timestamp);

        if (isset($FechaAnterior[0]->FechaLectura)) {
            $timestamp2 = strtotime($FechaAnterior[0]->FechaLectura);
            $FechaAnterior = date("d/m/Y", $timestamp2);
        }else {
            $FechaAnterior = "00/00/00";
        }

        $timestamp3 = strtotime($laFacturaCliente[0]->FechaVence);
        $FechaVence = date("d/m/Y", $timestamp3);

        $loMensaje = new MensajeClienteDAL;
        $loMensaje = $loMensaje->GetMensajes($Cliente, $Cobro, $DataBaseAlias);

        if(count($loMensaje) != 0){
            $mensajePrueba = $loMensaje[0]->Linea1 .' '. $loMensaje[0]->Linea2 .' '. $loMensaje[0]->Linea3 .' '. $loMensaje[0]->Linea4 .' '. $loMensaje[0]->Linea5;
        }else{
            $mensajePrueba = '';
        }

        if($mensajeAnormalidad && $mensajePrueba) $mensajePrueba = $mensajeAnormalidad .' <hr> '. $mensajePrueba;
        elseif ($mensajeAnormalidad && $mensajePrueba == '') $mensajePrueba = $mensajeAnormalidad;

            /*
            table td {
                border:1px solid black; 
            }
            */
        $array = '

        <style>
            .letra {
                font-family: Fantasy, sans-serif;
                text-align: justify;
                font-weight: bold;
                font-size: 12px;
                padding-top: 0;
                padding-right: 0;
                padding-bottom: 0;
                padding-left: 5px;
            }
            .rotate {
                text-align: center;
                white-space: nowrap;
                vertical-align: middle;
                width: 1.5em;
            }
            .rotate div {
                -moz-transform: rotate(-90.0deg);  /* FF3.5+ */
                -o-transform: rotate(-90.0deg);  /* Opera 10.5 */
                -webkit-transform: rotate(-90.0deg);  /* Saf3.1+, Chrome */
                filter:  progid:DXImageTransform.Microsoft.BasicImage(rotation=0.083);  /* IE6,IE7 */
                -ms-filter: "progid:DXImageTransform.Microsoft.BasicImage(rotation=0.083)"; /* IE8 */
                margin-left: -13em;
                margin-right: -15em;
            }
        </style>
        
        <div style="transform: rotate(180deg); margin: -30pt -32pt -25pt -5pt; font-family: Latin Modern Roman; font-style: bold;">
            <table style="width: 100%; font-size: 16px;" cellpadding="-5" cellspacing="-5">
                <thead>
                    <tr>
                        <th width="9%"> </th>
                        <th width="9%"> </th>
                        <th width="9%"> </th>
                        <th width="11%"> </th>
                        <th width="20%"> </th>
                        <th width="8%"> </th>
                        <th width="5%"> </th>
                        <th width="14%"> </th>
                        <th colspan="2" width="15%" style="font-size: 30px; padding-right: 35px;" height="40px" align="right">'. $Cliente .'</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td height="90px" colspan="6" align="left" style="font-size: 30px; padding: 0;">'. $laCliente[0]->Nombre .'</td>
                        <td colspan="2" align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 50px; padding-right: 0;">'. $laCliente[0]->NombreCategoria .'</td>
                        <td colspan="2" align="right" style="padding-right: 50px;">'. date('d/m/Y') .'</td>
                        <td class="rotate" rowspan="20" style="padding: 0; font-size: 18px;"><div>'.$Lecturador.'</div></td>
                    </tr>
                    <tr>
                        <td colspan="6" align="left" style="padding: 0;"> UV- '. $laCliente[0]->Uv .' MZ. '. $laCliente[0]->Manzana .' L. '. $laCliente[0]->Lote .' '. $laCliente[0]->Direccion .'</td>
                        <td colspan="2" align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 35px; padding-right: 0;">'. $newDate .'</td>
                        <td colspan="2" align="right" style="padding-right: 45px;">'. $FechaVence .'</td>
                    </tr>
                    <tr>
                        <td class="text-center">  </td>
                        <td colspan="4" align="left" style="font-size: 30px; padding-top: 0; padding-bottom: 0;  padding-left: 90px; padding-right: 0;">'. $CodigoUbicacion .'</td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                    </tr>
                    <tr>
                        <td colspan="10" height="42px">  </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="right">  </td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                        <td colspan="2" align="right">  </td>
                        <td colspan="2" align="right">  </td>
                        <td align="right">  </td>
                    </tr>
                    <tr>
                        <td colspan="1" align="right">'. date('d/m/Y') .'</td>
                        <td align="right" style="padding: 0;">'. $Lectura_Actual .'</td>
                        <td rowspan="2" colspan="1" align="right" style="padding: 0; font-size: 30px;">'. $Consumo .'</td>
                        <td rowspan="2" align="right" style="font-size: 30px; padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 20px;">'.$laFacturaCliente[0]->MedidorAnormalidad.'</td>
                        <td rowspan="2" colspan="1" align="left" style="font-size: 30px; padding-top: 0; padding-bottom: 0;  padding-left: 70px; padding-right: 0;">'.($contador->format($differenceFormat)).'</td>
                        <td rowspan="2" colspan="1" align="center" style="font-size: 30px;">'. $DeudaAtrasada .'</td>
                        <td rowspan="2" colspan="2" align="center" style="font-size: 30px;">'. $NumeroMeses .'</td>
                        <td colspan="2" align="right" style="padding-right: 75px;">'. (empty($FechaCorte) ? "" : "SÍ") .'</td>
                    </tr>
                    <tr>
                        <td colspan="1" align="right" style="padding: 0;">'. $FechaAnterior .'</td>
                        <td align="right">'. $Lectura_Anterior .'</td>
                        <td colspan="2" align="right" style="padding-right: 45px;">'. $FechaCorte .'</td>
                    </tr>
                    <tr>
                        <td colspan="10" height="70px"> </td>
                    </tr>
                    <tr>
                        <td colspan="6"> </td>
                        <td class="letra" colspan="3" height="200px" rowspan="13" VALIGN=top>'.$mensajePrueba.' </td>
                        <td colspan="1" rowspan="13" VALIGN=bottom align="right" style="margin-right: -15em;">
                            <img src="data:image/png;base64, {!!'. base64_encode(QrCode::format('png')->size(120)->generate($QR)) .'!!} ">
                        </td>
                    </tr>

                    '.
                        $Historico
                    .'

                    <tr>
                        <td colspan="10" height="25px"> </td>
                    </tr>
                    <tr>
                        <td colspan="1" align="left"> </td>
                        <td colspan="1" align="left"> </td>
                        <td colspan="2" align="left" style="padding-top: 0px; padding-bottom: 0;  padding-left: 30px; padding-right: 0; font-size: 30px;">'. (round($TotalDeuda, 2)) .'</td>
                        <td colspan="2" align="left" style="font-size: 30px; padding-left: 210px;">'. $ImporteFactura .'</td>
                        <td colspan="4" align="right">'.$ParaQR[0]->EtiquetaQR.'</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        ';
        
        $customPaper = array(0, 0, 289.13, 599.15); //596.20
        $pdf = PDF::loadHTML($array)
            ->setPaper($customPaper, 'landscape')
            ->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

        $namePDF = $GeneracionFactura . '_' . $Cobro . '_' . $CodigoUbicacion . '_' . $Cliente . '_' . date("Y-m-d") . '_' . date("H-i-s");
        $file = '/' . $EmpresaNombre . '/PDF';
        $filePath = public_path() . $file;

        if (!file_exists($filePath)) File::makeDirectory($filePath, $mode = 0777, true, true);
        
        $path = public_path() . $file . '/' . $namePDF . '.pdf';
        $pdf->save($path);
        $host = $_SERVER["HTTP_HOST"];
        return "http://" . $host . $file . '/' . $namePDF . '.pdf';
    }
}
