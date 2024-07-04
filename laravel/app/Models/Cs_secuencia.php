<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cs_secuencia extends Model
{
    use HasFactory;

    protected $connection = 'oracle';
    protected $table = 'cs_secuencia';
    public $timestamps = false;
}
