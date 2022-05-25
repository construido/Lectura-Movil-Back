<?php

namespace App\BLL;

use App\Models\Usuario;
use App\Models\ParaQR;

use App\DAL\ParametrosGeneralesDAL;
use App\DAL\HistoricoFacturaDAL;
use App\DAL\FacturaDetalleDAL;
use App\DAL\ClienteDAL;
use App\DAL\FacturaDAL;

use App\Http\Controllers\UsuarioController;

use Tymon\JWTAuth\Facades\JWTAuth;

use DB;

use PDF;
use File;
use QrCode;

class ImprimirBLL
{
    public function GetFactura($Cliente, $CodigoUbicacion, $Lectura_Actual, $Lectura_Anterior, $Consumo, $Cobro, $GeneracionFactura, $DataBaseAlias, $EmpresaNombre){
        $ParaQR = ParaQR::on($DataBaseAlias)->get();
        $URL    = trim($ParaQR[0]->Url);
        $ComerceID = trim($ParaQR[0]->ComerceID);
        $QR     = $URL.$ComerceID.'/'.$Cliente;

        // $Usuario = new UsuarioController;
        // $Usuario = $Usuario->obtenerLecturador(JWTAuth::user()->Usuario);
        // $Lecturador = $Usuario[0]->Nombre.' '.$Usuario[0]->Apellidos.' '.date("Y-m-d").' '.date("H:i:s");

        $laCliente = new ClienteDAL;
        $laCliente = $laCliente->GetDatosCliente($Cliente, $DataBaseAlias);
        
        $laFactura = new FacturaDAL;
        $laFacturaCliente = $laFactura->GetDatosFacturaSocio($GeneracionFactura, $Cliente, $DataBaseAlias); // TODO : se le aumento GeneracionFactura
        $FechaEmision = $laFacturaCliente[0]->FechaEmision;
        $Corte        = $laFacturaCliente[0]->Corte;
        $FechaCorte   = $laFactura->GetFechaCorte($FechaEmision, $Corte, $DataBaseAlias);

        $laHistoricoFactura = new HistoricoFacturaDAL;
        $laHistorial        = $laHistoricoFactura->TraerHistorial($Cliente, $DataBaseAlias);
        $FechaAnterior      = $laHistoricoFactura->GetFechaLecturaAnterior($Cliente, $DataBaseAlias);
        $ConsumoAnterior    = $laHistorial[0]->M3;

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
                $Historial = '<tr> <td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 10px; padding-right: 0;">'.$laFacturaCliente[0]->Cobro.'<span style="float:right;">'.(round($laFacturaCliente[0]->Consumo, 0)).'</span></td>';
                $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 0;">'.$laFacturaCliente[0]->MontoTotal.'</td>';
                $Historial = $Historial . '<td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 55px; padding-right: 0;">'.($laFacturaCliente[0]->Estado == 1 ? "Impaga" : "Pagado").'</td>';
                
                if ($i < $contar) {
                    $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 30px;">'.$laFacturaDetalle[$i]->Servicio.'</td>';
                    $Historial = $Historial . '<td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 0; font-size: 13px;">'.$laFacturaDetalle[$i]->NombreServicio.' </td>';
                    $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 30px;"> '.$laFacturaDetalle[$i]->MontoPago.' </td> </tr>';
    
                    $ImporteFactura = $ImporteFactura + $laFacturaDetalle[$i]->MontoPago;
                }else{
                    $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                    $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                    $Historial = $Historial . '<td align="left" style="padding: 0;"> </td> </tr>';
                }

                $NFactura = $NFactura + 1;
            }else{

                if(isset($laHistorial[$Indice]->M3)){
                    $Historial = '<tr> <td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 10px; padding-right: 0;">'.$laHistorial[$Indice]->Mes.'<span style="float:right;">'.(round($laHistorial[$Indice]->M3, 0)).'</span></td>';
                    $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 0;">'.$laHistorial[$Indice]->MontoFactura.'</td>';
                    $Historial = $Historial . '<td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 55px; padding-right: 0;">'.$laHistorial[$Indice]->Estado.'</td>';

                    if($laHistorial[$Indice]->Estado == 'Impaga'){
                        $TotalDeuda = $TotalDeuda + $laHistorial[$Indice]->MontoFactura;
                        $NumeroMeses = $NumeroMeses + 1;
                    }

                    $Indice = $Indice + 1;

                    if ($i < $contar) {
                        $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 30px;">'.$laFacturaDetalle[$i]->Servicio.'</td>';
                        $Historial = $Historial . '<td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 0; font-size: 13px;">'.$laFacturaDetalle[$i]->NombreServicio.'</td>';
                        $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 30px;">'.$laFacturaDetalle[$i]->MontoPago.'</td> </tr>';
                        $ImporteFactura = $ImporteFactura + $laFacturaDetalle[$i]->MontoPago;
                    }else{
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td> </tr>';
                    }
                }
                else{
                    $Historial = '<tr> <td align="center" style="padding: 0; color:#FFFFFF"> PRUEBA </td>';
                    $Historial = $Historial . '<td align="right" style="padding: 0; color:#FFFFFF""> PRUEBA </td>';
                    $Historial = $Historial . '<td align="left" style="padding: 0; color:#FFFFFF""> PRUEBA </td>';

                    $Indice = $Indice + 1;

                    if ($i < $contar) {
                        $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 30px;">'.$laFacturaDetalle[$i]->Servicio.'</td>';
                        $Historial = $Historial . '<td align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 0; font-size: 13px;">'.$laFacturaDetalle[$i]->NombreServicio.'</td>';
                        $Historial = $Historial . '<td align="right" style="padding-top: 0; padding-bottom: 0;  padding-left: 0; padding-right: 30px;">'.$laFacturaDetalle[$i]->MontoPago.'</td> </tr>';
                        $ImporteFactura = $ImporteFactura + $laFacturaDetalle[$i]->MontoPago;
                    }else{
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td>';
                        $Historial = $Historial . '<td align="left" style="padding: 0;"> </td> </tr>';
                    }
                }
                
            }

            $Historico = $Historico . $Historial;
        }

        $DeudaAtrasada = $TotalDeuda;
        $TotalDeuda = $TotalDeuda + $laFacturaCliente[0]->MontoTotal;

        $timestamp = strtotime($Cobro);
        $newDate = date("m/Y", $timestamp);

        $timestamp2 = strtotime($FechaAnterior[0]->FechaLectura);
        $FechaAnterior = date("d/m/Y", $timestamp2);

        $timestamp3 = strtotime($laFacturaCliente[0]->FechaVence);
        $FechaVence = date("d/m/Y", $timestamp3);

        $array = '

        <div style="transform: rotate(180deg); margin: -20pt -25pt -25pt -5pt; font-family: Latin Modern Roman; font-style: bold;">
            <table style="width: 100%; font-size: 16px;" cellpadding="-5" cellspacing="-5">
                <thead>
                    <tr>
                        <th width="12%"> </th>
                        <th width="9%"> </th>
                        <th width="9%"> </th>
                        <th width="11%"> </th>
                        <th width="19%"> </th>
                        <th width="6%"> </th>
                        <th width="5%"> </th>
                        <th width="10%"> </th>
                        <th colspan="2" width="19%" style="font-size: 30px; padding-right: 35px;" height="40px" align="right">'. $Cliente .'</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td height="85px" colspan="6" align="left" style="font-size: 30px; padding-top: 0; padding-bottom: 0;  padding-left: 10px; padding-right: 0;">'. $laCliente[0]->Nombre .'</td>
                        <td colspan="2" align="center" style="padding: 0px;">'. $laCliente[0]->NombreCategoria .'</td>
                        <td colspan="2" align="right" style="padding-right: 45px;">'. date('d/m/Y') .'</td>
                    </tr>
                    <tr>
                        <td colspan="6" align="left" style="padding-top: 0; padding-bottom: 0;  padding-left: 10px; padding-right: 0;"> UV- '. $laCliente[0]->Uv .' MZ. '. $laCliente[0]->Manzana .' L. '. $laCliente[0]->Lote .' '. $laCliente[0]->Direccion .'</td>
                        <td colspan="2" align="center" style="padding: 0;">'. $newDate .'</td>
                        <td colspan="2" align="right" style="padding-right: 45px;">'. $FechaVence .'</td>
                    </tr>
                    <tr>
                        <td class="text-center">  </td>
                        <td colspan="4" align="left" style="font-size: 30px; padding-top: 0; padding-bottom: 0;  padding-left: 80px; padding-right: 0;">'. $CodigoUbicacion .'</td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                        <td align="right">  </td>
                    </tr>
                    <tr>
                        <td colspan="10" height="45px">  </td>
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
                        <td rowspan="1" colspan="1" align="right" style="padding: 0;">'. $Consumo .'</td>
                        <td rowspan="2" align="center">'.$laFacturaCliente[0]->MedidorAnormalidad.'</td>
                        <td rowspan="2" colspan="2" align="center" style="font-size: 30px;">'. $DeudaAtrasada .'</td>
                        <td rowspan="2" colspan="1" align="center" style="font-size: 30px;">'. $NumeroMeses .'</td>
                        <td colspan="1" align="right"> </td>
                        <td colspan="2" align="right" style="padding-right: 75px;">'. (empty($FechaCorte) ? "" : "SÍ") .'</td>
                    </tr>
                    <tr>
                        <td colspan="1" align="right" style="padding: 0;">'. $FechaAnterior .'</td>
                        <td align="right">'. $Lectura_Anterior .'</td>
                        <td rowspan="1" colspan="1" align="right" style="padding: 0;">'. round($ConsumoAnterior, 0) .'</td>
                        <td colspan="1" align="right"> </td>
                        <td colspan="2" align="right" style="padding-right: 45px;">'. $FechaCorte .'</td>
                    </tr>
                    <tr>
                        <td colspan="10" height="75px"> </td>
                    </tr>
                    <tr>
                        <td colspan="7"> </td>
                        <td colspan="3" rowspan="13" VALIGN=bottom align="right">
                            <img src="data:image/png;base64, {!!'. base64_encode(QrCode::format('png')->size(120)->generate($QR)) .'!!} ">
                        </td>
                    </tr>

                    '.
                        $Historico
                    .'

                    <tr>
                        <td colspan="1" align="left"> </td>
                        <td colspan="1" align="left"> </td>
                        <td colspan="2" align="left" style="font-size: 30px;">'. $TotalDeuda .'</td>
                        <td colspan="2" align="left" style="font-size: 30px; padding-left: 180px;">'. $ImporteFactura .'</td>
                        <td colspan="1" align="left"> </td>
                    </tr>
                </tbody>
            </table>
            <spam style="font-size: 16px; float:right">'.$ParaQR[0]->EtiquetaQR.'</sapm>
        </div>
        
        ';

        $customPaper = array(0, 0, 289.13, 596.20);
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
