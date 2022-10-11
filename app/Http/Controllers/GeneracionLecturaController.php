<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\GeneracionLectura;

use App\Modelos\mPaqueteTodoFacil;

use App\BLL\GeneracionLecturaBLL;
use App\BLL\LecturaMovilRestNET;

use App\DAL\GeneracionLecturaDAL;
use App\DAL\ClienteDAL;
use App\DAL\FacturaDAL;
use App\DAL\CategoriaDAL;

use App\Http\Controllers\GeneracionLecturaFotoController;
use App\Http\Controllers\TrayectoriaController;

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

class GeneracionLecturaController extends Controller
{
     /**
     * Metodo que devuelve una de CLIENTE que se van a Lecturar
     * @method      listarLecturas()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       1-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      lista GENERACIONLECTURA
     */
    public function listarLecturas(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');
        $lnBuscar            = $request->input('dato');
        $lcTipoDato          = $request->input('tipo');

        switch ($lcTipoDato) {
            case 'Nombre':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.LecturaActual', '=', '0')
                ->where('GENERACIONLECTURA.Consumo', '=', '0')
                ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
                ->where('CLIENTE.Nombre', 'like', '%'.$lnBuscar.'%')
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'Codigo':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.LecturaActual', '=', '0')
                ->where('GENERACIONLECTURA.Consumo', '=', '0')
                ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
                ->where('GENERACIONLECTURA.Cliente', '=', $lnBuscar)
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'Ubicacion':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.LecturaActual', '=', '0')
                ->where('GENERACIONLECTURA.Consumo', '=', '0')
                ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
                ->where('GENERACIONLECTURA.CodigoUbicacion', 'like', '____'.$lnBuscar.'%')
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'UbicacionOtro':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.LecturaActual', '=', '0')
                ->where('GENERACIONLECTURA.Consumo', '=', '0')
                ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
                ->where('GENERACIONLECTURA.CodigoUbicacion', 'like', '%'.$lnBuscar.'%')
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;
        }

        $generacionLectura = [
            'pagination' => [
                'total' => $generacionLectura->total(),
                "current_page" => $generacionLectura->currentPage(),
                "per_page" => $generacionLectura->perPage(),
                "last_page" => $generacionLectura->lastPage(),
                "from" => $generacionLectura->firstItem(),
                "to" => $generacionLectura->lastItem(),
            ],
            'generacionLectura' => $generacionLectura
        ];
        
        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $generacionLectura;
        return response()->json($loPaquete);
    }

    public function listarProcesadas(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');
        $lnBuscar            = $request->input('dato');
        $lcTipoDato          = $request->input('tipo');

        switch ($lcTipoDato) {
            case 'Nombre':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('CLIENTE.Nombre', 'like', '%'.$lnBuscar.'%')
                ->where(function($query){
                    $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
                    ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
                    ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
                })
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'Codigo':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.Cliente', '=', $lnBuscar)
                ->where(function($query){
                    $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
                    ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
                    ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
                })
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'Ubicacion':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->leftJoin('MEDIDOR', 'GENERACIONLECTURA.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.CodigoUbicacion', 'like', '____'.$lnBuscar.'%')
                ->where(function($query){
                    $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
                    ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
                    ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
                })
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'UbicacionOtro':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.CodigoUbicacion', 'like', '%'.$lnBuscar.'%')
                ->where(function($query){
                    $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
                    ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
                    ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
                })
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;
        }

        $generacionLectura = [
            'pagination' => [
                'total' => $generacionLectura->total(),
                "current_page" => $generacionLectura->currentPage(),
                "per_page" => $generacionLectura->perPage(),
                "last_page" => $generacionLectura->lastPage(),
                "from" => $generacionLectura->firstItem(),
                "to" => $generacionLectura->lastItem(),
            ],
            'generacionLectura' => $generacionLectura
        ];
        
        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $generacionLectura;
        return response()->json($loPaquete);
    }

    /**
     * Metodo que devuelve un objeto CLIENTE que se va a Lecturar
     * @method      verLecturaId()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       1-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      objeto GENERACIONLECTURA
     */
    public function verLecturaIdProcesada(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');
        $lnCliente           = $request->input('tcCliente');

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
        ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC')
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
        ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
        ->where('GeneracionFactura', '=', $lnGeneracionFactura)
        ->where('GENERACIONLECTURA.Cliente', '=', $lnCliente)
        ->get();

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loGeneracionLectura;
        return response()->json($loPaquete);
    }

    public function verLecturaId(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');
        $lnCliente           = $request->input('tcCliente');

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->where('GeneracionFactura', '=', $lnGeneracionFactura)
        ->where('GENERACIONLECTURA.Cliente', '=', $lnCliente)
        ->get();

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loGeneracionLectura;
        return response()->json($loPaquete);
    }

    /**
     * Metodo que devuelve un objeto CLIENTE que se va a Lecturar
     * @method      verLecturaIdNext()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       23-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      objeto GENERACIONLECTURA
     */
    public function verLecturaIdNext(Request $request){
        $lnDataBaseAlias = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->where('GENERACIONLECTURA.LecturaActual', '=', 0)
        ->where('GENERACIONLECTURA.Consumo', '=', 0)
        ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
        ->where('GeneracionFactura', '=', $lnGeneracionFactura)
        ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
        ->limit('1')
        ->get();

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loGeneracionLectura;
        return response()->json($loPaquete);
    }

    public function verLecturaIdNextProcesada(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnCodigpUbicacion   = $request->CodigoUbicacion;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
        ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC')
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
        ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
        ->where('GeneracionFactura', '=', $lnGeneracionFactura)
        ->where(function($query){
            $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
            ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
            ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
        })
        ->having('GENERACIONLECTURA.CodigoUbicacion', '>', $lnCodigpUbicacion)
        ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
        ->limit('1')
        ->get();

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loGeneracionLectura;
        return response()->json($loPaquete);
    }

    public function lecturasPendientesLecturados(Request $request){
        $lnDataBaseAlias = $request->DataBaseAlias;

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
            ->select('GENERACIONLECTURA.GeneracionFactura',
        (DB::raw('count(GENERACIONLECTURA.GeneracionFactura) as Pendientes')),
        (DB::raw('sum(case when (GENERACIONLECTURA.LecturaActual = 0 and GENERACIONLECTURA.Consumo = 0 and GENERACIONLECTURA.MedidorAnormalidad = 0) then 00000 else 00001 end) as Lecturados')))
        ->where('GENERACIONLECTURA.GeneracionFactura', '=', $request->tcGeneracionFactura)->get();

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loGeneracionLectura;
        return response()->json($loPaquete);
    }

    // public $Medidor, $Categoria, $SocioConMedidor;
    public function DO_CargarMedidorYConsumoAsignado($Cliente, $DataBaseAlias){
        $loClienteDAL = ClienteDAL::Get_DatosConMedidor($Cliente, $DataBaseAlias);

        if (count($loClienteDAL) > 0 && $loClienteDAL != null) {
            $Medidor   = $loClienteDAL[0]->Medidor;
            $Categoria = $loClienteDAL[0]->Categoria;
            $SocioConMedidor = $Medidor > 0 ? $Medidor : 0;
        }else{
            $loClienteDAL = 'Cliente no existe';
        }

        return $SocioConMedidor;
    }
    
    /**
     * Metodo que devuelve un objeto con la GENERACIONLECTURA registrada
     * @method      DO_LecturarNormal()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       06-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      registro GENERACIONLECTURA
     */
    public function DO_LecturarNormal(Request $request){
        $GeneracionLecturaFoto  = new GeneracionLecturaFotoController;
        $loTrayectoria          = new TrayectoriaController;
        $loGeneracionLecturaBLL = new GeneracionLecturaBLL;
        $loPaquete              = new mPaqueteTodoFacil();
        $loGeneracionLectura    = [];

        $laGeneracionLectura['tcMedidorAnormalidad2'] = $request->input('tnMedidorAnormalidad2');
        $laGeneracionLectura['tcMedidorAnormalidad'] = $request->input('tnMedidorAnormalidad');
        $laGeneracionLectura['tcGeneracionLectura']  = $request->input('tnGeneracionFactura');
        $laGeneracionLectura['tcLecturaAnterior']    = $request->input('tnLecturaAnterior');
        $laGeneracionLectura['tcLecturaActual']      = $request->input('tnLecturaActual');
        $laGeneracionLectura['tcCliente']            = $request->input('tnCliente');
        
        $laGeneracionLectura['tcCategoria']          = $request->input('tnCategoria');
        $laGeneracionLectura['tcMedidor']            = $request->input('tnMedidor');
        $laGeneracionLectura['tcMedia']              = $request->input('tnMedia');
        $laGeneracionLectura['llNuevaLectura']       = $request->input('llNuevaLectura');
        $laGeneracionLectura['DataBaseAlias']        = $request->input('DataBaseAlias');
        $laGeneracionLectura['tnPlomero']            = $request->input('tnPlomero');

        $SocioConMedidor = $this->DO_CargarMedidorYConsumoAsignado($request->tnCliente, $request->DataBaseAlias);

        if ($request->tnMedidor == 0 && $SocioConMedidor == 0) {
            $loCategoriaDAL = new CategoriaDAL;
            $loCategoria = $loCategoriaDAL->GetConsumoMinimo($request->tnCategoria, $request->DataBaseAlias);
            $lnConsumo     = $loCategoria[0]->ConsumoMinimo;
            $LecturaActual = $lnConsumo;
            $Consumo       = $lnConsumo;
            $lnMedidorAnormalidad = 0;
            $lnTipoConsumo = 1;

            GeneracionLecturaDAL::ActualizarLecturaSinMedidorDAL($request->tnGeneracionFactura, $request->tnCliente, $LecturaActual, $Consumo, $lnMedidorAnormalidad, $request->DataBaseAlias);
            $loGeneracionLecturaBLL->ActualizarLecturaMovilSinMedidor($request->tnGeneracionFactura, $request->tnCliente, $request->tnCategoria, $lnTipoConsumo, $lnMedidorAnormalidad, $request->DataBaseAlias);
            $loGeneracionLectura['Error'] = 2000;
        }else{
            $loGeneracionLectura = $loGeneracionLecturaBLL->ModificarYValidarLectura($laGeneracionLectura);
        }

        if ($loGeneracionLectura['Error'] == 2000) {
            $loPaquete->values = $loGeneracionLectura;
            if ($request->file('imageEnviar')){
                $GeneracionLecturaFoto->imagenStore($request->file('imageEnviar'), $request->input('tnGeneracionFactura'), $request->input('tnCobro'),
                $request->input('tnCodigoUbicacion'), $request->input('tnCliente'), $request->input('EmpresaNombre'), $request->input('DataBaseAlias'));
            }

            if ($request->input('Latitud') && $request->input('Longitud')) {
                $loTrayectoria->GuardarUbicacion($request->input('tnGeneracionFactura'), /*$request->input('tnPlomero'),*/ $request->input('tnCliente'), 
                $request->input('Latitud'), $request->input('Longitud'), $request->input('DataBaseAlias'));
            }
        }else{
            $loPaquete->error   = 1;
            $loPaquete->status  = 0;
            $loPaquete->message = "Error";
            $loPaquete->values  = $loGeneracionLectura;
        }

        return response()->json($loPaquete);
    }
}
