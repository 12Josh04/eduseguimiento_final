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
        <p class="page-subtitle">Rendimiento académico por salón y sección</p>
    </div>
</div>

{{-- Gráficas de barras, separadas por nivel (Primaria en letras, Secundaria en números) --}}
<div class="grid-2" style="margin-bottom:1rem;align-items:start">
    @foreach(['Primaria','Secundaria'] as $nivel)
    @php $cursosNivel = $promediosCursosPorNivel[$nivel] ?? collect(); @endphp
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas {{ $nivel === 'Primaria' ? 'fa-child' : 'fa-user-graduate' }}"></i>
                Promedio por Curso — {{ $nivel }}
            </div>
        </div>
        <div class="card-body">
            @if($cursosNivel->isEmpty())
                <div class="empty-state" style="padding:1.5rem"><p>Sin datos de notas aún en este nivel.</p></div>
            @else
                @php
                    $vals   = $cursosNivel->pluck('promedio')->toArray();
                    $labels = $cursosNivel->pluck('nombre')->map(fn($n) => Str::limit($n, 12))->toArray();
                @endphp
                <canvas class="bar-chart-simple" style="width:100%;height:180px"
                    data-values='{{ json_encode($vals) }}'
                    data-labels='{{ json_encode($labels) }}'>
                </canvas>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Tabla por salón y sección --}}
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-table"></i> Detalle por Salón y Sección</div>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control" id="tableSearch" placeholder="Buscar estudiante...">
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table id="tablaPromedios">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        @foreach($cursoNombres as $cn)
                            <th style="white-space:nowrap">{{ Str::limit($cn, 10) }}</th>
                        @endforeach
                        <th style="background:var(--primary-soft);color:var(--primary)">Promedio Gral.</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($secciones as $clave => $estudiantes)
                    @php
                        $primero = $estudiantes->first();
                        $esPrimaria = $primero->nivel === 'Primaria';
                        $colspan = 3 + count($cursoNombres);
                    @endphp
                    <tr>
                        <td colspan="{{ $colspan }}" style="background:var(--n50);font-weight:700;color:var(--primary);padding:.6rem 1rem">
                            <i class="fas {{ $esPrimaria ? 'fa-child' : 'fa-user-graduate' }}" style="margin-right:.4rem"></i>
                            {{ $primero->grado_nombre }} "{{ $primero->seccion }}" — {{ $primero->nivel }}
                            <span style="font-weight:400;color:var(--n400);font-size:.78rem;margin-left:.5rem">{{ $estudiantes->count() }} estudiante{{ $estudiantes->count() == 1 ? '' : 's' }}</span>
                        </td>
                    </tr>
                    @foreach($estudiantes as $est)
                    @php
                        $notasPorCurso = $est->cursos;
                        $promGeneral = $est->promedio;
                        $estado = $promGeneral === null ? null : ($promGeneral >= 14 ? 'aprobado' : ($promGeneral >= 11 ? 'riesgo' : 'desaprobado'));
                    @endphp
                    <tr data-nombre="{{ Str::lower($est->estudiante_nombre) }}">
                        <td>
                            <div class="student-cell">
                                <div class="avatar">{{ strtoupper(substr($est->estudiante_nombre, 0, 1)) }}</div>
                                <span style="font-weight:500">{{ $est->estudiante_nombre }}</span>
                            </div>
                        </td>
                        @foreach($cursoNombres as $cn)
                        @php $nota = $notasPorCurso[$cn] ?? null; @endphp
                        <td>
                            @if($nota === null)
                                <span style="color:var(--n400)">—</span>
                            @elseif($esPrimaria)
                                <span style="font-weight:700;color:{{ nivel_logro_color(nivel_logro_letra($nota)) }}">{{ nivel_logro_letra($nota) }}</span>
                            @else
                                <span class="{{ $nota>=14?'nota-alta':($nota>=11?'nota-media':'nota-baja') }}">{{ number_format($nota,1) }}</span>
                            @endif
                        </td>
                        @endforeach
                        <td style="background:var(--primary-soft)">
                            @if($promGeneral === null)
                                <span style="color:var(--n400)">—</span>
                            @elseif($esPrimaria)
                                <strong style="font-size:1rem;color:{{ nivel_logro_color(nivel_logro_letra($promGeneral)) }}">{{ nivel_logro_letra($promGeneral) }}</strong>
                            @else
                                <strong class="{{ $promGeneral>=14?'nota-alta':($promGeneral>=11?'nota-media':'nota-baja') }}" style="font-size:1rem">{{ number_format($promGeneral, 1) }}</strong>
                            @endif
                        </td>
                        <td>
                            @if($estado === 'aprobado')
                                <span class="tag tag-success">✓ Aprobado</span>
                            @elseif($estado === 'riesgo')
                                <span class="tag tag-warning">⚠ Riesgo</span>
                            @elseif($estado === 'desaprobado')
                                <span class="tag tag-danger">✗ Desaprobado</span>
                            @else
                                <span style="color:var(--n400)">Sin notas</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @empty
                    <tr><td colspan="1"><div class="empty-state" style="padding:2rem"><p>No hay estudiantes matriculados para mostrar.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="display:flex;gap:1rem;margin-top:.75rem;font-size:.78rem;align-items:center;flex-wrap:wrap">
    <span style="color:var(--n400)">Secundaria (escala vigesimal 0–20):</span>
    <span class="nota-alta">■ Aprobado (14–20)</span>
    <span class="nota-media">■ En riesgo (11–13.9)</span>
    <span class="nota-baja">■ Desaprobado (0–10.9)</span>
    <span style="color:var(--n400);margin-left:1rem">Primaria (nivel de logro):</span>
    <span style="color:{{ nivel_logro_color('AD') }}">■ AD/A</span>
    <span style="color:{{ nivel_logro_color('B') }}">■ B</span>
    <span style="color:{{ nivel_logro_color('C') }}">■ C</span>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('tableSearch')?.addEventListener('input', function () {
    const search = this.value.toLowerCase().trim();
    document.querySelectorAll('#tablaPromedios tbody tr[data-nombre]').forEach(row => {
        row.style.display = !search || row.dataset.nombre.includes(search) ? '' : 'none';
    });
});
</script>
@endpush
