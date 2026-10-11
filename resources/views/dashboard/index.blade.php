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
        @if(in_array($rol,['promotor','docente']))
        <a href="/notas" class="btn btn-primary"><i class="fas fa-plus"></i> Registrar Nota</a>
        @endif
    </div>
    @endif
</div>

<div class="stats-grid">
    @if(in_array($rol,['promotor','docente','psicologa']))
    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div><div><div class="stat-value">{{ $stats['total_estudiantes'] ?? 17 }}</div><div class="stat-label">Estudiantes activos</div></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-clipboard-list"></i></div><div><div class="stat-value">{{ $stats['conductas_semana'] ?? 0 }}</div><div class="stat-label">Conductas esta semana</div></div></div>
    @endif
    @if($rol==='estudiante')
    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-star"></i></div><div><div class="stat-value">{{ number_format($stats['promedio_general']??0,1) }}</div><div class="stat-label">Promedio general</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-book-open"></i></div><div><div class="stat-value">{{ $stats['cursos_aprobados']??0 }}</div><div class="stat-label">Cursos aprobados</div></div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-exclamation"></i></div><div><div class="stat-value">{{ $stats['cursos_riesgo']??0 }}</div><div class="stat-label">Cursos en riesgo</div></div></div>
    @endif
</div>

@if($rol==='estudiante' && count($promedios_cursos))
<div class="card" style="margin-bottom:1rem">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-chart-bar"></i> Mis Promedios por Curso</div>
        <a href="{{ route('alumno.promedios') }}" class="btn btn-ghost btn-sm">Ver detalle</a>
    </div>
    <div class="card-body">
        @foreach($promedios_cursos as $c)
        @php $pct=min(100,($c['promedio']/$c['max'])*100); $cls=$c['promedio']>=14?'good':($c['promedio']>=11?'medium':'bad'); @endphp
        <div style="margin-bottom:.9rem">
            <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
                <span style="font-size:.85rem;font-weight:500">{{ $c['nombre'] }}</span>
                <span class="{{ $c['promedio']>=14?'nota-alta':($c['promedio']>=11?'nota-media':'nota-baja') }}" style="font-size:.85rem">{{ number_format($c['promedio'],1) }}</span>
            </div>
            <div class="progress-bar-wrap"><div class="progress-bar-fill {{ $cls }}" style="width:{{ $pct }}%"></div></div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if(in_array($rol,['promotor','docente']) && count($nivelStats))
<div class="card" style="margin-bottom:1rem">
    <div class="card-header"><div class="card-title"><i class="fas fa-layer-group"></i> Panorama por Nivel Educativo</div></div>
    <div class="card-body">
        <div class="grid-2" style="align-items:start">
            @foreach(['Primaria','Secundaria'] as $nivel)
            @php $n = $nivelStats[$nivel] ?? null; @endphp
            <div class="nivel-panel">
                <div class="nivel-panel-header">
                    <i class="fas {{ $nivel === 'Primaria' ? 'fa-child' : 'fa-user-graduate' }}"></i>
                    <span>{{ $nivel }}</span>
                    <span class="tag tag-info" style="margin-left:auto">{{ $n['total_estudiantes'] ?? 0 }} estudiante{{ ($n['total_estudiantes'] ?? 0) == 1 ? '' : 's' }}</span>
                </div>

                @if($nivel === 'Primaria')
                    <div class="nivel-chip-row">
                        <span class="nivel-chip" style="color:{{ nivel_logro_color('AD') }}"><strong>{{ $n['distribucion_logro']['AD'] ?? 0 }}</strong> AD</span>
                        <span class="nivel-chip" style="color:{{ nivel_logro_color('A') }}"><strong>{{ $n['distribucion_logro']['A'] ?? 0 }}</strong> A</span>
                        <span class="nivel-chip" style="color:{{ nivel_logro_color('B') }}"><strong>{{ $n['distribucion_logro']['B'] ?? 0 }}</strong> B</span>
                        <span class="nivel-chip" style="color:{{ nivel_logro_color('C') }}"><strong>{{ $n['distribucion_logro']['C'] ?? 0 }}</strong> C</span>
                    </div>
                @else
                    <div class="nivel-chip-row">
                        <span class="nivel-chip" style="color:var(--success)"><strong>{{ $n['aprobados'] ?? 0 }}</strong> Aprobados</span>
                        <span class="nivel-chip" style="color:var(--warning)"><strong>{{ $n['en_riesgo'] ?? 0 }}</strong> En riesgo</span>
                        <span class="nivel-chip" style="color:var(--danger)"><strong>{{ $n['desaprobados'] ?? 0 }}</strong> Desaprobados</span>
                    </div>
                @endif

                @if(!empty($n['promedios_cursos']))
                <div class="nivel-cursos-list">
                    @foreach($n['promedios_cursos'] as $c)
                    <div class="nivel-curso-row">
                        <span>{{ $c['nombre'] }}</span>
                        @if($nivel === 'Primaria')
                            <span style="font-weight:700;color:{{ nivel_logro_color($c['nl']) }}">{{ $c['nl'] }}</span>
                        @else
                            <span class="{{ $c['promedio']>=14?'nota-alta':($c['promedio']>=11?'nota-media':'nota-baja') }}">{{ number_format($c['promedio'],1) }}</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                    <p style="font-size:.8rem;color:var(--n400);padding:.5rem 0">Sin notas registradas en este nivel aún.</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<style>
    .nivel-panel{border:1px solid var(--n200);border-radius:var(--radius);padding:1rem}
    .nivel-panel-header{display:flex;align-items:center;gap:.5rem;font-weight:800;color:var(--primary);margin-bottom:.85rem;font-size:.95rem}
    .nivel-chip-row{display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:.9rem}
    .nivel-chip{background:var(--n50);border-radius:8px;padding:.35rem .7rem;font-size:.8rem;color:var(--n600)}
    .nivel-cursos-list{border-top:1px dashed var(--n200);padding-top:.6rem}
    .nivel-curso-row{display:flex;justify-content:space-between;padding:.3rem 0;font-size:.83rem}
</style>

@if(in_array($rol,['promotor','docente','psicologa']))
<div class="card" style="margin-bottom:1rem">
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

@if($rol === 'promotor' && count($estudiantes_riesgo))
<div class="card" style="margin-top:1rem">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i> Estudiantes que Requieren Atención</div>
    </div>
    <div class="card-body" style="padding:0">
        @foreach($estudiantes_riesgo->groupBy('nivel') as $nivelNombre => $lista)
        <div style="padding:.6rem 1.1rem;background:var(--n50);font-size:.78rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.03em">{{ $nivelNombre }}</div>
        <table>
            <thead><tr><th>Estudiante</th><th>Grado</th><th>{{ $nivelNombre === 'Primaria' ? 'Nivel de logro' : 'Promedio' }}</th><th>Estado</th><th>Conductas (−)</th><th>Acciones</th></tr></thead>
            <tbody>
                @foreach($lista as $est)
                <tr>
                    <td><div class="student-cell"><div class="avatar" style="background:var(--danger-soft);color:var(--danger)">{{ strtoupper(substr($est['nombre'],0,1)) }}</div><span>{{ $est['nombre'] }}</span></div></td>
                    <td><span class="tag tag-info">{{ $est['grado'] }}</span></td>
                    <td>
                        @if($nivelNombre === 'Primaria')
                            <span style="font-weight:700;color:{{ nivel_logro_color(nivel_logro_letra($est['promedio'])) }}">{{ nivel_logro_letra($est['promedio']) }}</span>
                        @else
                            <span class="nota-baja">{{ number_format($est['promedio'],1) }}</span>
                        @endif
                    </td>
                    <td>@if($est['estado']==='desaprobado')<span class="tag tag-danger">✗ Desaprobado</span>@else<span class="tag tag-warning">⚠ En riesgo</span>@endif</td>
                    <td>@if($est['conductas']>0)<span class="tag tag-danger">{{ $est['conductas'] }}</span>@else<span style="color:var(--n400)">—</span>@endif</td>
                    <td><a href="/conductas" class="btn btn-ghost btn-sm"><i class="fas fa-eye"></i> Ver</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
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
        @foreach($alertas_asistencia->groupBy('nivel') as $nivelNombre => $lista)
        <div style="padding:.6rem 1.1rem;background:var(--n50);font-size:.78rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:.03em">{{ $nivelNombre }}</div>
        <table>
            <thead><tr><th>Estudiante</th><th>Grado</th><th>% Asistencia</th><th>Acciones</th></tr></thead>
            <tbody>
                @foreach($lista as $al)
                <tr>
                    <td><div class="student-cell"><div class="avatar" style="background:var(--danger-soft);color:var(--danger)">{{ strtoupper(substr($al['nombre'],0,1)) }}</div><span>{{ $al['nombre'] }}</span></div></td>
                    <td><span class="tag tag-info">{{ $al['grado'] }}</span></td>
                    <td><span class="tag tag-danger">{{ $al['porcentaje'] }}%</span></td>
                    <td>
                        @if($rol === 'promotor')
                        <a href="/control-asistencia" class="btn btn-ghost btn-sm"><i class="fas fa-eye"></i> Ver</a>
                        @else
                        <span style="color:var(--n400)">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    </div>
</div>
@endif
@endsection
