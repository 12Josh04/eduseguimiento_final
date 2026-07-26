@extends('layouts.app')
@section('title','Detalle de Asignación')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><a href="{{ route('asignaciones.index') }}">Asignaciones</a><span style="margin:0 .3rem">/</span><span>Detalle</span></div>
        <h1 class="page-title"><i class="fas fa-user-check"></i> {{ $asignacion->docente->nombre ?? '—' }} — {{ $asignacion->curso->nombre ?? '—' }}</h1>
    </div>
    <a href="{{ route('asignaciones.edit', $asignacion->id) }}" class="btn btn-primary"><i class="fas fa-edit"></i> Editar</a>
</div>

<div class="card" style="margin-bottom:1rem">
    <div class="card-body">
        <div class="grid-2">
            <div><strong>Docente:</strong> {{ $asignacion->docente->nombre ?? '—' }}</div>
            <div><strong>Curso:</strong> {{ $asignacion->curso->nombre ?? '—' }}</div>
            <div><strong>Tipo:</strong> {{ $asignacion->isGeneral() ? 'General (todos los grados)' : 'Específico' }}</div>
            <div><strong>Grado / Sección:</strong> {{ $asignacion->grado->nombre ?? 'Todos' }} {{ $asignacion->seccion ? '"'.$asignacion->seccion.'"' : '' }}</div>
            <div><strong>Año escolar:</strong> {{ $asignacion->anio_escolar }}</div>
            <div><strong>Estado:</strong> {{ $asignacion->activo ? 'Activo' : 'Inactivo' }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-calendar-week"></i> Horarios asignados</div></div>
    <div class="card-body" style="padding:0">
        <table>
            <thead><tr><th>Día</th><th>Hora inicio</th><th>Hora fin</th><th>Aula</th></tr></thead>
            <tbody>
                @forelse($asignacion->horarios as $h)
                <tr>
                    <td>{{ $h->dia_nombre }}</td>
                    <td>{{ \Carbon\Carbon::parse($h->hora_inicio)->format('H:i') }}</td>
                    <td>{{ \Carbon\Carbon::parse($h->hora_fin)->format('H:i') }}</td>
                    <td>{{ $h->aula ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state" style="padding:1.5rem"><p>Sin horarios registrados.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
