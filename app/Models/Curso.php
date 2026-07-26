<?php
// app/Models/Curso.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Grado;
use App\Models\AsignacionDocente;


class Curso extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'nombre', 'codigo', 'grado_id', 'seccion', 
        'anio_escolar', 'nivel', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
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
    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function asignacionesDocentes()
    {
        return $this->hasMany(AsignacionesDocente::class);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    // Scope para cursos activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para nivel
    public function scopeNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }
}