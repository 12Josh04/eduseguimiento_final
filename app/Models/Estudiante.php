<?php
// app/Models/Estudiante.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Estudiante extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'usuario_id', 'nombre', 'apellido', 'codigo',
        'fecha_nacimiento', 'dni', 'telefono', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_nacimiento' => 'date',
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
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function apoderados()
    {
        return $this->hasMany(Apoderado::class);
    }

    public function apoderadoPrincipal()
    {
        return $this->hasOne(Apoderado::class)->where('es_principal', true);
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class);
    }

    public function matriculaActiva()
    {
        return $this->hasOne(Matricula::class)->where('activo', true);
    }

    public function notas()
    {
        return $this->hasMany(Nota::class);
    }

    public function conductas()
    {
        return $this->hasMany(Conducta::class);
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }

    // Scope
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Obtener nombre completo
    public function getNombreCompletoAttribute()
    {
        return $this->nombre . ' ' . $this->apellido;
    }

    // Obtener edad
    public function getEdadAttribute()
    {
        if ($this->fecha_nacimiento) {
            return $this->fecha_nacimiento->age;
        }
        return null;
    }
}