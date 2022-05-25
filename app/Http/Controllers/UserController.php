<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use JWTAuth;
use App\Models\User;

// *************************************** NO SE ESTÁ TRABAJANDO CON ESTE CONTROLADOR ***************************************************
// **************************************************************************************************************************************


class UserController extends Controller
{
    public function authenticate(Request $request){
        
        $credential = $request->only('email', 'password');
        $validator = Validator::make($credential, [
            'email'     => 'required',
            'password'  => 'required'
        ]);

        if(!$validator->fails()){

            try{
                if(!$token = JWTAuth::attempt($credential)){
                    return \response()->json([
                        'status'    => false,
                        'message'   => 'Invalid credentials'
                    ]);
                }
            }catch(\Tymon\JWTAuth\Exceptions\JWTException $e){
                return \response()->json([
                    'status'    => false,
                    'error'     => $e->getMessage(),
                    'message'   => 'Invalid credentials'
                ]);
            }

            return \response()->json([
                'status'    => true,
                'token'     => \compact('token'),
                'message'   => 'Valid credentials'
            ]);

        }else{
            return \response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function register(Request $request){
        return \response()->json([
            'message' => $request->get('user') //'You can continue'
        ]);
    }

    public function show(Request $request){
        return User::get();
    }
}
