<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametrosGenerales extends Model
{
    use HasFactory;

    protected $table        = 'PARAMETROGENERALES';
    protected $primaryKey   = 'Empresa';
    protected $fillable     = ['Iva', 'It', 'CorteMes', 'ReconMes', 'CorteDias', 'FactPlazo', 'TipoMedida', 'Alcantar', 'Pg_Socest',
                            'G_Conexion', 'ComoLeyFac', 'G_Corte', 'MoraDias', 'Consumo', 'Aportacion', 'Instalacion', 'Medidor', 'CorteAuto',
                            'Reconecta', 'Sirese', 'Transferen', 'Escritorio', 'MantValor', 'Multa', 'Interes', 'PorcSirese', 'PorcIntere',
                            'Descuento', 'PorcDescue', 'AvisoFact', 'Pg_DirCont', 'Pg_Integra', 'CredFiscal', 'Ctait', 'CtaitXpag', 'DebFiscal',
                            'Racumulado', 'Ley1886', 'Ley1886Cub', 'Ley1886Por', 'GenCortado', 'TelSirese', 'TelOdeco', 'TamAviso', 'TamFactura',
                            'T_Facturar', 'TipoCorte', 'F_Version', 'Path', 'NroHabCon', 'Copias', 'GrabarSec', 'Servidor', 'Pg_Exporta', 'TamCab', 
                            'TamCuerpo1', 'TamDetalle', 'TamCuerpo2', 'TamPie', 'CtaAguaGea', 'CtaAlcaGea', 'CtaAguaNme', 'ModLecant', 'LimiteAnu', 'CtaCenta'];
    public $timestamps      = false;
}
