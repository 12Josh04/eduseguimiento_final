<?php
// app/Models/Matricula.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Matricula extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'estudiante_id', 'grado_id', 'seccion', 
        'anio_escolar', 'activo'
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
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    // Scope para matrículas activas del año actual
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeAnio($query, $anio)
    {
        return $query->where('anio_escolar', $anio);
    }
}