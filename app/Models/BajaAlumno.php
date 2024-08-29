<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BajaAlumno extends Model
{
    use HasFactory;

    protected $table = 'bajas_alumnos';
    protected $primaryKey = 'id_alumno';
    public $timestamps = false;

    protected $fillable = [
        'id_alumno',
        'fecha'
    ];
}
