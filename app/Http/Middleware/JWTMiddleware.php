<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
use Closure;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try{
            $user = JWTAuth::parseToken()->authenticate();
        }catch(Exception $e){
            if($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return \response()->json([
                    'status' => 403,
                    'message' => 'Invalid Token'
                // ], 403);
                ]);
            }
            else{
                if($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                    return \response()->json([
                        'status' => 403,
                        'message' => 'Token expired'
                    // ], 403);
                    ]);
                }
                else{
                    return \response()->json([
                        'status' => 403,
                        'message' => 'Token is required'
                    // ], 403);
                    ]);
                }
            }
        }
        return $next($request->merge(['USUARIO' => $user]));
    }
}
