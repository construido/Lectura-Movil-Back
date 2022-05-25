<?php
namespace App\Http\Controllers;

use App\Modelos\mPaqueteTodoFacil;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\DAL\UsuarioDAL;
use Carbon\Carbon;
use Validator;
use Hash;
use DB;
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


class UsuarioController extends Controller
{
        /**
     * Metodo que devuelve un USUARIO según su tnUsuario
     * @method      showById()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      registro USUARIO
     */
    public function obtenerLecturador($Usuario){
        $loUsuario = Usuario::on('mysql')->where('Usuario', '=', $Usuario)->get();

        // $loPaquete = new mPaqueteTodoFacil();
        // $loPaquete->values = $loUsuario;
        // return response()->json($loPaquete);

        return $loUsuario;
    }

    /**
     * Metodo que devuelve un USUARIO o lista de USUARIOs según su tcNombre
     * @method      getUsuarios()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      registro USUARIO
     */
    public function getUsuarios(Request $request){

        $lcNombre = $request->input('tcNombre');
        if ($lcNombre == "") {
            $laUsuario = Usuario::on('mysql')->get();
        }else{
            $laUsuario = Usuario::on('mysql')
            ->where('Nombre', 'like', '%'.$lcNombre.'%')->get();
        }

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $laUsuario;
        return response()->json($loPaquete);
    }

    /**
     * Metodo que devuelve un USUARIO según su tnUsuario
     * @method      showById()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      registro USUARIO
     */
    public function showById(Request $request){
        $loUsuario = Usuario::on('mysql')->where('Usuario', '=', $request->tnUsuario)->get();

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loUsuario;
        return response()->json($loPaquete);
    }

    /**
     * Metodo que devuelve un bool si los datos de actualización de USUARIO son válidos o no
     * @method      validarEditar()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      validar USUARIO
     */
    public function validarEditar(Request $request){
        $this->validate($request, [
            'tnUsuario'   => 'required',
            'tcNombre'    => 'required|max:128',
            'tcApellidos' => 'required|max:256',
            'tcLogin'     => 'required|max:45',
            'tcCorreo'    => 'required|max:100',
        ]);
    }

    /**
     * Metodo que devuelve un bool si los datos de registro de USUARIO son válidos o no
     * @method      validarRegistrar()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      validar USUARIO
     */
    public function validarRegistrar(Request $request){
        $this->validate($request,[
            'tcNombre'    => 'required|max:128',
            'tcApellidos' => 'required|max:256',
            'tcLogin'     => 'required|max:45|unique:USUARIO,Login',
            'tcPassword'  => 'required',
            'tcCorreo'    => 'required|max:100',
            'tcConfirmarPassword' => 'required'
        ]);
    }

    public function validarEstado(Request $request){
        $this->validate($request,[
            'tnUsuario' => 'required',
            'tnEstado'  => 'required',
        ]);
    }

    public function validarContraseña(Request $request){
        $this->validate($request,[
            // 'tnUsuario'           => 'required',
            'Password_actual'   => 'required',
            'Password_nuevo'    => 'required',
            'Password_repetir'  => 'required',
        ]);
    }

    /**
     * Metodo que devuelve un objeto con el USUARIO actualizado
     * @method      updateUsuario()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      actualización USUARIO
     */
    public function updateUsuario(Request $request){

        $this->validarEditar($request);

        $laUsuario['tnUsuario']   = $request->tnUsuario;
        $laUsuario['tcNombre']    = $request->tcNombre;
        $laUsuario['tcApellidos'] = $request->tcApellidos;
        $laUsuario['tcLogin']     = $request->tcLogin;
        $laUsuario['tcCorreo']    = $request->tcCorreo;

        $loUsuario = UsuarioDAL::editarUsuario($laUsuario);

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loUsuario;
        return response()->json($loPaquete);
    }

    /**
     * Metodo que devuelve un objeto con el USUARIO registrado
     * @method      saveUsuario()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      registro USUARIO
     */
    public function saveUsuario(Request $request){

        $this->validarRegistrar($request);

        $laUsuario['tcNombre']    = $request->tcNombre;
        $laUsuario['tcApellidos'] = $request->tcApellidos;
        $laUsuario['tcLogin']     = $request->tcLogin;
        $laUsuario['tcCorreo']    = $request->tcCorreo;
        $laUsuario['tcPassword']  = $request->tcPassword;

        $loUsuario = UsuarioDAL::registrarUsuario($laUsuario);

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loUsuario;
        return response()->json($loPaquete);
    }

    public function updateEstado(Request $request){
        $this->validarEstado($request);

        $laUsuario['tnUsuario'] = $request->tnUsuario;
        $laUsuario['tnEstado']  = $request->tnEstado;

        $loUsuario = UsuarioDAL::cambiarEstado($laUsuario);

        $loPaquete = new mPaqueteTodoFacil();
        $loPaquete->values = $loUsuario;
        return response()->json($loPaquete);
    }

    public function updateContraseña(Request $request){
        $this->validarContraseña($request);
        $loPaquete = new mPaqueteTodoFacil();

        if ($this->verificar(JWTAuth::user()->Password, $request->Password_actual) == true) {
            $laUsuario['tnUsuario']          = JWTAuth::user()->Usuario; //$request->tnUsuario; 
            $laUsuario['tcPassword_actual']  = $request->Password_actual; // 123
            $laUsuario['tcPassword_nuevo']   = $request->Password_nuevo;
            $laUsuario['tcPassword_repetir'] = $request->Password_repetir;

            $loUsuario = UsuarioDAL::cambiarContraseña($laUsuario);

            $loPaquete->values = $loUsuario;
        }else{
            $loPaquete->error   = 1;
            $loPaquete->status  = 404;
            $loPaquete->message = 'Error';
            $loPaquete->values  = 0;
        }
        
        return response()->json($loPaquete);
    }

    public function verificar($tcPassword, $tcPassword_actual){
            
        if (password_verify($tcPassword_actual, $tcPassword)) {
            $verificar = true;
        }else{
            $verificar = false; //'Datos incorrectos, verifique sus datos o contacte al Administrador';
        }

        return $verificar;
    }
}