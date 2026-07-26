@extends('layouts.app')
@section('title', $actividad->titulo)
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="{{ route('alumno.actividades') }}">Mis Actividades</a>
            <span style="margin:0 .3rem">/</span>
            <span>{{ $actividad->titulo }}</span>
        </div>
        <h1 class="page-title"><i class="fas fa-tasks"></i> {{ $actividad->titulo }}</h1>
        <p class="page-subtitle">{{ $actividad->curso_nombre }} — {{ $actividad->periodo_nombre }} — {{ $actividad->docente_nombre }}</p>
    </div>
</div>

<div class="card" style="margin-bottom:1rem">
    <div class="card-body">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem">
            <span class="tag {{ $actividad->tipo_evaluacion==='examen'?'tag-danger':($actividad->tipo_evaluacion==='tarea'?'tag-success':'tag-warning') }}">{{ ucfirst($actividad->tipo_evaluacion) }}</span>
            @if($actividad->fecha_entrega)
                <span class="tag tag-info"><i class="fas fa-calendar"></i> Fecha de entrega: {{ \Carbon\Carbon::parse($actividad->fecha_entrega)->format('d/m/Y') }}</span>
            @endif
            @if($actividad->archivo_path)
                <a href="{{ route('actividades.archivo', $actividad->id) }}" target="_blank" class="tag" style="background:var(--primary-soft);color:var(--primary);text-decoration:none">
                    <i class="fas fa-paperclip"></i> Descargar indicaciones
                </a>
            @endif
        </div>
        <div style="background:var(--n50);border-radius:8px;padding:1rem;font-size:.9rem;white-space:pre-line">{{ $actividad->instrucciones }}</div>
    </div>
</div>

@if($entrega->estado === 'calificado')
<div class="card" style="border-left:3px solid var(--success)">
    <div class="card-header"><div class="card-title"><i class="fas fa-check-double" style="color:var(--success)"></i> Actividad Calificada</div></div>
    <div class="card-body">
        <div style="display:flex;gap:2rem;flex-wrap:wrap;align-items:center;margin-bottom:1rem">
            <div style="text-align:center;padding:.75rem 1.25rem;background:var(--success-soft);border-radius:10px">
                <div style="font-size:1.8rem;font-weight:800;color:{{ $entrega->nota>=14?'var(--success)':($entrega->nota>=11?'var(--warning)':'var(--danger)') }}">{{ number_format($entrega->nota,1) }}</div>
                <div style="font-size:.7rem;color:var(--n400);font-weight:600">NOTA /20</div>
            </div>
            @if($entrega->calificado_en)
                <span style="font-size:.82rem;color:var(--n400)">Calificado el {{ \Carbon\Carbon::parse($entrega->calificado_en)->format('d/m/Y H:i') }}</span>
            @endif
        </div>
        @if($entrega->observacion_docente)
            <div style="background:var(--n50);border-radius:8px;padding:.9rem">
                <strong style="font-size:.82rem;color:var(--n600)">Comentario del docente:</strong>
                <p style="font-size:.88rem;margin-top:.3rem">{{ $entrega->observacion_docente }}</p>
            </div>
        @endif
        @if($entrega->archivo_path)
            <p style="margin-top:.9rem"><a href="{{ route('entregas.archivo', $entrega->id) }}" target="_blank"><i class="fas fa-paperclip"></i> Ver mi archivo entregado</a></p>
        @endif
    </div>
</div>
@else
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-upload"></i> {{ $entrega->estado === 'entregado' ? 'Actualizar mi entrega' : 'Entregar trabajo' }}</div>
        @if($entrega->estado === 'entregado')<span class="tag tag-warning">Entregado — pendiente de calificar</span>@endif
    </div>
    <div class="card-body">
        @if($entrega->estado === 'entregado' && $entrega->entregado_en)
            <p style="font-size:.82rem;color:var(--n400);margin-bottom:1rem">
                Última entrega: {{ \Carbon\Carbon::parse($entrega->entregado_en)->format('d/m/Y H:i') }}.
                Puedes reemplazarla mientras no haya sido calificada.
            </p>
        @endif
        <form action="{{ route('alumno.actividad.entregar', $actividad->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Comentario (opcional)</label>
                <textarea name="comentario_estudiante" class="form-control" rows="3" placeholder="Escribe algún comentario para tu docente...">{{ $entrega->comentario_estudiante }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Archivo de tu trabajo (opcional)</label>
                <input type="file" name="archivo" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                @if($entrega->archivo_path)
                    <p style="font-size:.78rem;color:var(--n400);margin-top:.3rem">
                        Ya tienes un archivo entregado: <a href="{{ route('entregas.archivo', $entrega->id) }}" target="_blank">verlo aquí</a>. Sube uno nuevo para reemplazarlo.
                    </p>
                @endif
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> {{ $entrega->estado === 'entregado' ? 'Actualizar entrega' : 'Entregar' }}</button>
        </form>
    </div>
</div>
@endif
@endsection
