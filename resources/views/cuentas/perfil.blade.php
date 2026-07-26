@extends('layouts.app')
@section('title','Mi cuenta')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Mi cuenta</span></div>
        <h1 class="page-title">Configuración de Cuenta</h1>
        <p class="page-subtitle">Actualiza tus datos y cambia tu contraseña de forma segura</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-user"></i> Datos personales</div></div>
        <div class="card-body">
            <form action="/mi-cuenta" method="POST">@csrf
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" class="form-control" value="{{ $usuario->nombre }}" required></div>
                <div class="form-group"><label>Correo</label><input type="email" name="email" class="form-control" value="{{ $usuario->email }}" required></div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar datos</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-lock"></i> Cambiar contraseña</div></div>
        <div class="card-body">
            <form action="/mi-cuenta/password" method="POST">@csrf
                <div class="form-group"><label>Contraseña actual</label><input type="password" name="password_actual" class="form-control" required></div>
                <div class="form-group"><label>Nueva contraseña</label><input type="password" name="password_nueva" class="form-control" required></div>
                <div class="form-group"><label>Confirmar contraseña</label><input type="password" name="password_nueva_confirmation" class="form-control" required></div>
                <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Cambiar contraseña</button>
            </form>
        </div>
    </div>
</div>
@endsection
