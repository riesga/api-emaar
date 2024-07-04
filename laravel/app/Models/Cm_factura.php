<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cm_factura extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'cm_factura';
    protected $primaryKey = 'IDCUENTACOBRO';

    public function suscriptorfacturas()
    {
        return $this->belongsTo(Cm_suscriptor::class, 'IDSUSCRIPTOR', 'IDSUSCRIPTOR');
    }
}
