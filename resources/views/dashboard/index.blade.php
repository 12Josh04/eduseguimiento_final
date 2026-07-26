@extends('layouts.app')
@section('title','Dashboard')
@section('content')
@php $rol = Auth::user()->rol; @endphp

<div class="page-header">
    <div>
        <div class="breadcrumb"><i class="fas fa-home"></i></div>
        <h1 class="page-title">Bienvenido, {{ explode(' ', Auth::user()->nombre)[0] }} 👋</h1>
        <p class="page-subtitle">{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }} — Año Escolar {{ anio_escolar_actual() }}</p>
    </div>
    @if(in_array($rol,['promotor','docente','psicologa']))
    <div class="page-actions">
        <a href="/conductas" class="btn btn-success"><i class="fas fa-plus"></i> Registrar Conducta</a>
        <a href="/notas" class="btn btn-primary"><i class="fas fa-plus"></i> Registrar Nota</a>
    </div>
    @endif
</div>

<div class="stats-grid">
    @if(in_array($rol,['promotor','docente','psicologa']))
    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div><div><div class="stat-value">{{ $stats['total_estudiantes'] ?? 17 }}</div><div class="stat-label">Estudiantes activos</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div><div><div class="stat-value">{{ $stats['aprobados'] ?? 10 }}</div><div class="stat-label">Aprobados</div></div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-exclamation-triangle"></i></div><div><div class="stat-value">{{ $stats['en_riesgo'] ?? 4 }}</div><div class="stat-label">En riesgo</div></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="fas fa-times-circle"></i></div><div><div class="stat-value">{{ $stats['desaprobados'] ?? 3 }}</div><div class="stat-label">Desaprobados</div></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-clipboard-list"></i></div><div><div class="stat-value">{{ $stats['conductas_semana'] ?? 0 }}</div><div class="stat-label">Conductas esta semana</div></div></div>
    @endif
    @if($rol==='estudiante')
    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-star"></i></div><div><div class="stat-value">{{ number_format($stats['promedio_general']??0,1) }}</div><div class="stat-label">Promedio general</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-book-open"></i></div><div><div class="stat-value">{{ $stats['cursos_aprobados']??0 }}</div><div class="stat-label">Cursos aprobados</div></div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-exclamation"></i></div><div><div class="stat-value">{{ $stats['cursos_riesgo']??0 }}</div><div class="stat-label">Cursos en riesgo</div></div></div>
    @endif
</div>

<div class="grid-2">
    @if(in_array($rol,['promotor','docente','psicologa']))
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-clipboard-check"></i> Conductas Recientes</div>
            <a href="/conductas" class="btn btn-ghost btn-sm">Ver todas</a>
        </div>
        <div class="card-body" style="padding:0">
            <table>
                <thead><tr><th>Estudiante</th><th>Tipo</th><th>Hace</th></tr></thead>
                <tbody>
                    @forelse($conductas_recientes as $c)
                    <tr>
                        <td><div class="student-cell"><div class="avatar">{{ strtoupper(substr($c->estudiante_nombre,0,1)) }}</div><span>{{ $c->estudiante_nombre }}</span></div></td>
                        <td><span class="tag conducta-{{ $c->tipo }}">{{ ucfirst($c->tipo) }}</span></td>
                        <td style="color:var(--n400);font-size:.8rem">{{ \Carbon\Carbon::parse($c->fecha_hora)->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3"><div class="empty-state" style="padding:1.5rem"><div class="empty-state-icon"><i class="fas fa-clipboard"></i></div><p>Sin conductas registradas.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-chart-bar"></i> Promedios por Curso</div>
            <a href="/notas/promedios" class="btn btn-ghost btn-sm">Ver detalle</a>
        </div>
        <div class="card-body">
            @forelse($promedios_cursos as $c)
            @php $pct=min(100,($c['promedio']/$c['max'])*100); $cls=$c['promedio']>=14?'good':($c['promedio']>=11?'medium':'bad'); @endphp
            <div style="margin-bottom:.9rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
                    <span style="font-size:.85rem;font-weight:500">{{ $c['nombre'] }}</span>
                    <span class="{{ $c['promedio']>=14?'nota-alta':($c['promedio']>=11?'nota-media':'nota-baja') }}" style="font-size:.85rem">{{ number_format($c['promedio'],1) }}</span>
                </div>
                <div class="progress-bar-wrap"><div class="progress-bar-fill {{ $cls }}" style="width:{{ $pct }}%"></div></div>
            </div>
            @empty
            <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-chart-bar"></i></div><p>Sin datos de notas aún.</p></div>
            @endforelse
        </div>
    </div>
</div>

@if(in_array($rol,['promotor','psicologa']) && count($estudiantes_riesgo))
<div class="card" style="margin-top:1rem">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i> Estudiantes que Requieren Atención</div>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead><tr><th>Estudiante</th><th>Grado</th><th>Promedio</th><th>Estado</th><th>Conductas (−)</th><th>Acciones</th></tr></thead>
            <tbody>
                @foreach($estudiantes_riesgo as $est)
                <tr>
                    <td><div class="student-cell"><div class="avatar" style="background:var(--danger-soft);color:var(--danger)">{{ strtoupper(substr($est['nombre'],0,1)) }}</div><span>{{ $est['nombre'] }}</span></div></td>
                    <td><span class="tag tag-info">{{ $est['grado'] }}</span></td>
                    <td><span class="nota-baja">{{ number_format($est['promedio'],1) }}</span></td>
                    <td>@if($est['estado']==='desaprobado')<span class="tag tag-danger">✗ Desaprobado</span>@else<span class="tag tag-warning">⚠ En riesgo</span>@endif</td>
                    <td>@if($est['conductas']>0)<span class="tag tag-danger">{{ $est['conductas'] }}</span>@else<span style="color:var(--n400)">—</span>@endif</td>
                    <td><a href="/conductas" class="btn btn-ghost btn-sm"><i class="fas fa-eye"></i> Ver</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(in_array($rol,['promotor','psicologa']) && count($alertas_asistencia))
<div class="card" style="margin-top:1rem">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-bell" style="color:var(--danger)"></i> Alertas de Riesgo por Inasistencia</div>
        <span style="font-size:.78rem;color:var(--n500)">Menos del 75% de asistencia</span>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead><tr><th>Estudiante</th><th>Grado</th><th>% Asistencia</th><th>Acciones</th></tr></thead>
            <tbody>
                @foreach($alertas_asistencia as $al)
                <tr>
                    <td><div class="student-cell"><div class="avatar" style="background:var(--danger-soft);color:var(--danger)">{{ strtoupper(substr($al['nombre'],0,1)) }}</div><span>{{ $al['nombre'] }}</span></div></td>
                    <td><span class="tag tag-info">{{ $al['grado'] }}</span></td>
                    <td><span class="tag tag-danger">{{ $al['porcentaje'] }}%</span></td>
                    <td><a href="/control-asistencia" class="btn btn-ghost btn-sm"><i class="fas fa-eye"></i> Ver</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
