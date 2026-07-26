<?php
// app/Models/AsignacionesDocente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionesDocente extends Model
{
    protected $table = 'asignaciones_docente';
    
    protected $fillable = [
        'id', 'docente_id', 'curso_id', 'grado_id', 'seccion', 
        'anio_escolar', 'activo'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    // 👇 CORREGIR: La clave foránea en horarios es 'asignacion_id'
    public function horarios()
    {
        return $this->hasMany(Horario::class, 'asignacion_id');
    }

    public function isGeneral()
    {
        return is_null($this->grado_id) && is_null($this->seccion);
    }
}