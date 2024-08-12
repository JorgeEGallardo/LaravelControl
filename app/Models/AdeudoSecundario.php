<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdeudoSecundario extends Model
{
    use HasFactory;

    protected $table = 'adeudos_secundarios';
    protected $primaryKey = 'id_alumno';
    public $timestamps = false;

    protected $fillable = [
        'id_alumno',
        'concepto',
        'periodo',
        'monto',
        'descuento',
        'corte'
    ];
}
