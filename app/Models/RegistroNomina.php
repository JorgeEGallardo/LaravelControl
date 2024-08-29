<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroNomina extends Model
{
    use HasFactory;

    protected $table = 'registro_nominas';
    protected $primaryKey = 'id_nomina';
    public $timestamps = false;

    protected $fillable = [
        'id_maestro',
        'id_clase',
        'total',
        'comision',
        'total_neto'
    ];
}
