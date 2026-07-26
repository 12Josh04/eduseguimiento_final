@extends('layouts.app')
@section('title', $docente->nombre)
@section('content')

@php
    $dias = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
@endphp

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="{{ route('docentes.index') }}">Docentes</a>
            <span style="margin:0 .3rem">/</span>
            <span>{{ $docente->nombre }}</span>
        </div>
        <h1 class="page-title"><i class="fas fa-chalkboard-teacher"></i> {{ $docente->nombre }}</h1>
        <p class="page-subtitle">{{ $docente->email }}</p>
    </div>
    <a href="{{ route('docentes.index') }}" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Volver a paneles
    </a>
</div>

<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-book"></i> Cursos y grados asignados</div></div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Curso</th><th>Grado</th><th>Horario</th><th>Aula</th>
                        <th>Estudiantes</th><th>Promedio</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asignaciones as $a)
                    <tr>
                        <td><span class="tag tag-info">{{ $a->curso }}</span></td>
                        <td>{{ $a->grado }} <span style="color:var(--n400);font-size:.78rem">({{ $a->nivel }})</span></td>
                        <td style="font-size:.82rem;color:var(--n500)">
                            {{ $a->dia_semana ? ($dias[$a->dia_semana] ?? '—') . ' ' . substr($a->hora_inicio,0,5) . '–' . substr($a->hora_fin,0,5) : '—' }}
                        </td>
                        <td style="font-size:.82rem;color:var(--n400)">{{ $a->aula ?? '—' }}</td>
                        <td>{{ $a->total_estudiantes }}</td>
                        <td>
                            @if($a->promedio !== null)
                                <span style="font-weight:700;color:{{ $a->promedio >= 14 ? 'var(--success)' : ($a->promedio >= 11 ? 'var(--warning)' : 'var(--danger)') }}">{{ number_format($a->promedio,1) }}</span>
                            @else
                                <span style="color:var(--n400)">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('estudiantes.index', ['grado_id' => $a->grado_id]) }}" class="btn btn-ghost btn-sm btn-icon-only" title="Ver alumnos">
                                <i class="fas fa-user-graduate"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="empty-state" style="padding:2rem"><p>Sin asignaciones activas.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
