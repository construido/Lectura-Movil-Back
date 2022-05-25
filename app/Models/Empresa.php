<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table        = 'EMPRESA';
    protected $primaryKey   = 'Empresa';
    protected $fillable     = ['EmpresaNombre', 'RazonSocial', 'Nombre', 'Nit', 'Rubro', 'DataBase', 'DataBaseAlias',
                                'IPDataBase', 'PortDataBase', 'UserDataBase', 'PassDataBase', 'ServerIP',
                                'ServerIPVPN', 'ServerIPProxy', 'ProtocoloHTTP', 'ServicioWeb', 'EmpresaTelefono',
                                'EmpresaFax', 'EmpresaDireccion', 'Localidad', 'Usr', 'UsrHora', 'UsrFecha'];
                               
    public $timestamps      = false;
}
