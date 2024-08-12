<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    use HasFactory;

    // Si la tabla no sigue la convención de nombres plural, especifica el nombre de la tabla
    protected $table = 'alumnos';
 // Si la clave primaria no es 'id', especifica el nombre de la clave primaria
    protected $primaryKey = 'id_alumno';

    // Indica que la clave primaria no es un entero autoincremental
    public $incrementing = false;

    // Indica que la clave primaria es de tipo string
    protected $keyType = 'string';

    // Lista de columnas que pueden ser asignadas masivamente
    protected $fillable = [
        'id_alumno',
        'nombre', 
        'fecha_nac', 
        'celular', 
        'tutor', 
        'tutor_2', 
        'telefono', 
        'telefono_2', 
        'hist_medico', 
        'status', 
        'beca',
        'url_imagen'
    ];

    public $timestamps = false;

    public function registrosPredefinidos()
    {
        return $this->hasOne(RegistroPredefinido::class, 'id_alumno', 'id_alumno');
    }
    
    public function adeudosProgramas()
    {
        return $this->hasMany(AdeudoPrograma::class, 'id_alumno', 'id_alumno');
    }
}
