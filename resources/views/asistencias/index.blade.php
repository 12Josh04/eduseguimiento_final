@extends('layouts.app')
@section('title','Asistencias')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span>
            @if($vista === 'lista')
                <a href="{{ route('asistencias.index') }}">Asistencias</a><span style="margin:0 .3rem">/</span>
                <span>{{ $grado->nombre ?? '—' }} "{{ $seccion }}"</span>
            @else
                <span>Asistencias</span>
            @endif
        </div>
        <h1 class="page-title">
            @if($vista === 'lista') {{ $grado->nombre ?? '—' }} "{{ $seccion }}" @else Control de Asistencia @endif
        </h1>
        <p class="page-subtitle">
            @if($vista === 'lista') Registra la asistencia del día por curso @else Elige el salón donde vas a tomar asistencia hoy @endif
        </p>
    </div>
    @if($vista === 'lista')
    <a href="{{ route('asistencias.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Volver a paneles</a>
    @endif
</div>

@if($vista === 'paneles')
<div class="paneles-nivel">
    @forelse($paneles as $nivel => $seccionesNivel)
        <div class="nivel-bloque">
            <h2 class="nivel-titulo">
                <i class="fas {{ $nivel === 'Primaria' ? 'fa-child' : 'fa-user-graduate' }}"></i>
                {{ $nivel }}
            </h2>
            <div class="paneles-grid">
                @foreach($seccionesNivel as $p)
                    @php
                        $completo = $p->total_estudiantes > 0 && $p->registrados_hoy >= $p->total_estudiantes;
                    @endphp
                    <a href="{{ route('asistencias.index', ['grado_id' => $p->grado_id, 'seccion' => $p->seccion, 'fecha' => $fecha]) }}" class="panel-grado">
                        <div class="panel-grado-header">
                            <span class="panel-grado-nombre">{{ $p->grado_nombre }} "{{ $p->seccion }}"</span>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <div class="panel-grado-body">
                            <div class="panel-stat">
                                <span class="panel-stat-num">{{ $p->total_estudiantes }}</span>
                                <span class="panel-stat-label">estudiantes</span>
                            </div>
                            <div class="panel-stat">
                                <span class="panel-stat-num" style="color:{{ $completo ? 'var(--success)' : 'var(--warning)' }}">{{ $p->registrados_hoy }}</span>
                                <span class="panel-stat-label">registrados hoy</span>
                            </div>
                        </div>
                        @if($completo)
                            <span class="tag tag-success" style="margin-top:.5rem;display:inline-block"><i class="fas fa-check"></i> Completo</span>
                        @elseif($p->registrados_hoy > 0)
                            <span class="tag tag-warning" style="margin-top:.5rem;display:inline-block">Pendiente</span>
                        @else
                            <span class="tag" style="margin-top:.5rem;display:inline-block;background:var(--n100);color:var(--n500)">Sin registrar</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body">
            <div class="empty-state" style="padding:2rem"><p>No tienes grados ni secciones asignados todavía.</p></div>
        </div></div>
    @endforelse
</div>

<style>
    .nivel-bloque{margin-bottom:1.75rem}
    .nivel-titulo{font-size:1rem;font-weight:800;color:var(--primary);margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
    .paneles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:.85rem}
    .panel-grado{background:var(--surface);border:1px solid var(--n200);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:1rem;text-decoration:none;color:inherit;transition:var(--t);display:block}
    .panel-grado:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);border-color:var(--primary-light)}
    .panel-grado-header{display:flex;justify-content:space-between;align-items:center;font-weight:700;color:var(--primary);margin-bottom:.75rem}
    .panel-grado-header i{color:var(--n400);font-size:.8rem}
    .panel-grado-body{display:flex;gap:1.25rem}
    .panel-stat{display:flex;flex-direction:column}
    .panel-stat-num{font-size:1.3rem;font-weight:800;color:var(--n800)}
    .panel-stat-label{font-size:.72rem;color:var(--n500);text-transform:uppercase;letter-spacing:.03em}
</style>

@else
<div class="card">
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="hidden" name="grado_id" value="{{ $gradoId }}">
            <input type="hidden" name="seccion" value="{{ $seccion }}">
            <input type="date" name="fecha" class="form-control" value="{{ $fecha }}">
            <select name="curso_id" class="form-control" onchange="this.form.submit()">
                @foreach($cursos as $c)
                    <option value="{{ $c->id }}" {{ $cursoId == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
        </form>

        <div class="table-wrapper">
            <table>
                <thead><tr><th>Estudiante</th><th>Estado</th><th>Observación</th><th>Acción</th></tr></thead>
                <tbody>
                    @forelse($estudiantes as $est)
                    <tr>
                        <td>{{ $est->estudiante }}</td>
                        <td>@php $a = $asistencias[$est->id] ?? null; @endphp {{ $a->estado ?? 'presente' }}</td>
                        <td>{{ $a->observacion ?? '—' }}</td>
                        <td>
                            <form action="/asistencias" method="POST" style="display:flex;gap:.3rem;align-items:center">@csrf
                                <input type="hidden" name="fecha" value="{{ $fecha }}">
                                <input type="hidden" name="estudiante_id" value="{{ $est->id }}">
                                <input type="hidden" name="curso_id" value="{{ $cursoId }}">
                                <input type="hidden" name="grado_id" value="{{ $est->grado_id }}">
                                <input type="hidden" name="seccion" value="{{ $est->seccion }}">
                                <select name="estado" class="form-control" style="min-width:120px">
                                    <option value="presente" {{ ($a->estado ?? 'presente') == 'presente' ? 'selected' : '' }}>Presente</option>
                                    <option value="falta" {{ ($a->estado ?? '') == 'falta' ? 'selected' : '' }}>Falta</option>
                                    <option value="tardanza" {{ ($a->estado ?? '') == 'tardanza' ? 'selected' : '' }}>Tardanza</option>
                                </select>
                                <input type="text" name="observacion" class="form-control" placeholder="Obs." value="{{ $a->observacion ?? '' }}" style="max-width:140px">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-calendar-check"></i></div><p>No hay estudiantes para mostrar.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
