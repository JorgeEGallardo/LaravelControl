<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clase extends Model
{
    use HasFactory;

    // Especifica el nombre de la tabla si no sigue la convención plural
    protected $table = 'clases';

    // Lista de columnas que pueden ser asignadas masivamente
    protected $fillable = [
        'id_programa', 
        'id_clase', 
        'nombre', 
        'id_maestro', 
        'informacion', 
        'porcentaje', 
        'personal'
    ];

    // Si la clave primaria no es 'id', especifica el nombre de la clave primaria
    protected $primaryKey = 'id_clase';

    public $timestamps = false;

    public function maestro()
    {
        return $this->belongsTo(Maestro::class, 'id_maestro', 'id_maestro');
    }
}
