<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedidorAnormalidad;
use App\Modelos\mPaqueteTodoFacil;
use App\DAL\ParametroLecturaDAL;

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

class MedidorAnormalidadController extends Controller
{
    /**
     * Metodo que devuelve una lista de la tabla MEDIDORANORMALIDAD
     * @method      llenarSelectAnormalidad()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       1-09-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      lista MEDIDORANORMALIDAD
     */
    public function llenarSelectAnormalidad(Request $request){
        $result = new ParametroLecturaDAL();
        $result = $result->GetAlldt(1, $request->tcDataBaseAlias);
        $AN = $result[0]->AnormalidadNuevo;
        $AC = $result[0]->AnormalidadCambioMedidor;
        $AR = $result[0]->AnormalidadRegularizacionBajaTemporal;

        $lcDataBaseAlias = $request->tcDataBaseAlias;
        $lcOrden = $request->tcOrden;
        $lcTipo  = $request->tcTipo;
        $lcDato  = $request->tcDato;

        $laMedidorAnormalidad;

        switch ($lcOrden) {
            case 'top10':
                $columna = 'Frecuencia';
                $direccion = 'DESC';
                break;

            case 'ASC':
                $columna = 'MEDIDORANORMALIDAD.NombreAnormalidad';
                $direccion = 'ASC';
                break;

            case 'DESC':
                $columna = 'MEDIDORANORMALIDAD.NombreAnormalidad';
                $direccion = 'DESC';
                break;
        }        

        switch ($lcTipo) {
            case 'Regla':
                $laMedidorAnormalidad = MedidorAnormalidad::on($lcDataBaseAlias)
                ->select('MEDIDORANORMALIDAD.*', 'TIPOCONSUMO.Nombre', 'REGLALECTURACION.Nombre as NombreL')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->leftJoin('REGLALECTURACION', 'MEDIDORANORMALIDAD.Regla', '=', 'REGLALECTURACION.ReglaLecturacion')
                ->where('REGLALECTURACION.Nombre', 'like', '%'.$lcDato.'%')
                ->whereRaw("MEDIDORANORMALIDAD.MedidorAnormalidad NOT IN ($AN, $AC, $AR)")
                ->orderBy($columna, $direccion)
                ->paginate(6);
                break;

            case 'Nombre':
                $laMedidorAnormalidad = MedidorAnormalidad::on($lcDataBaseAlias)
                ->select('MEDIDORANORMALIDAD.*', 'TIPOCONSUMO.Nombre', 'REGLALECTURACION.Nombre as NombreL')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->leftJoin('REGLALECTURACION', 'MEDIDORANORMALIDAD.Regla', '=', 'REGLALECTURACION.ReglaLecturacion')
                ->where('MEDIDORANORMALIDAD.NombreAnormalidad', 'like', '%'.$lcDato.'%')
                ->whereRaw("MEDIDORANORMALIDAD.MedidorAnormalidad NOT IN ($AN, $AC, $AR)")
                ->orderBy($columna, $direccion)
                ->paginate(6);
                break;

            case 'TipoConsumo':
                $laMedidorAnormalidad = MedidorAnormalidad::on($lcDataBaseAlias)
                ->select('MEDIDORANORMALIDAD.*', 'TIPOCONSUMO.Nombre', 'REGLALECTURACION.Nombre as NombreL')
                ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                ->leftJoin('REGLALECTURACION', 'MEDIDORANORMALIDAD.Regla', '=', 'REGLALECTURACION.ReglaLecturacion')
                ->where('TIPOCONSUMO.Nombre', 'like', '%'.$lcDato.'%')
                ->whereRaw("MEDIDORANORMALIDAD.MedidorAnormalidad NOT IN ($AN, $AC, $AR)")
                ->orderBy($columna, $direccion)
                ->paginate(6);
                break;
        }

        $laMedidorAnormalidad = [
            'pagination' => [
                'total' => $laMedidorAnormalidad->total(),
                "current_page" => $laMedidorAnormalidad->currentPage(),
                "per_page" => $laMedidorAnormalidad->perPage(),
                "last_page" => $laMedidorAnormalidad->lastPage(),
                "from" => $laMedidorAnormalidad->firstItem(),
                "to" => $laMedidorAnormalidad->lastItem(),
            ],
            'laMedidorAnormalidad' => $laMedidorAnormalidad
        ];

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $laMedidorAnormalidad;
        return response()->json($loPaquete);
    }

    public function AnormalidadesDeMedidor(Request $request){
        $lnDataBaseAlias = $request->DataBaseAlias;
        $lnTipo = $request->Tipo;
        $lnDato = $request->Dato;

        switch ($lnTipo) {
            case 'Nombre':
                $laMedidorAnormalidad = MedidorAnormalidad::on($lnDataBaseAlias)
                    ->select('MEDIDORANORMALIDAD.MedidorAnormalidad', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.Inspeccion',
                                'MEDIDORANORMALIDAD.Informativo', 'TIPOCONSUMO.Nombre as TipoConsumo', 'REGLALECTURACION.Nombre as Regla')
                    ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                    ->leftJoin('REGLALECTURACION', 'MEDIDORANORMALIDAD.Regla', '=', 'REGLALECTURACION.ReglaLecturacion')
                    ->where('MEDIDORANORMALIDAD.NombreAnormalidad', 'like', '%'.$lnDato.'%')
                    ->orderBy('MEDIDORANORMALIDAD.MedidorAnormalidad','ASC')
                    ->get();
                break;

            case 'Codigo':
                $laMedidorAnormalidad = MedidorAnormalidad::on($lnDataBaseAlias)
                    ->select('MEDIDORANORMALIDAD.MedidorAnormalidad', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.Inspeccion',
                                'MEDIDORANORMALIDAD.Informativo', 'TIPOCONSUMO.Nombre as TipoConsumo', 'REGLALECTURACION.Nombre as Regla')
                    ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                    ->leftJoin('REGLALECTURACION', 'MEDIDORANORMALIDAD.Regla', '=', 'REGLALECTURACION.ReglaLecturacion')
                    ->where('MEDIDORANORMALIDAD.MedidorAnormalidad', '=', $lnDato)
                    ->orderBy('MEDIDORANORMALIDAD.MedidorAnormalidad','ASC')
                    ->get();
                break;

            case 'TipoConsumo':
                $laMedidorAnormalidad = MedidorAnormalidad::on($lnDataBaseAlias)
                    ->select('MEDIDORANORMALIDAD.MedidorAnormalidad', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'MEDIDORANORMALIDAD.Inspeccion',
                                'MEDIDORANORMALIDAD.Informativo', 'TIPOCONSUMO.Nombre as TipoConsumo', 'REGLALECTURACION.Nombre as Regla')
                    ->leftJoin('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
                    ->leftJoin('REGLALECTURACION', 'MEDIDORANORMALIDAD.Regla', '=', 'REGLALECTURACION.ReglaLecturacion')
                    ->where('TIPOCONSUMO.Nombre', 'like', '%'.$lnDato.'%')
                    ->orderBy('MEDIDORANORMALIDAD.MedidorAnormalidad','ASC')
                    ->get();
                break;
        }
        
        $loPaquete = new mPaqueteTodoFacil();
        if (count($laMedidorAnormalidad) > 0) {
            $loPaquete->values = $laMedidorAnormalidad;
        }else{
            $loPaquete->error   = 1;
            $loPaquete->status  = 0;
            $loPaquete->message = 'Error';
            $loPaquete->values  = 0;
        }

        return response()->json($loPaquete);
    }

    public function Categorizar(Request $request){
        $laMedidorAnormalidad = MedidorAnormalidad::on($request->DataBaseAlias)
            ->select('PARAMETROLECTURA.AnormalidadVerificarCategoria', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'TIPOCONSUMO.Nombre')
            ->join('PARAMETROLECTURA', 'MEDIDORANORMALIDAD.MedidorAnormalidad', '=', 'PARAMETROLECTURA.AnormalidadVerificarCategoria')
            ->join('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
            ->get();

        return response()->json($laMedidorAnormalidad);
    }

    public function LecturaPendiente(Request $request){
        $laMedidorAnormalidad = MedidorAnormalidad::on($request->DataBaseAlias)
            ->select('PARAMETROLECTURA.AnormalidadPendiente', 'MEDIDORANORMALIDAD.NombreAnormalidad', 'TIPOCONSUMO.Nombre')
            ->join('PARAMETROLECTURA', 'MEDIDORANORMALIDAD.MedidorAnormalidad', '=', 'PARAMETROLECTURA.AnormalidadPendiente')
            ->join('TIPOCONSUMO', 'MEDIDORANORMALIDAD.TipoConsumo', '=', 'TIPOCONSUMO.TipoConsumo')
            ->get();

        return response()->json($laMedidorAnormalidad);
    }

    public function parametroLectura(Request $request){ // TODO: implementado el 30/07/2023
        $result = new ParametroLecturaDAL();
        $result = $result->GetAlldt(1, $request->DataBaseAlias);
        return $result;
    }
}