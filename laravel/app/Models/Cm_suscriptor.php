<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cm_suscriptor extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'cm_suscriptor';
    protected $fillable = ['identificacion','celular','telefono','correoelectronico','idrazon','idestadotecnico'];
    public $timestamps = false;
    protected $primaryKey = 'idsuscriptor';

    const PREFIX = 57811;

    public static function consultaSaldoSuscriptor($id) {

        return Cm_suscriptor::where('idsuscriptor', Cm_suscriptor::PREFIX.$id)
            ->whereNot('idestadotecnico',55)
            ->firstOrFail();
    }


    public static function buscarVigencia($id) {

        return Cm_suscriptor::where('idsuscriptor', $id)
                ->where('sieweb.cm_vigencia.periodoactual', '=', 'S')
                ->join('sieweb.cm_vigencia', 'sieweb.cm_suscriptor.idciclofacturacion', '=', 'sieweb.cm_vigencia.idciclofacturacion')
                ->select('sieweb.cm_suscriptor.idciclofacturacion', 'sieweb.cm_vigencia.ano', 'sieweb.cm_vigencia.mes','sieweb.cm_suscriptor.lecturaactual','sieweb.cm_suscriptor.idobslecturaactual')
                ->toSql();
    }

}
