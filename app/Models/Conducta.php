<?php
// app/Models/Conducta.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Conducta extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'estudiante_id', 'reportador_id', 'tipo',
        'descripcion', 'lugar', 'fecha_hora'
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
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

    public function reportador()
    {
        return $this->belongsTo(User::class, 'reportador_id');
    }

    // Scopes para tipos
    public function scopePositivas($query)
    {
        return $query->where('tipo', 'positivo');
    }

    public function scopeNegativas($query)
    {
        return $query->where('tipo', 'negativo');
    }

    public function scopeObservaciones($query)
    {
        return $query->where('tipo', 'observacion');
    }

    // Scope para fechas
    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_hora', [$inicio, $fin]);
    }

    // Obtener color según tipo
    public function getColorAttribute()
    {
        return match ($this->tipo) {
            'positivo' => 'success',
            'negativo' => 'danger',
            'observacion' => 'warning',
            default => 'secondary',
        };
    }

    // Obtener icono según tipo
    public function getIconoAttribute()
    {
        return match ($this->tipo) {
            'positivo' => 'fa-thumbs-up',
            'negativo' => 'fa-thumbs-down',
            'observacion' => 'fa-eye',
            default => 'fa-info',
        };
    }
}