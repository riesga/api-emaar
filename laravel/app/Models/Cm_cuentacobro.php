<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cm_cuentacobro extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'cm_cuentacobro';
    protected $primaryKey = 'IDCUENTACOBRO';

    public function suscriptorcuentacobro()
    {
        return $this->belongsTo(Cm_suscriptor::class, 'IDCUENTACOBRO', 'IDCUENTACOBRO');
    }
}
