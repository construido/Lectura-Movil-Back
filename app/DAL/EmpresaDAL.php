<?php

namespace App\DAL;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Empresa;
use DB;

class EmpresaDAL
{
    public function obtenerDatosEmpresa(){
        $loEmpresa = Empresa::on('mysql')->select('ServerIP', 'Plomero', 'LoginEmpresa', 'PasswordEmpresa')
            ->join('CONTRATO', 'EMPRESA.Empresa', 'CONTRATO.Empresa')
            ->join('USUARIO', 'CONTRATO.Usuario', 'USUARIO.Usuario')
            ->where('USUARIO.Usuario', '=', JWTAuth::user()->Usuario)
            ->get();

        return $loEmpresa;
    }
}