<?php
// app/Models/Rol.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'nombre', 'descripcion'
    ];

    // Relación con usuarios
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Scope para obtener roles por nombre
    public function scopeNombre($query, $nombre)
    {
        return $query->where('nombre', $nombre);
    }
}