<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Models\Empresa;
use App\Models\Contrato;
use App\Models\Usuario;

class AuthController extends Controller
{
    //------------------------------------------------------- INICIO DEL LOGIN -----------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------

    public function username() {
        return 'Login';
    }

    public function __construnct(){
        $this->middleware('jwt.verify', ['except' => ['authenticate']]);
    }

    public function authenticate(Request $request){
        $this->validate($request,[
            'Login'    => 'required',
            'password' => 'required',
        ]);

        $credential = $request->only('Login', 'password');

        try{
            if(!$token = JWTAuth::attempt($credential)){
                return response(null, 401);
            }
            else{
                $Login     = JWTAuth::user()->Login;
                $Password  = JWTAuth::user()->Password;
                $verificar = $this->verificar($request->password, $Password);

                if ((JWTAuth::user()->Estado == 1 || JWTAuth::user()->Estado == 5) && $verificar != false) {

                    $data = JWTAuth::user(); // utilizar HELPER para Laravel
                    // $data['Contrato']      = $verificar[0]->Contrato;
                    // $data['FechaContrato'] = $verificar[0]->FechaContrato;
                    // $data['FechaLimite']   = $verificar[0]->FechaLimite;
                    // $data['Empresa']       = $verificar[0]->Empresa;
                    $data['EmpresaNombre'] = $verificar[0]->EmpresaNombre;
                    $data['DataBaseAlias'] = $verificar[0]->DataBaseAlias;
                    $data['Plomero']       = $verificar[0]->Plomero;

                    return \response()->json([
                        'status'    => true,
                        'token'     => \compact('token'),
                        'data'      => $data,
                        'message'   => 'Credenciales válidos'
                    ]);
                }else{
                    return response(null, 403);
                }
                
            }
        }catch(\Tymon\JWTAuth\Exceptions\JWTException $e){
            return $e->getMessage();
        }
    }

    public function verificar($password_front, $password){

        if (password_verify($password_front, $password)) {
             $verificar = Usuario::on('mysql')
                ->select('CONTRATO.Contrato', 'CONTRATO.FechaContrato', 'CONTRATO.FechaLimite', 
                        'EMPRESA.Empresa', 'EMPRESA.EmpresaNombre', 'EMPRESA.DataBaseAlias', 'CONTRATO.Plomero', 'USUARIO.Estado')
                ->join('CONTRATO', 'USUARIO.Usuario', '=', 'CONTRATO.Usuario')
                ->join('EMPRESA', 'CONTRATO.Empresa', '=', 'EMPRESA.Empresa')
                ->where('USUARIO.Usuario', '=', JWTAuth::user()->Usuario)
                ->where('CONTRATO.Estado', '=', 1)
                ->where(function($query){
                    $query->where('USUARIO.Estado', '=', '1')
                    ->orWhere('USUARIO.Estado', '=', '5');
                })
                ->where('CONTRATO.FechaLimite', '>', date("Y-m-d"))
                ->get();

            if (count($verificar) == 0) {
                $verificar = false;
            }
        }else{
            $verificar = false; //'Datos incorrectos, verifique sus datos o contacte al Administrador';
        }
        return $verificar;
    }

    public function logout(){
        Auth::logout();
        return \response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ]);
    }
    
    //---------------------------------------------------- FIN DEL LOGIN -----------------------------------------------------------
    //------------------------------------------------------------------------------------------------------------------------------

}
