<?php
// app/Models/ConfiguracionInstitucional.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ConfiguracionInstitucional extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'clave', 'valor', 'tipo', 'descripcion'
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

    // Scopes
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Helper para obtener valor por clave
    public static function getValor($clave, $default = null)
    {
        $config = self::where('clave', $clave)->first();
        return $config ? $config->valor : $default;
    }

    // Helper para establecer valor
    public static function setValor($clave, $valor, $descripcion = null)
    {
        $config = self::updateOrCreate(
            ['clave' => $clave],
            [
                'valor' => $valor,
                'descripcion' => $descripcion
            ]
        );
        return $config;
    }

    // Cast según tipo
    public function getValorFormateadoAttribute()
    {
        return match ($this->tipo) {
            'boolean' => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->valor,
            'float' => (float) $this->valor,
            'json' => json_decode($this->valor, true),
            default => $this->valor,
        };
    }
}