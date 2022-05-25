<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table        = 'CLIENTE';
    protected $primaryKey   = 'Cliente';
    protected $fillable     = ['CodigoUbicacion', 'Nombre', 'Consumo', 'ConsumoPromedio', 'Categoria', 'ActividadCliente', 
                               'Medidor', 'Direccion', 'Uv', 'Manzana', 'Lote', 'Estado', 'Persona', 'Factura', 'Cloaca', 
                               'Seguro', 'FechaNacimiento', 'EsPErsonaNatural', 'FechaFactura', 'Corte'];
                               
    public $timestamps      = false;
}
