<?php
// app/Models/Periodo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Periodo extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'nombre', 'tipo', 'anio_escolar', 
        'fecha_inicio', 'fecha_fin', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
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
    public function notas()
    {
        return $this->hasMany(Nota::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeAnio($query, $anio)
    {
        return $query->where('anio_escolar', $anio);
    }

    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Verificar si el periodo está vigente
    public function getEstaVigenteAttribute()
    {
        $hoy = now();
        return $hoy->between($this->fecha_inicio, $this->fecha_fin);
    }
}