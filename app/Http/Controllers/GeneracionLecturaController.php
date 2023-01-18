<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\MedidorAnormalidad;
use App\Models\GeneracionLectura;
use App\Models\ParametroLectura;

use App\Modelos\mPaqueteTodoFacil;
use App\Modelos\GuardarErrores;

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
    public function verLecturaIdPendiente(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');
        $lnAnormalidad       = $request->input('tcAnormalidad');
        $lnCliente           = $request->input('tcCliente');

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
        ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte',
                'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2') // TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
        ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
        ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
        ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
        ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
        ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
        ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
        ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
        ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', $lnAnormalidad)
        ->where('GENERACIONLECTURA.Cliente', '=', $lnCliente)
        ->get();

        if(isset($loGeneracionLectura[0]->MedidorAnormalidad2)) $loGeneracionLectura['Categorizar'] = $this->BuscarAnormalidadCategorizar($loGeneracionLectura[0]->MedidorAnormalidad2, $lnDataBaseAlias);
        else $loGeneracionLectura['Categorizar'] = false;

        if(isset($loGeneracionLectura[0]->MedidorAnormalidad)) $loGeneracionLectura['Pendiente'] = $this->BuscarAnormalidadPendiente($loGeneracionLectura[0]->MedidorAnormalidad, $lnDataBaseAlias);
        else $loGeneracionLectura['Pendiente'] = false;

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loGeneracionLectura;
        return response()->json($loPaquete);
    }

    public function verLecturaIdNextPendiente(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnAnormalidad       = $request->tcAnormalidad;
        $lnCodigpUbicacion   = $request->CodigoUbicacion;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
        ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte',
                'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2') // TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
        ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
        ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
        ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
        ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
        ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
        ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
        ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', $lnAnormalidad)
        ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
        ->where(function($query){
            $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
            ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
            ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
        })
        ->having('GENERACIONLECTURA.CodigoUbicacion', '>', $lnCodigpUbicacion)
        ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
        ->limit('1')
        ->get();

        $array = [];

        if(isset($loGeneracionLectura[0]->MedidorAnormalidad2)) $array['Categorizar'] = $this->BuscarAnormalidadCategorizar($loGeneracionLectura[0]->MedidorAnormalidad2, $lnDataBaseAlias);
        else $array['Categorizar'] = false;

        if(isset($loGeneracionLectura[0]->MedidorAnormalidad)) $array['Pendiente'] = $this->BuscarAnormalidadPendiente($loGeneracionLectura[0]->MedidorAnormalidad, $lnDataBaseAlias);
        else $array['Pendiente'] = false;

        $array['GeneracionLectura'] = $loGeneracionLectura;

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $array;
        return response()->json($loPaquete);
    }

    public function buscarCliente(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');
        $lnBuscar            = $request->input('dato');
        $lcTipoDato          = $request->input('tipo');

        switch ($lcTipoDato) {
            case 'Codigo':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte',
                    'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2')
                ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad')
                ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo')
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.Cliente', '=', $lnBuscar)
                ->get();

                if(count($generacionLectura) == 0){
                    $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                    ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                        'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte')
                    ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                    ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                    ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                    ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                    ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                    ->where('GENERACIONLECTURA.Cliente', '=', $lnBuscar)
                    ->get();
                }
                break;

            case 'Ubicacion':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte',
                    'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2')
                ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad')
                ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo')
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')
                ->where('GENERACIONLECTURA.CodigoUbicacion', 'like', '____'.$lnBuscar.'%')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->get();

                if(count($generacionLectura) == 0){
                    $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                    ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                        'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte')
                    ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                    ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                    ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                    ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                    ->where('GENERACIONLECTURA.CodigoUbicacion', 'like', '____'.$lnBuscar.'%')
                    ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                    ->get();
                }
                break;
        }

        $array = [];

        if(isset($generacionLectura[0]->MedidorAnormalidad2)) $array['Categorizar'] = $this->BuscarAnormalidadCategorizar($generacionLectura[0]->MedidorAnormalidad2, $lnDataBaseAlias);
        else $array['Categorizar'] = false;

        if(isset($generacionLectura[0]->MedidorAnormalidad)) $array['Pendiente'] = $this->BuscarAnormalidadPendiente($generacionLectura[0]->MedidorAnormalidad, $lnDataBaseAlias);
        else $array['Pendiente'] = false;

        $array['GeneracionLectura'] = $generacionLectura;
        
        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $array;
        return response()->json($loPaquete);
    }

    public function listarPendientes(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');
        $lnBuscar            = $request->input('dato');
        $lcTipoDato          = $request->input('tipo');

        switch ($lcTipoDato) {
            case 'MedidorAnormalidad':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')), 'CATEGORIA.NombreCategoria',
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2',// TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
                ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
                ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
                ->where('MEDIDORANORMALIDAD.MedidorAnormalidad', '=', $lnBuscar)
                ->where(function($query){
                    $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
                    ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
                    ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
                })
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'LecturaPendiente':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')), 'CATEGORIA.NombreCategoria',
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura')
                ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo')
                ->leftJoin('MEDIDOR', 'GENERACIONLECTURA.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->join('PARAMETROLECTURA', 'MEDIDORANORMALIDAD.MedidorAnormalidad', 'PARAMETROLECTURA.AnormalidadPendiente')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')
                ->whereColumn('MEDIDORANORMALIDAD.MedidorAnormalidad', '=', 'PARAMETROLECTURA.AnormalidadPendiente')
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

    public function AnormalidadesEspeciales($DataBaseAlias){
        $loParametroLectura = ParametroLectura::on($DataBaseAlias)
            ->select('AnormalidadNuevo', 'AnormalidadCambioMedidor', 'AnormalidadRegularizacionBajaTemporal')
            ->get();
        return $loParametroLectura;
    }
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

        $anormalidades = $this->AnormalidadesEspeciales($lnDataBaseAlias);

        switch ($lcTipoDato) {
            case 'Nombre':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'CATEGORIA.NombreCategoria',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.LecturaActual', '=', '0')
                ->where('GENERACIONLECTURA.Consumo', '=', '0')
                // ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
                ->whereIn('GENERACIONLECTURA.MedidorAnormalidad', [0, $anormalidades[0]->AnormalidadNuevo,
                    $anormalidades[0]->AnormalidadCambioMedidor, $anormalidades[0]->AnormalidadRegularizacionBajaTemporal])
                ->where('CLIENTE.Nombre', 'like', '%'.$lnBuscar.'%')
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'Codigo':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'CATEGORIA.NombreCategoria',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.LecturaActual', '=', '0')
                ->where('GENERACIONLECTURA.Consumo', '=', '0')
                // ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
                ->whereIn('GENERACIONLECTURA.MedidorAnormalidad', [0, $anormalidades[0]->AnormalidadNuevo,
                    $anormalidades[0]->AnormalidadCambioMedidor, $anormalidades[0]->AnormalidadRegularizacionBajaTemporal])
                ->where('GENERACIONLECTURA.Cliente', '=', $lnBuscar)
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'Ubicacion':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'CATEGORIA.NombreCategoria',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.LecturaActual', '=', '0')
                ->where('GENERACIONLECTURA.Consumo', '=', '0')
                // ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
                ->whereIn('GENERACIONLECTURA.MedidorAnormalidad', [0, $anormalidades[0]->AnormalidadNuevo,
                    $anormalidades[0]->AnormalidadCambioMedidor, $anormalidades[0]->AnormalidadRegularizacionBajaTemporal])
                ->where('GENERACIONLECTURA.CodigoUbicacion', 'like', '____'.$lnBuscar.'%')
                ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
                ->paginate(10);
                break;

            case 'UbicacionOtro':
                $generacionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select((DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,1,2) as Zona')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,3,2) as Ruta')),
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')),
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'CATEGORIA.NombreCategoria',
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->where('GENERACIONLECTURA.LecturaActual', '=', '0')
                ->where('GENERACIONLECTURA.Consumo', '=', '0')
                // ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
                ->whereIn('GENERACIONLECTURA.MedidorAnormalidad', [0, $anormalidades[0]->AnormalidadNuevo,
                    $anormalidades[0]->AnormalidadCambioMedidor, $anormalidades[0]->AnormalidadRegularizacionBajaTemporal])
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
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')), 'CATEGORIA.NombreCategoria',
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2',// TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
                ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
                ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
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
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')), 'CATEGORIA.NombreCategoria',
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2',// TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
                ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
                ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
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
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')), 'CATEGORIA.NombreCategoria',
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2',// TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
                ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
                ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
                ->leftJoin('MEDIDOR', 'GENERACIONLECTURA.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
                ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
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
                    (DB::raw('substr(GENERACIONLECTURA.CodigoUbicacion,5,5) as CodigoUbicacion')), 'CATEGORIA.NombreCategoria',
                    'MARCAMEDIDOR.NombreMarcaMedidor', 'GENERACIONLECTURA.CodigoUbicacion as CodUbi', 'GENERACIONLECTURA.Cobro', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2',// TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
                    'GENERACIONLECTURA.Cliente', 'GENERACIONLECTURA.LecturaAnterior', 'GENERACIONLECTURA.GeneracionFactura', 'GENERACIONLECTURA.ConsumoFacturado', 'TIPOCONSUMO.Nombre as NombreTC',
                    'CLIENTE.Nombre', 'MEDIDOR.NumeroSerie', 'MEDIDOR.Numero', 'GENERACIONLECTURA.LecturaActual', 'GENERACIONLECTURA.Consumo', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad',
                    (DB::raw('floor(GENERACIONLECTURA.Media) as Media')))
                ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
                ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
                ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
                ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
                ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
                ->leftJoin('MEDIDOR', 'CLIENTE.Medidor', '=', 'MEDIDOR.Medidor')
                ->leftJoin('MARCAMEDIDOR', 'MEDIDOR.MarcaMedidor', '=', 'MARCAMEDIDOR.MarcaMedidor')
                ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
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
        ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte',
                'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2') // TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
        ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
        ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
        ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
        ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
        ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
        ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
        ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
        ->where('GENERACIONLECTURA.Cliente', '=', $lnCliente)
        ->get();

        if(isset($loGeneracionLectura[0]->MedidorAnormalidad2)) $loGeneracionLectura['Categorizar'] = $this->BuscarAnormalidadCategorizar($loGeneracionLectura[0]->MedidorAnormalidad2, $lnDataBaseAlias);
        else $loGeneracionLectura['Categorizar'] = false;

        if(isset($loGeneracionLectura[0]->MedidorAnormalidad)) $loGeneracionLectura['Pendiente'] = $this->BuscarAnormalidadPendiente($loGeneracionLectura[0]->MedidorAnormalidad, $lnDataBaseAlias);
        else $loGeneracionLectura['Pendiente'] = false;
        
        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loGeneracionLectura;
        return response()->json($loPaquete);
    }

    public function BuscarAnormalidadCategorizar($Anormalidad, $lnDataBaseAlias){
        $loCategoria = ParametroLectura::on($lnDataBaseAlias)->where('AnormalidadVerificarCategoria', '=', $Anormalidad)->get();
        $loCategoria = isset($loCategoria[0]->AnormalidadVerificarCategoria) ? true : false;

        return $loCategoria;
    }

    public function BuscarAnormalidadPendiente($Anormalidad, $lnDataBaseAlias){
        $loPendiente = ParametroLectura::on($lnDataBaseAlias)->where('AnormalidadPendiente', '=', $Anormalidad)->get();
        $loPendiente = isset($loPendiente[0]->AnormalidadPendiente) ? true : false;

        return $loPendiente;
    }

    public function verLecturaId(Request $request){
        $lnDataBaseAlias     = $request->DataBaseAlias;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');
        $lnCliente           = $request->input('tcCliente');

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
        ->select('GENERACIONLECTURA.*', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte', 'CLIENTE.Nombre', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'TIPOCONSUMO.Nombre as NombreTC')
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
        ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
        ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
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
        $lnCodigpUbicacion   = $request->CodigoUbicacion;
        $lnGeneracionFactura = $request->input('tcGeneracionFactura');

        $anormalidades = $this->AnormalidadesEspeciales($lnDataBaseAlias);

        $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
        ->select('GENERACIONLECTURA.*', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte', 'CLIENTE.Nombre', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'TIPOCONSUMO.Nombre as NombreTC')
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
        ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
        ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
        ->where('GENERACIONLECTURA.LecturaActual', '=', 0)
        ->where('GENERACIONLECTURA.Consumo', '=', 0)
        // ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', '0')
        ->whereIn('GENERACIONLECTURA.MedidorAnormalidad', [0, $anormalidades[0]->AnormalidadNuevo,
            $anormalidades[0]->AnormalidadCambioMedidor, $anormalidades[0]->AnormalidadRegularizacionBajaTemporal])
        ->where('GeneracionFactura', '=', $lnGeneracionFactura)
        ->having('GENERACIONLECTURA.CodigoUbicacion', '>', $lnCodigpUbicacion)
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
        ->select('GENERACIONLECTURA.*', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.MedidorAnormalidad', 'CATEGORIA.NombreCategoria', 'CLIENTE.Corte',
                'CLIENTE.Nombre', 'TIPOCONSUMO.Nombre as NombreTC', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2','MA2.NombreAnormalidad as NA2', 'TC2.Nombre as N2') // TODO : se aumento MedidorAnormalidad2 y 'MA2.NombreAnormalidad TC2.Nombre
        ->join('CLIENTE', 'GENERACIONLECTURA.Cliente', '=', 'CLIENTE.Cliente')
        ->join('CATEGORIA', 'CLIENTE.Categoria', '=', 'CATEGORIA.Categoria')
        ->join('GENERACIONLECTURAMOVIL', 'GENERACIONLECTURA.GeneracionFactura', '=', 'GENERACIONLECTURAMOVIL.GeneracionFactura') // TODO : se modificó la consulta para la segunda anormalidad
        ->join('MEDIDORANORMALIDAD as MA2', 'GENERACIONLECTURAMOVIL.MedidorAnormalidad2', '=', 'MA2.MedidorAnormalidad') // TODO : se modificó la consulta para el nombre de la segunda anormalidad
        ->leftJoin('TIPOCONSUMO as TC2', 'MA2.TipoConsumo', '=', 'TC2.TipoConsumo') // TODO : se modificó la consulta para el TipoConsumo de la segunda anormalidad
        ->join('MEDIDORANORMALIDAD', 'GENERACIONLECTURA.MedidorAnormalidad', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
        ->leftjoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
        ->where('GENERACIONLECTURA.GeneracionFactura', '=', $lnGeneracionFactura)
        ->whereColumn('GENERACIONLECTURAMOVIL.Cliente', '=', 'GENERACIONLECTURA.Cliente')  // TODO : se aumento MedidorAnormalidad2
        ->where(function($query){
            $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
            ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
            ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
        })
        ->having('GENERACIONLECTURA.CodigoUbicacion', '>', $lnCodigpUbicacion)
        ->orderBy('GENERACIONLECTURA.CodigoUbicacion', 'ASC')
        ->limit('1')
        ->get();

        $array = [];
        if(isset($loGeneracionLectura[0]->MedidorAnormalidad2)) $array['Categorizar'] = $this->BuscarAnormalidadCategorizar($loGeneracionLectura[0]->MedidorAnormalidad2, $lnDataBaseAlias);
        else $array['Categorizar'] = false;

        if(isset($loGeneracionLectura[0]->MedidorAnormalidad)) $array['Pendiente'] = $this->BuscarAnormalidadPendiente($loGeneracionLectura[0]->MedidorAnormalidad, $lnDataBaseAlias);
        else $array['Pendiente'] = false;

        $array['GeneracionLectura'] = $loGeneracionLectura;

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $array;
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

    public function AnormalidadPendiente($DataBaseAlias){
        $parametroLectura = ParametroLectura::on($DataBaseAlias)
            ->select('AnormalidadPendiente')
            ->join('MEDIDORANORMALIDAD', 'PARAMETROLECTURA.AnormalidadPendiente', '=', 'MEDIDORANORMALIDAD.MedidorAnormalidad')
            ->get();

        return $parametroLectura[0]->AnormalidadPendiente;
    }

    public function lecturasPendientesAnormalidades(Request $request){
        $lnDataBaseAlias = $request->DataBaseAlias;
        $tipo            = $request->tcTipo;

        switch ($tipo) {
            case 'LecturaPendiente':
                $anormalidad = $this->AnormalidadPendiente($lnDataBaseAlias);
                $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
                    ->select('GENERACIONLECTURA.GeneracionFactura',
                        (DB::raw('count(GENERACIONLECTURA.GeneracionFactura) as Pendientes')),
                        (DB::raw('sum(case when (GENERACIONLECTURA.MedidorAnormalidad = '.$anormalidad.') then 00001 else 00000 end) as Lecturados')))
                    ->where('GENERACIONLECTURA.GeneracionFactura', '=', $request->tcGeneracionFactura)->get();
                break;
            
            case 'MedidorAnormalidad':
                $loGeneracionLectura = GeneracionLectura::on($lnDataBaseAlias)
                ->select('GENERACIONLECTURA.GeneracionFactura',
                        (DB::raw('count(GENERACIONLECTURA.GeneracionFactura) as Pendientes')),
                        (DB::raw('sum(case when (GENERACIONLECTURA.MedidorAnormalidad = '.$request->Anormalidad.') then 00001 else 00000 end) as Lecturados')))
                    ->where('GENERACIONLECTURA.GeneracionFactura', '=', $request->tcGeneracionFactura)->get();
                break;
        }

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
        $laGeneracionLectura['tnGlosa']              = $request->input('tnGlosa');

        $SocioConMedidor = $this->DO_CargarMedidorYConsumoAsignado($request->tnCliente, $request->DataBaseAlias);

        if ($request->tnMedidor == 0 && $SocioConMedidor == 0) {
            $loCategoriaDAL = new CategoriaDAL;
            $loCategoria = $loCategoriaDAL->GetConsumoMinimo($request->tnCategoria, $request->DataBaseAlias);
            $lnConsumo     = $loCategoria[0]->ConsumoMinimo;
            $LecturaActual = $lnConsumo;
            $Consumo       = $lnConsumo;
            $lnMedidorAnormalidad = 0;
            $lnMedidorAnormalidad2 = 0; // TODO : implementacion de la variable para la segunda anormalidad
            $lnTipoConsumo = 1;

            GeneracionLecturaDAL::ActualizarLecturaSinMedidorDAL($request->tnGeneracionFactura, $request->tnCliente, $LecturaActual, $Consumo, $lnMedidorAnormalidad, $request->DataBaseAlias);
            $loGeneracionLecturaBLL->ActualizarLecturaMovilSinMedidor($request->tnGeneracionFactura, $request->tnCliente, $request->tnCategoria,
                        $lnTipoConsumo, /*$lnMedidorAnormalidad*/ $lnMedidorAnormalidad2, $request->DataBaseAlias);
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
