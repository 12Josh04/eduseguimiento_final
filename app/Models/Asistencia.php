<?php
// app/Models/Asistencia.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Asistencia extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'estudiante_id', 'curso_id', 'docente_id', 
        'grado_id', 'seccion', 'fecha', 'estado', 'observacion'
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

    // Relaciones
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class);
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    // Scopes
    public function scopeFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    // Obtener color según estado
    public function getColorAttribute()
    {
        return match ($this->estado) {
            'presente' => 'success',
            'tardanza' => 'warning',
            'justificado' => 'info',
            'faltó' => 'danger',
            default => 'secondary',
        };
    }
}