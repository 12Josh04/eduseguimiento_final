<?php
// app/Models/Horario.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $table = 'horarios';
    
    protected $fillable = [
        'id', 'asignacion_id', 'dia_semana', 'hora_inicio', 
        'hora_fin', 'aula', 'tipo_horario', 'observaciones'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'dia_semana' => 'integer',
    ];

    // 👇 CORREGIR: La clave foránea es 'asignacion_id', no 'asignaciones_docente_id'
    public function asignacion()
    {
        return $this->belongsTo(AsignacionesDocente::class, 'asignacion_id');
    }

    public function getDiaNombreAttribute()
    {
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ];
        return $dias[$this->dia_semana] ?? 'Desconocido';
    }
}