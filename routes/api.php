<?php

use App\Http\Controllers\MedidorAnormalidadController;
use App\Http\Controllers\GeneracionFacturaController;
use App\Http\Controllers\GeneracionLecturaController;
use App\Http\Controllers\MarcaMedidorController;
use App\Http\Controllers\LecturaMovilController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AuthController;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/login', [AuthController::class, 'authenticate']);

// Route::group(['prefix' => 'admin', 'middleware' => ['jwt.verify', 'admin.verify']], function(){
Route::group(['prefix' => 'admin', 'middleware' => ['jwt.verify']], function(){
    Route::get('/logout', [AuthController::class, 'logout']);

    // RUTAS DE LA TABLA USUARIO DE LA BASE DE DATOS LECTURA MOVIL EMPRESA
    Route::put('/updateContraseña', [UsuarioController::class,'updateContraseña']);
    Route::put('/updateUsuario', [UsuarioController::class,'updateUsuario']);
    Route::put('/updateEstado', [UsuarioController::class,'updateEstado']);
    Route::post('/getUsuarios', [UsuarioController::class,'getUsuarios']);
    Route::post('/saveUsuario', [UsuarioController::class,'saveUsuario']);
    Route::post('/showById', [UsuarioController::class,'showById']);

    // RUTAS DE LA TABLA GENERACIONLECTURA DE LA BASE DE DATOS LECTURA MOVIL
    Route::post('/lecturasPendientesAnormalidades', [GeneracionLecturaController::class, 'lecturasPendientesAnormalidades']);
    Route::post('/lecturasPendientesLecturados', [GeneracionLecturaController::class, 'lecturasPendientesLecturados']);
    Route::post('/verLecturaIdNextProcesada', [GeneracionLecturaController::class, 'verLecturaIdNextProcesada']);
    Route::post('/verLecturaIdNextPendiente', [GeneracionLecturaController::class, 'verLecturaIdNextPendiente']);
    Route::post('/verLecturaIdProcesada', [GeneracionLecturaController::class, 'verLecturaIdProcesada']);
    Route::post('/verLecturaIdPendiente', [GeneracionLecturaController::class, 'verLecturaIdPendiente']);
    Route::post('/DO_LecturarNormal', [GeneracionLecturaController::class, 'DO_LecturarNormal']);
    Route::post('/verLecturaIdNext', [GeneracionLecturaController::class, 'verLecturaIdNext']);
    Route::post('/listarPendientes', [GeneracionLecturaController::class, 'listarPendientes']);
    Route::post('/listarProcesadas', [GeneracionLecturaController::class, 'listarProcesadas']);
    Route::post('/listarLecturas', [GeneracionLecturaController::class, 'listarLecturas']);
    Route::post('/buscarCliente', [GeneracionLecturaController::class, 'buscarCliente']);
    Route::post('/verLecturaId', [GeneracionLecturaController::class, 'verLecturaId']);
    
    // RUTAS DE LA TABLA GENERACIONFACTURA DE LA BASE DE DATOS LECTURA MOVIL
    Route::post('/listarPlanillaDeLecturasPendientes', [GeneracionFacturaController::class, 'listarPlanillaDeLecturasPendientes']);
    Route::post('/listarPlanillaDeLecturasProcesadas', [GeneracionFacturaController::class, 'listarPlanillaDeLecturasProcesadas']);

    // RUTAS DE LA TABLA MEDIDORANORMALIDAD DE LA BASE DE DATOS LECTURA MOVIL
    Route::post('/llenarSelectAnormalidad', [MedidorAnormalidadController::class, 'llenarSelectAnormalidad']);
    Route::post('/AnormalidadesDeMedidor', [MedidorAnormalidadController::class, 'AnormalidadesDeMedidor']);
    Route::post('/Categorizar', [MedidorAnormalidadController::class, 'Categorizar']);

    // RUTAS PARA EL ENVIO DE DATOS A LA DB´s DE LAS EMPRESAS
    Route::post('/WMSincronizacionBDListDemo', [LecturaMovilController::class,'WMSincronizacionBDListDemo']);
    Route::post('/WMGet_Lecturas_Pendientes', [LecturaMovilController::class,'WMGet_Lecturas_Pendientes']);
    Route::post('/verificarConexionRestNET', [LecturaMovilController::class,'verificarConexionRestNET']);
    Route::post('/WMSincronizarCaS', [LecturaMovilController::class,'WMSincronizarCaS']);
    Route::post('/WMAutenticar', [LecturaMovilController::class,'WMAutenticar']);

    // RUTAS PARA LA IMPRESIÓN DE LA FACTURA
    Route::post('/CrearFactura', [FacturaController::class,'CrearFactura']);

    // RUTA PARA LA TABLA MARCAMEDIDOR
    Route::post('/llenarSelectMarca', [MarcaMedidorController::class,'llenarSelectMarca']);
});

//----------------------------------------------------------------------//
//----------------------------------------------------------------------//
// php artisan serve --host=ip
// ip de la maquina = 192.168.100.84
// ejecutar para que funcione en celular
// tymon/config/config.php TODO - para cambiar el tiempo que dure activo el token de session

//Route::post('/WMSincronizarCaS', [LecturaMovilController::class,'WMSincronizarCaS']);