<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class User extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id', 'nombre', 'email', 'password_hash', 
        'rol_id', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected $hidden = [
        'password_hash',
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
    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class);
    }

    public function asignacionesDocentes()
    {
        return $this->hasMany(AsignacionesDocente::class, 'docente_id');
    }

    public function notas()
    {
        return $this->hasMany(Nota::class, 'docente_id');
    }

    public function conductasReportadas()
    {
        return $this->hasMany(Conducta::class, 'reportador_id');
    }

    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'docente_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeRol($query, $rolNombre)
    {
        return $query->whereHas('rol', function ($q) use ($rolNombre) {
            $q->where('nombre', $rolNombre);
        });
    }

    // Helper para roles
    public function esDocente()
    {
        return $this->rol->nombre === 'docente';
    }

    public function esEstudiante()
    {
        return $this->rol->nombre === 'estudiante';
    }

    public function esPsicologa()
    {
        return $this->rol->nombre === 'psicologa';
    }

    public function esPromotor()
    {
        return $this->rol->nombre === 'promotor';
    }

    // Verificar contraseña
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }
}