<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject //extends Model
{
    use HasFactory, Notifiable;

    protected $table        = 'USUARIO';
    protected $primaryKey   = 'Usuario';
    protected $fillable     = ['Nombre', 'Apellidos', 'Login', 'Password', 'Correo', 
                                'Estado', 'FechaCreacion', 'Usr', 'UsrHora', 'UsrFecha'];
    public $timestamps      = false;

    public function getJWTIdentifier(){
        return $this->getKey();
    }

    public function getJWTCustomClaims(){
        return [];
    }

    public function getAuthPassword()
    {
        return $this->Password;
    }
}
