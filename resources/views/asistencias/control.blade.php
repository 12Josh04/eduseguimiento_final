@extends('layouts.app')
@section('title','Control de Asistencia')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Control de Asistencia</span></div>
        <h1 class="page-title"><i class="fas fa-clipboard-check"></i> Control de Asistencia</h1>
        <p class="page-subtitle">Resumen institucional de asistencia por estudiante</p>
    </div>
    <a href="{{ route('asistencias.control', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-ghost">
        <i class="fas fa-file-csv"></i> Exportar CSV
    </a>
</div>

<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.25rem">
    <div class="card"><div class="card-body">
        <div style="font-size:1.6rem;font-weight:800;color:var(--danger)">{{ $totalFaltas }}</div>
        <div style="color:var(--n500);font-size:.85rem">Total de faltas registradas</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:1.6rem;font-weight:800;color:var(--warning)">{{ $totalTardanzas }}</div>
        <div style="color:var(--n500);font-size:.85rem">Total de tardanzas registradas</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:1.6rem;font-weight:800;color:var(--danger)">{{ $enRiesgo }}</div>
        <div style="color:var(--n500);font-size:.85rem">Estudiantes con asistencia menor a 85%</div>
    </div></div>
</div>

<div class="card" style="margin-bottom:1rem">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:.75rem;align-items:flex-end">
            <div>
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">Grado</label>
                <select name="grado_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos los grados</option>
                    @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => $gradoId])
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-users"></i> Asistencia por estudiante</div>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Estudiante</th><th>Grado</th><th>Sección</th>
                    <th>Presentes</th><th>Faltas</th><th>Tardanzas</th><th>Justificadas</th>
                    <th>% Asistencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resumen as $r)
                <tr>
                    <td>{{ $r->nombre }}</td>
                    <td>{{ $r->grado ?? '—' }}</td>
                    <td>{{ $r->seccion ?? '—' }}</td>
                    <td>{{ $r->presentes }}</td>
                    <td>{{ $r->faltas }}</td>
                    <td>{{ $r->tardanzas }}</td>
                    <td>{{ $r->justificadas }}</td>
                    <td>
                        @if($r->porcentaje_asistencia === null)
                            <span style="color:var(--n400)">Sin registros</span>
                        @else
                            <span class="badge" style="background:{{ $r->porcentaje_asistencia < 85 ? 'var(--danger-soft)' : 'var(--success-soft)' }};color:{{ $r->porcentaje_asistencia < 85 ? 'var(--danger)' : 'var(--success)' }};padding:.2rem .6rem;border-radius:6px;font-weight:700">
                                {{ $r->porcentaje_asistencia }}%
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8"><div class="empty-state" style="padding:2rem"><p>No hay registros de asistencia todavía.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
