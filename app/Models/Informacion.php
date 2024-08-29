<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Informacion extends Model
{
    // Definir el nombre de la tabla
    protected $table = 'informacion';

    // Definir los campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'nombre_corto',
        'version',
        'precio_inscripcion',
        'precio_visita',
        'precio_recargo',
        'dia_limite',
        'comision',
        'temporada',
    ];

    // Desactivar las marcas de tiempo automáticas si la tabla no tiene `created_at` y `updated_at`
    public $timestamps = false;
}
