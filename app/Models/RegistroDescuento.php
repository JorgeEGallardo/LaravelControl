<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroDescuento extends Model
{
    protected $table = 'registro_descuentos';

    protected $primaryKey = null; // Si la tabla tiene una clave primaria compuesta

    public $incrementing = false; // Si la clave primaria no es autoincremental

    protected $fillable = [
        'id_alumno',
        'id_programa',
        'periodo',
        'precio_orig',
        'descuento',
        'precio_final',
        'tipo',
        'observaciones',
        'fecha',
    ];

    // Si no tienes las columnas de created_at y updated_at
    public $timestamps = false;
}
