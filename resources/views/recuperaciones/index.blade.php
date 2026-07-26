@extends('layouts.app')
@section('title','Recuperación de Contraseña')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Recuperación</span></div>
        <h1 class="page-title"><i class="fas fa-key"></i> Recuperación de Contraseña</h1>
        <p class="page-subtitle">Restablece la contraseña de cualquier usuario activo del sistema</p>
    </div>
</div>

<div class="card" style="max-width:520px">
    <div class="card-body">
        <form method="POST" action="{{ route('recuperaciones.reset') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Usuario *</label>
                <select name="usuario_id" class="form-control" required>
                    <option value="">— Selecciona un usuario —</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id }}" @selected(old('usuario_id') == $u->id)>{{ $u->nombre }} ({{ $u->email }})</option>
                    @endforeach
                </select>
                @error('usuario_id')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nueva contraseña *</label>
                <input type="password" name="nueva_password" class="form-control" required minlength="8" placeholder="Mínimo 8 caracteres">
                @error('nueva_password')<span class="form-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar nueva contraseña *</label>
                <input type="password" name="nueva_password_confirmation" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Restablecer contraseña</button>
        </form>
        <p style="font-size:.78rem;color:var(--n500);margin-top:1rem">
            <i class="fas fa-info-circle"></i> Esta acción queda registrada en el módulo de Auditoría.
            Comunica la nueva contraseña al usuario por un canal seguro.
        </p>
    </div>
</div>
@endsection
