<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramaPredefinido extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'programas_predefinidos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'mensualidad',
        'nivel',
        'complex',
        'status',
        'ocultar'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => 'boolean',
        'ocultar' => 'boolean',
    ];


    public function registrosPredefinidos()
    {
        return $this->hasMany(RegistroPredefinido::class, 'id_programa', 'id_programa');
    }
    public function clases()
    {
        return $this->hasMany(Clase::class, 'id_programa', 'id_programa');
    }
}
