<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cm_cuponpago extends Model
{
    use HasFactory;

    protected $conecction = 'oracle';
    protected $table = 'cm_cuponpago';
    protected $primatyKey = 'IDCUPONPAGO';
    public $timestamps = false;
    protected $fillable = ['idcuponpago','valor','fechageneracion','ditipocuponpago','idestadocupon','idcuentacobro','idpais','iddepartamento','idmunicipio,','idusuario','maquina','idservicio'];
}
