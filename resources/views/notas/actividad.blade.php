@extends('layouts.app')
@section('title', $actividad->titulo)
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="{{ route('notas.index') }}">Notas</a>
            <span style="margin:0 .3rem">/</span>
            <a href="{{ route('notas.curso', ['curso' => $actividad->curso_id, 'grado_id' => $actividad->grado_id, 'seccion' => $actividad->seccion ?? request('seccion', 'A')]) }}">{{ $actividad->curso_nombre }}</a>
            <span style="margin:0 .3rem">/</span>
            <span>{{ $actividad->titulo }}</span>
        </div>
        <h1 class="page-title"><i class="fas fa-tasks"></i> {{ $actividad->titulo }}</h1>
        <p class="page-subtitle">
            {{ $actividad->curso_nombre }} — {{ $actividad->grado_nombre }} — {{ $actividad->periodo_nombre }} — {{ $actividad->docente_nombre }}
        </p>
    </div>
    @if(in_array($rol, ['promotor','docente']))
    <form action="{{ route('actividades.destroy', $actividad->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta actividad? Se borrarán también las notas asociadas.')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-ghost" style="color:var(--danger)"><i class="fas fa-trash"></i> Eliminar actividad</button>
    </form>
    @endif
</div>

<div class="card" style="margin-bottom:1rem">
    <div class="card-body">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem">
            <span class="tag {{ $actividad->tipo_evaluacion==='examen'?'tag-danger':($actividad->tipo_evaluacion==='tarea'?'tag-success':'tag-warning') }}">{{ ucfirst($actividad->tipo_evaluacion) }}</span>
            @if($actividad->fecha_entrega)
                <span class="tag tag-info"><i class="fas fa-calendar"></i> Entrega: {{ \Carbon\Carbon::parse($actividad->fecha_entrega)->format('d/m/Y') }}</span>
            @endif
            @if($actividad->archivo_path)
                <a href="{{ route('actividades.archivo', $actividad->id) }}" target="_blank" class="tag" style="background:var(--primary-soft);color:var(--primary);text-decoration:none">
                    <i class="fas fa-paperclip"></i> Ver indicaciones adjuntas
                </a>
            @endif
        </div>
        <div style="background:var(--n50);border-radius:8px;padding:1rem;font-size:.9rem;white-space:pre-line">{{ $actividad->instrucciones }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-clipboard-list"></i> Entregas y calificación</div>
        <span style="font-size:.8rem;color:var(--n400)">{{ $entregas->count() }} estudiante(s)</span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Estudiante</th><th>Estado</th><th>Entrega</th>@if(in_array($rol,['promotor','docente']))<th>Nota</th><th>Acción</th>@endif</tr>
                </thead>
                <tbody>
                    @forelse($entregas as $e)
                    <tr>
                        <td>
                            <div class="student-cell">
                                <div class="avatar">{{ strtoupper(substr($e->estudiante_nombre,0,1)) }}</div>
                                <span style="font-weight:500">{{ $e->estudiante_nombre }}</span>
                            </div>
                        </td>
                        <td>
                            @if($e->estado === 'calificado')<span class="tag tag-success"><i class="fas fa-check-double"></i> Calificado</span>
                            @elseif($e->estado === 'entregado')<span class="tag tag-warning"><i class="fas fa-inbox"></i> Entregado</span>
                            @else<span class="tag" style="background:var(--n100);color:var(--n500)"><i class="fas fa-clock"></i> Pendiente</span>
                            @endif
                        </td>
                        <td style="font-size:.82rem;max-width:220px">
                            @if($e->entregado_en)
                                <div style="color:var(--n400)">{{ \Carbon\Carbon::parse($e->entregado_en)->format('d/m/Y H:i') }}</div>
                            @endif
                            @if($e->comentario_estudiante)
                                <div style="color:var(--n600);margin-top:.2rem">{{ Str::limit($e->comentario_estudiante, 80) }}</div>
                            @endif
                            @if($e->archivo_path)
                                <a href="{{ route('entregas.archivo', $e->id) }}" target="_blank" style="font-size:.78rem"><i class="fas fa-paperclip"></i> Ver archivo entregado</a>
                            @endif
                            @if(!$e->entregado_en && !$e->archivo_path)
                                <span style="color:var(--n400)">—</span>
                            @endif
                        </td>
                        @if(in_array($rol, ['promotor','docente']))
                        <td style="min-width:200px">
                            <form action="{{ route('actividades.calificar', ['id' => $actividad->id, 'estudiante' => $e->estudiante_id]) }}" method="POST" style="display:flex;gap:.3rem;align-items:center">
                                @csrf
                                <input type="number" name="nota" class="form-control" style="width:70px" min="0" max="20" step="0.5" value="{{ $e->nota }}" required>
                                <input type="text" name="observacion_docente" class="form-control" placeholder="Comentario (opcional)" value="{{ $e->observacion_docente }}" style="max-width:180px">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i></button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="5"><div class="empty-state" style="padding:2rem"><p>No hay estudiantes asignados a esta actividad.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
