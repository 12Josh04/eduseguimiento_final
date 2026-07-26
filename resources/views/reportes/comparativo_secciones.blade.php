@extends('layouts.app')
@section('title','Comparativo entre Secciones')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Comparativo entre Secciones</span></div>
        <h1 class="page-title"><i class="fas fa-balance-scale"></i> Comparativo entre Secciones</h1>
        <p class="page-subtitle">Compara el rendimiento académico, conductual y de asistencia entre las secciones de un mismo grado</p>
    </div>
</div>

<div class="card" style="margin-bottom:1rem">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:.75rem;align-items:flex-end">
            <div>
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">Grado</label>
                <select name="grado_id" class="form-control" onchange="this.form.submit()">
                    @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => $gradoId])
                </select>
            </div>
        </form>
    </div>
</div>

@php $gradoNombre = optional($grados->firstWhere('id', $gradoId))->nombre; @endphp

<div class="card" style="margin-bottom:1rem">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Promedio general — {{ $gradoNombre }}</div></div>
    <div class="card-body">
        @php
            $vals = $comparativo->pluck('promedio')->map(fn($v) => $v ?? 0)->toArray();
            $labels = $comparativo->map(fn($c) => '"'.$c->seccion.'"')->toArray();
        @endphp
        <canvas class="bar-chart-simple" style="width:100%;height:200px"
            data-values='{{ json_encode($vals) }}'
            data-labels='{{ json_encode($labels) }}'></canvas>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-table"></i> Detalle por sección</div></div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Sección</th><th>Estudiantes</th><th>Promedio</th><th>Nivel de logro</th>
                    <th>% Notas aprobadas</th><th>% Asistencia</th><th>Conductas negativas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comparativo as $c)
                <tr>
                    <td><strong>{{ $gradoNombre }} "{{ $c->seccion }}"</strong></td>
                    <td>{{ $c->total_estudiantes }}</td>
                    <td>{{ $c->promedio ?? '—' }}</td>
                    <td>
                        @if($c->nl)
                            <span style="font-weight:800;color:{{ nivel_logro_color($c->nl) }}">{{ $c->nl }}</span>
                        @else
                            <span style="color:var(--n400)">—</span>
                        @endif
                    </td>
                    <td>{{ $c->pct_aprobados !== null ? $c->pct_aprobados.'%' : '—' }}</td>
                    <td>{{ $c->pct_asistencia !== null ? $c->pct_asistencia.'%' : '—' }}</td>
                    <td>
                        @if($c->conductas_neg > 0)
                            <span class="tag tag-danger">{{ $c->conductas_neg }}</span>
                        @else
                            <span style="color:var(--n400)">0</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="empty-state" style="padding:2rem"><p>Este grado no tiene secciones configuradas.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
