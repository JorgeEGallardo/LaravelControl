<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroPredefinido extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'registro_predefinido';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_alumno',
        'id_programa',
        'precio',
        'beca'
    ];

    public function programaPredefinido()
    {
        return $this->belongsTo(ProgramaPredefinido::class, 'id_programa', 'id_programa');
    }

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'id_alumno', 'id_alumno');
    }
}
