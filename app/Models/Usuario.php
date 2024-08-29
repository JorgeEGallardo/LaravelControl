<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

        // Si la tabla no sigue la convención de nombres plural, especifica el nombre de la tabla
        protected $table = 'usuarios';

        protected $primaryKey = 'id';

        // Indica que la clave primaria no es un entero autoincremental
        public $incrementing = false;

        // Indica que la clave primaria es de tipo string
        protected $keyType = 'string';

        // Lista de columnas que pueden ser asignadas masivamente
        protected $fillable = ['usuario', 'nombre', 'password', 'permisos'];
    
        // Opción para ocultar campos (ej. password) en las respuestas JSON
        protected $hidden = ['password'];

        public $timestamps = false;
}
