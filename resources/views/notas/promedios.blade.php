@extends('layouts.app')
@section('title','Promedios')
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="/notas">Notas</a>
            <span style="margin:0 .3rem">/</span>
            <span>Promedios</span>
        </div>
        <h1 class="page-title">Promedios y Estadísticas</h1>
        <p class="page-subtitle">Rendimiento académico general por curso y estudiante</p>
    </div>
</div>

{{-- Gráfica de barras --}}
<div class="card" style="margin-bottom:1rem">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-chart-bar"></i> Promedio por Curso</div>
    </div>
    <div class="card-body">
        @php
            $vals   = collect($promedios_cursos)->pluck('promedio')->toArray();
            $labels = collect($promedios_cursos)->pluck('nombre')
                        ->map(fn($n) => Str::limit($n, 12))->toArray();
        @endphp
        <canvas class="bar-chart-simple" style="width:100%;height:200px"
            data-values='{{ json_encode($vals) }}'
            data-labels='{{ json_encode($labels) }}'>
        </canvas>
    </div>
</div>

{{-- Tabla resumen por estudiante y curso --}}
@php
    // Pivotear: agrupar por estudiante
    $porEstudiante = $promedios->groupBy('id');
    $cursoNombres  = $promedios->pluck('curso')->unique()->sort()->values();
@endphp

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-table"></i> Detalle por Estudiante</div>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control" id="tableSearch" placeholder="Buscar estudiante...">
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Grado</th>
                        @foreach($cursoNombres as $cn)
                            <th style="white-space:nowrap">{{ Str::limit($cn, 10) }}</th>
                        @endforeach
                        <th style="background:var(--primary-soft);color:var(--primary)">Promedio Gral.</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($porEstudiante as $estId => $filas)
                    @php
                        $primera = $filas->first();
                        $notasPorCurso = $filas->pluck('promedio', 'curso');
                        $promGeneral   = round($filas->avg('promedio'), 1);
                        $estado = $promGeneral >= 14 ? 'aprobado' : ($promGeneral >= 11 ? 'riesgo' : 'desaprobado');
                    @endphp
                    <tr>
                        <td>
                            <div class="student-cell">
                                <div class="avatar">{{ strtoupper(substr($primera->nombre, 0, 1)) }}</div>
                                <span style="font-weight:500">{{ $primera->nombre }}</span>
                            </div>
                        </td>
                        <td><span class="tag tag-info">{{ $primera->grado ?? '—' }}</span></td>
                        @foreach($cursoNombres as $cn)
                        @php $nota = $notasPorCurso[$cn] ?? null; @endphp
                        <td>
                            @if($nota !== null)
                                <span class="{{ $nota>=14?'nota-alta':($nota>=11?'nota-media':'nota-baja') }}">
                                    {{ number_format($nota,1) }}
                                </span>
                            @else
                                <span style="color:var(--n400)">—</span>
                            @endif
                        </td>
                        @endforeach
                        <td style="background:var(--primary-soft)">
                            <strong class="{{ $promGeneral>=14?'nota-alta':($promGeneral>=11?'nota-media':'nota-baja') }}"
                                    style="font-size:1rem">
                                {{ number_format($promGeneral, 1) }}
                            </strong>
                        </td>
                        <td>
                            @if($estado==='aprobado')
                                <span class="tag tag-success">✓ Aprobado</span>
                            @elseif($estado==='riesgo')
                                <span class="tag tag-warning">⚠ Riesgo</span>
                            @else
                                <span class="tag tag-danger">✗ Desaprobado</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="display:flex;gap:1rem;margin-top:.75rem;font-size:.78rem;align-items:center;flex-wrap:wrap">
    <span style="color:var(--n400)">Escala vigesimal (0 – 20):</span>
    <span class="nota-alta">■ Aprobado (14 – 20)</span>
    <span class="nota-media">■ En riesgo (11 – 13.9)</span>
    <span class="nota-baja">■ Desaprobado (0 – 10.9)</span>
</div>

@endsection
