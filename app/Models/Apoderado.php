<?php
// app/Models/Apoderado.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Apoderado extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'estudiante_id', 'nombre', 'parentesco', 
        'telefono', 'email', 'es_principal'
    ];

    protected $casts = [
        'es_principal' => 'boolean',
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

    // Scope para apoderados principales
    public function scopePrincipales($query)
    {
        return $query->where('es_principal', true);
    }
}