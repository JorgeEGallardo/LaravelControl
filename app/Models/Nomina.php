<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nomina extends Model
{
    use HasFactory;

    // Especifica el nombre de la tabla si no sigue la convención plural
    protected $table = 'nominas';

    // Lista de columnas que pueden ser asignadas masivamente
    protected $fillable = [
        'fecha',
        'id_autor',
        'clases',
        'inscripciones',
        'recargos',
        'total',
        'comisiones',
        'total_neto',
        'porcentaje_comision'
    ];

    // Si la clave primaria no es 'id', especifica el nombre de la clave primaria
    protected $primaryKey = 'id_nomina';
}
