<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdeudoPrograma extends Model
{
    use HasFactory;

    protected $table = 'adeudos_programas';
    protected $primaryKey = 'id_alumno'; // Ajusta esto según la clave primaria de tu tabla
    public $timestamps = false; // Indica a Laravel que no maneje timestamps automáticamente
      // Indica que la clave primaria no es un entero autoincremental
      public $incrementing = false;

      // Indica que la clave primaria es de tipo string
      protected $keyType = 'string';

    protected $fillable = [
        'id_alumno',
        'id_programa',
        'periodo',
        'concepto',
        'monto',
        'beca',
        'descuento',
        'fecha_limite'
    ];
    
    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'id_alumno', 'id_alumno');
    }

    public function programa()
    {
        return $this->belongsTo(ProgramaPredefinido::class, 'id_programa', 'id_programa');
    }
}

