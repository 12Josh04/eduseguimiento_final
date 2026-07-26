<?php
// app/Models/Seccion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    // 👇 ESPECIFICAR EL NOMBRE CORRECTO DE LA TABLA
    protected $table = 'secciones';
    
    protected $fillable = [
        'id', 'grado_id', 'nombre', 'anio_escolar', 'activo'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(AsignacionesDocente::class, 'seccion', 'nombre');
    }
}