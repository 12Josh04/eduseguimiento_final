<?php
// app/Models/Nota.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Estudiante;

class Nota extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Usa 'creado_en' en lugar de 'created_at'

    protected $fillable = [
        'id', 'estudiante_id', 'curso_id', 'grado_id', 'seccion',
        'observacion', 'docente_id', 'periodo_id', 'tipo_evaluacion',
        'nombre_evaluacion', 'nota', 'nota_maxima', 'fecha'
    ];

    protected $casts = [
        'nota' => 'decimal:2',
        'nota_maxima' => 'decimal:2',
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

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    // Scope para filtrar por periodo
    public function scopePeriodo($query, $periodoId)
    {
        return $query->where('periodo_id', $periodoId);
    }

    // Scope para filtrar por tipo de evaluación
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo_evaluacion', $tipo);
    }

    // Obtener el porcentaje de la nota
    public function getPorcentajeAttribute()
    {
        if ($this->nota_maxima > 0) {
            return ($this->nota / $this->nota_maxima) * 100;
        }
        return 0;
    }

    // Verificar si está aprobado (nota mínima 11/20)
    public function getAprobadoAttribute()
    {
        return $this->nota >= 11;
    }
}