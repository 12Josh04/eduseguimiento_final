<?php
// app/Models/Grado.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Matricula;

class Grado extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'nombre', 'nivel', 'anio_escolar', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Boot para generar UUID automáticamente
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
    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }

    public function estudiantes()
    {
        return $this->hasManyThrough(Estudiante::class, Matricula::class);
    }

    public function secciones()
    {
        return $this->hasMany(Seccion::class);
    }

    public function asignacionesDocentes()
    {
        return $this->hasMany(AsignacionesDocente::class);
    }

    public function cursos()
    {
        return $this->hasMany(Curso::class);
    }

    // Scope para año escolar activo
    public function scopeAnioActivo($query, $anio = null)
    {
        $anio = $anio ?? now()->year;
        return $query->where('anio_escolar', $anio);
    }

    // Scope para grados activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}