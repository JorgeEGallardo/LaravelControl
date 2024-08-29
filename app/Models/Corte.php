<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Corte extends Model
{
    use HasFactory;

    protected $table = 'cortes';
    protected $primaryKey = 'id_corte';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'id_autor',
        'total'
    ];
}
