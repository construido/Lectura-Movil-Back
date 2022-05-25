<?php

namespace App\DAL;

use App\Models\Usuario;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuarioDAL
{
    // ESTADOS PARA LOS USUARIOS
    // 1 = Activo 
    // 2 = Pendiente Activar
    // 3 = Pasivo
    // 4 = Baja Definitiva
    // 5 = Usuario Prueba

    /**
     * Metodo que devuelve un objeto con el USUARIO registrado
     * @method      editarUsuario()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      registro USUARIO
     */
    public static function registrarUsuario($datos){

        try {
            DB::beginTransaction();

            $loUsuario = Usuario::on('mysql')->create([
                'Nombre'        => $datos['tcNombre'],
                'Apellidos'     => $datos['tcApellidos'],
                'Login'         => $datos['tcLogin'],
                'Correo'        => $datos['tcCorreo'],
                'Password'      => Hash::make($datos['tcPassword']),
                'FechaCreacion' => date("Y-m-d"),
                'Estado'        => 2,
                'Usr'           => 1,
                'UsrHora'       => date("H:i:s"), //$ldFecha->format('H:m:s'),
                'UsrFecha'      => date("Y-m-d"), //$ldFecha->format('Y-m-d'),
            ]);

            DB::commit();
            return $loUsuario;

        } catch (Exception $e) {
            DB::rollback();
            return $e->getMessage();
        }
    }

    /**
     * Metodo que devuelve un objeto con el USUARIO actualizado
     * @method      editarUsuario()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      actualización USUARIO
     */
    public static function editarUsuario($datos){
        try {
            DB::beginTransaction();

            $loUsuario = Usuario::on('mysql')->findOrFail($datos['tnUsuario']);
            $loUsuario->Nombre    = $datos['tcNombre'];
            $loUsuario->Apellidos = $datos['tcApellidos'];
            $loUsuario->Login     = $datos['tcLogin'];
            $loUsuario->Correo    = $datos['tcCorreo'];
            $loUsuario->save();

            DB::commit();
            return $loUsuario;

        } catch (Exception $e) {
            DB::rollback();
            return $e->getMessage();
        }
    }

    /**
     * Metodo que devuelve un objeto con el Estado del USUARIO actualizado
     * @method      cambiarEstado()
     * @author      Ing. Eligio Eloy Vaca FLores
     * @fecha       12-08-2021
     * @param       \Illuminate\Http\Request  $request
     * @return      actualización Estado USUARIO
     */
    public static function cambiarEstado($datos){
        try {
            DB::beginTransaction();

            $loUsuario = Usuario::on('mysql')->findOrFail($datos['tnUsuario']);
            $loUsuario->Estado    = $datos['tnEstado'];
            $loUsuario->save();

            DB::commit();
            return $loUsuario;

        } catch (Exception $e) {
            DB::rollback();
            return $e->getMessage();
        }
    }

    public static function cambiarContraseña($datos){
        try {
            DB::beginTransaction();

            $loUsuario = Usuario::on('mysql')->findOrFail($datos['tnUsuario']);
            $loUsuario->Password = Hash::make($datos['tcPassword_nuevo']);
            $loUsuario->save();

            DB::commit();
            return $loUsuario;

        } catch (Exception $e) {
            DB::rollback();
            return $e->getMessage();
        }
    }
}