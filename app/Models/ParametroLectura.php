<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametroLectura extends Model
{
    use HasFactory;

    protected $table        = 'PARAMETROLECTURA';
    protected $primaryKey   = 'ParametroLectura';
    protected $fillable     = ['GenerarFacturaCelularServidor', 'MesesDePromedio', 'MesesNuevo', 'DiasDeInstalacion', 'EjemploFinDeCiclo', 'EjemploVolcado', 
                            'EjemploVolcado2', 'FinMedidorInformacion', 'ConsumoMaximo', 'AnormalidadPendiente', 'AnormalidadEstimado', 'AnormalidadIrreal', 
                            'AnormalidadNuevo', 'EsperaParaGeneracion', 'TipoAviso', 'DeudaAcumuladoConsumoActual', 'ParametroLecturaEstadoConHistorico', 'ImprimirLecturador'];
    public $timestamps      = false;
}
