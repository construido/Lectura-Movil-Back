<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeneracionFactura;
use App\Modelos\mPaqueteTodoFacil;
use App\Models\GeneracionLectura;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

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
use App\DAL\FacturaDAL;

class GeneracionFacturaController extends Controller
{
    /**
     * Metodo que devuelve una lista de las Planillas de Lecturación
     * @method      listarFacturas()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       31-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      lista GENERACIONFACTURA
     */
    // public function listarPlanilla(Request $request){
    //     $laFactura = new FacturaDAL;
    //     $laGeneracionFactura = $laFactura->GetDatosFacturaSocio(12831, 207, 'mysql_LMCosepW');

    //     return  $laGeneracionFactura;
    // }

    public function listarPlanillaDeLecturasPendientes(Request $request){
        $lnDataBaseAlias = $request->DataBaseAlias;
        $lnPlomero       = $request->Plomero;

        if (JWTAuth::user()->Estado == 1) {
            $laGeneracionFactura = GeneracionFactura::on($lnDataBaseAlias)
            ->select('GENERACIONFACTURA.GeneracionFactura', 'GENERACIONFACTURA.Zona', 'GENERACIONFACTURA.Ruta', 'GENERACIONFACTURA.Cobro',
                (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionLectura,"%d-%m-%Y") as FechaGeneracionLectura')),
                (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionFactura,"%d-%m-%Y") as FechaGeneracionFactura')))
            ->join('GENERACIONLECTURA', 'GENERACIONFACTURA.GeneracionFactura', '=', 'GENERACIONLECTURA.GeneracionFactura')
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->where('GENERACIONLECTURA.LecturaActual', '=', 0)
            ->where('GENERACIONLECTURA.Consumo', '=', 0)
            ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', 0)
            ->where('GENERACIONFACTURA.Plomero', '=', $lnPlomero)
            ->groupBy('GENERACIONFACTURA.GeneracionFactura')
            ->orderBy('GENERACIONFACTURA.GeneracionFactura', 'ASC')
            ->paginate(10);
        }else{
            if (JWTAuth::user()->Estado == 5) {
                $laGeneracionFactura = GeneracionFactura::on($lnDataBaseAlias)
                ->select('GENERACIONFACTURA.GeneracionFactura', 'GENERACIONFACTURA.Zona', 'GENERACIONFACTURA.Ruta', 'GENERACIONFACTURA.Cobro',
                    (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionLectura,"%d-%m-%Y") as FechaGeneracionLectura')),
                    (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionFactura,"%d-%m-%Y") as FechaGeneracionFactura')))
                ->join('GENERACIONLECTURA', 'GENERACIONFACTURA.GeneracionFactura', '=', 'GENERACIONLECTURA.GeneracionFactura')
                ->where('GENERACIONFACTURA.Generado', '=', 0)
                ->where('GENERACIONLECTURA.LecturaActual', '=', 0)
                ->where('GENERACIONLECTURA.Consumo', '=', 0)
                ->where('GENERACIONLECTURA.MedidorAnormalidad', '=', 0)
                ->groupBy('GENERACIONFACTURA.GeneracionFactura')
                ->orderBy('GENERACIONFACTURA.GeneracionFactura', 'ASC')
                ->paginate(10);
            }
        }

        for ($i=0; $i < count($laGeneracionFactura); $i++) { 
            $LPL = $this->lecturasPendientesLecturados($laGeneracionFactura[$i]->GeneracionFactura, $lnDataBaseAlias);
            $laGeneracionFactura[$i]['Lecturados'] = $LPL[0]->Lecturados;
            $laGeneracionFactura[$i]['Pendientes'] = $LPL[0]->Pendientes - $LPL[0]->Lecturados;
        }

        $laGeneracionFactura = [
            'pagination' => [
                'total' => $laGeneracionFactura->total(),
                "current_page" => $laGeneracionFactura->currentPage(),
                "per_page" => $laGeneracionFactura->perPage(),
                "last_page" => $laGeneracionFactura->lastPage(),
                "from" => $laGeneracionFactura->firstItem(),
                "to" => $laGeneracionFactura->lastItem(),
            ],
            'laGeneracionFactura' => $laGeneracionFactura
        ];

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $laGeneracionFactura;
        return response()->json($loPaquete);
    }

    public function listarPlanillaDeLecturasProcesadas(Request $request){
        $lnDataBaseAlias = $request->DataBaseAlias;
        $lnPlomero       = $request->Plomero;

        if (JWTAuth::user()->Estado == 1) {
            $laGeneracionFactura = GeneracionFactura::on($lnDataBaseAlias)
            ->select('GENERACIONFACTURA.GeneracionFactura', 'GENERACIONFACTURA.Zona', 'GENERACIONFACTURA.Ruta', 'GENERACIONFACTURA.Cobro',
                (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionLectura,"%d-%m-%Y") as FechaGeneracionLectura')),
                (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionFactura,"%d-%m-%Y") as FechaGeneracionFactura')))
            ->join('GENERACIONLECTURA', 'GENERACIONFACTURA.GeneracionFactura', '=', 'GENERACIONLECTURA.GeneracionFactura')
            ->where('GENERACIONFACTURA.Plomero', '=', $lnPlomero)
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->where(function($query){
                $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
                    ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
                    ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
            })
            ->groupBy('GENERACIONFACTURA.GeneracionFactura')
            ->paginate(10);
        }else{
            if (JWTAuth::user()->Estado == 5) {
                $laGeneracionFactura = GeneracionFactura::on($lnDataBaseAlias)
                ->select('GENERACIONFACTURA.GeneracionFactura', 'GENERACIONFACTURA.Zona', 'GENERACIONFACTURA.Ruta', 'GENERACIONFACTURA.Cobro',
                    (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionLectura,"%d-%m-%Y") as FechaGeneracionLectura')),
                    (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionFactura,"%d-%m-%Y") as FechaGeneracionFactura')))
                ->join('GENERACIONLECTURA', 'GENERACIONFACTURA.GeneracionFactura', '=', 'GENERACIONLECTURA.GeneracionFactura')
                ->where('GENERACIONFACTURA.Generado', '=', 0)
                ->where(function($query){
                    $query->where('GENERACIONLECTURA.LecturaActual', '>', '0')
                        ->orWhere('GENERACIONLECTURA.Consumo', '>', '0')
                        ->orWhere('GENERACIONLECTURA.MedidorAnormalidad', '>', '0');
                })
                ->groupBy('GENERACIONFACTURA.GeneracionFactura')
                ->paginate(10);
            }
        }

        for ($i=0; $i < count($laGeneracionFactura); $i++) { 
            $LPL = $this->lecturasPendientesLecturados($laGeneracionFactura[$i]->GeneracionFactura, $lnDataBaseAlias);
            $laGeneracionFactura[$i]['Lecturados'] = $LPL[0]->Lecturados;
            $laGeneracionFactura[$i]['Pendientes'] = $LPL[0]->Pendientes - $LPL[0]->Lecturados;
        }

        $laGeneracionFactura = [
            'pagination' => [
                'total' => $laGeneracionFactura->total(),
                "current_page" => $laGeneracionFactura->currentPage(),
                "per_page" => $laGeneracionFactura->perPage(),
                "last_page" => $laGeneracionFactura->lastPage(),
                "from" => $laGeneracionFactura->firstItem(),
                "to" => $laGeneracionFactura->lastItem(),
            ],
            'laGeneracionFactura' => $laGeneracionFactura
        ];

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $laGeneracionFactura;
        return response()->json($loPaquete);
    }

    public function AnormalidadesEspeciales($DataBaseAlias){
        $loParametroLectura = ParametroLectura::on($DataBaseAlias)
            ->select('AnormalidadNuevo', 'AnormalidadCambioMedidor', 'AnormalidadRegularizacionBajaTemporal')
            ->get();
        return $loParametroLectura;
    }
    
    public function lecturasPendientesLecturados($tcGeneracionFactura, $DataBaseAlias){

        $loGeneracionLectura = GeneracionLectura::on($DataBaseAlias)
            
        ->select((DB::raw('count(GENERACIONLECTURA.GeneracionFactura) as Pendientes')),
        (DB::raw('sum(case when (GENERACIONLECTURA.LecturaActual = 0 and GENERACIONLECTURA.Consumo = 0 and GENERACIONLECTURA.MedidorAnormalidad = 0) then 00000 else 00001 end) as Lecturados')))
        ->where('GENERACIONLECTURA.GeneracionFactura', '=', $tcGeneracionFactura)->get();

        return $loGeneracionLectura;
    }

    public function listarTodasLasPlanilla(Request $request){
        $lnDataBaseAlias = $request->DataBaseAlias;
        $lnPlomero       = $request->Plomero;

        if (JWTAuth::user()->Estado == 1) {
            $laGeneracionFactura = GeneracionFactura::on($lnDataBaseAlias)
            ->select('GENERACIONFACTURA.GeneracionFactura', 'GENERACIONFACTURA.Zona', 'GENERACIONFACTURA.Ruta', 'GENERACIONFACTURA.Cobro',
                (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionLectura,"%d-%m-%Y") as FechaGeneracionLectura')),
                (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionFactura,"%d-%m-%Y") as FechaGeneracionFactura')))
            ->join('GENERACIONLECTURA', 'GENERACIONFACTURA.GeneracionFactura', '=', 'GENERACIONLECTURA.GeneracionFactura')
            ->where('GENERACIONFACTURA.Generado', '=', 0)
            ->where('GENERACIONFACTURA.Plomero', '=', $lnPlomero)
            ->groupBy('GENERACIONFACTURA.GeneracionFactura')
            ->orderBy('GENERACIONFACTURA.GeneracionFactura', 'ASC')
            ->paginate(10);
        }else{
            if (JWTAuth::user()->Estado == 5) {
                $laGeneracionFactura = GeneracionFactura::on($lnDataBaseAlias)
                ->select('GENERACIONFACTURA.GeneracionFactura', 'GENERACIONFACTURA.Zona', 'GENERACIONFACTURA.Ruta', 'GENERACIONFACTURA.Cobro',
                    (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionLectura,"%d-%m-%Y") as FechaGeneracionLectura')),
                    (DB::raw('DATE_FORMAT(GENERACIONFACTURA.FechaGeneracionFactura,"%d-%m-%Y") as FechaGeneracionFactura')))
                ->join('GENERACIONLECTURA', 'GENERACIONFACTURA.GeneracionFactura', '=', 'GENERACIONLECTURA.GeneracionFactura')
                ->where('GENERACIONFACTURA.Generado', '=', 0)
                ->groupBy('GENERACIONFACTURA.GeneracionFactura')
                ->orderBy('GENERACIONFACTURA.GeneracionFactura', 'ASC')
                ->paginate(10);
            }
        }

        for ($i=0; $i < count($laGeneracionFactura); $i++) { 
            $LPL = $this->lecturasPendientesLecturados($laGeneracionFactura[$i]->GeneracionFactura, $lnDataBaseAlias);
            $laGeneracionFactura[$i]['Lecturados'] = $LPL[0]->Lecturados;
            $laGeneracionFactura[$i]['Pendientes'] = $LPL[0]->Pendientes - $LPL[0]->Lecturados;
        }

        $laGeneracionFactura = [
            'pagination' => [
                'total' => $laGeneracionFactura->total(),
                "current_page" => $laGeneracionFactura->currentPage(),
                "per_page" => $laGeneracionFactura->perPage(),
                "last_page" => $laGeneracionFactura->lastPage(),
                "from" => $laGeneracionFactura->firstItem(),
                "to" => $laGeneracionFactura->lastItem(),
            ],
            'laGeneracionFactura' => $laGeneracionFactura
        ];

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $laGeneracionFactura;
        return response()->json($loPaquete);
    }
}