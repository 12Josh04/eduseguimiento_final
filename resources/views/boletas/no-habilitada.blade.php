@extends('layouts.app')
@section('title','Libreta de Notas')
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Libreta de Notas</span></div>
        <h1 class="page-title">Libreta de Notas</h1>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:3rem;text-align:center">
        <div class="empty-state-icon" style="background:var(--warning-soft);color:var(--warning);margin:0 auto 1rem">
            <i class="fas fa-lock"></i>
        </div>
        <h3 style="margin-bottom:.5rem">Tu Libreta de Notas aún no está disponible</h3>
        <p style="color:var(--n500);max-width:420px;margin:0 auto">
            El promotor habilita esta sección cuando la libreta oficial del año escolar está lista para consulta.
            Vuelve a intentarlo más adelante, o consulta directamente con el colegio.
        </p>
    </div>
</div>

@endsection
