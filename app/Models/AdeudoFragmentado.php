<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdeudoFragmentado extends Model
{
    use HasFactory;

    protected $table = 'adeudos_fragmentados';
    protected $primaryKey = 'id_alumno';
    public $timestamps = false;

    protected $fillable = [
        'id_alumno',
        'id_programa',
        'id_clase',
        'periodo',
        'id_maestro',
        'monto'
    ];
}
