@extends('layouts.app')
@section('title','Notas')
@section('content')

@php $rol = Auth::user()->rol; @endphp

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <span>{{ $rol === 'docente' ? 'Mis Notas' : 'Notas' }}</span>
        </div>
        <h1 class="page-title">
            {{ $rol === 'docente' ? 'Mis Notas' : ($rol === 'estudiante' ? 'Mis Calificaciones' : 'Registro de Notas') }}
        </h1>
        <p class="page-subtitle">
            @if($vista === 'paneles')
                Explora por grado y sección; luego por curso, para ver clases desarrolladas y notas de trabajo
            @elseif($vista === 'cursos')
                {{ optional($grados->firstWhere('id', request('grado_id')))->nombre }} "{{ request('seccion') }}" — selecciona un curso
            @elseif($rol === 'docente')
                Solo los cursos que tienes asignados
            @elseif($rol === 'estudiante')
                Tus calificaciones de este año escolar
            @else
                Gestión de evaluaciones y calificaciones
            @endif
        </p>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($vista === 'cursos')
            <a href="{{ route('notas.index') }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Volver a paneles</a>
        @endif
        @if($vista === 'lista' && in_array($rol, ['promotor','docente']))
        <button class="btn btn-primary" data-modal-open="modalNota">
            <i class="fas fa-plus"></i> Registrar Nota
        </button>
        @endif
    </div>
</div>

@if($vista === 'paneles')
{{-- ── Nivel 1: Paneles por Grado + Sección ─────────────────────────────── --}}
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
                        $prom = $p->promedio_seccion;
                        $colorProm = $prom === null ? 'var(--n400)' : ($prom >= 14 ? 'var(--success)' : ($prom >= 11 ? 'var(--warning)' : 'var(--danger)'));
                    @endphp
                    <a href="{{ route('notas.index', ['grado_id' => $p->grado_id, 'seccion' => $p->seccion]) }}" class="panel-grado">
                        <div class="panel-grado-header">
                            <span class="panel-grado-nombre">{{ $p->grado_nombre }} "{{ $p->seccion }}"</span>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <div class="panel-grado-body">
                            <div class="panel-stat">
                                <span class="panel-stat-num">{{ $p->total_estudiantes }}</span>
                                <span class="panel-stat-label">estudiante{{ $p->total_estudiantes == 1 ? '' : 's' }}</span>
                            </div>
                            <div class="panel-stat">
                                <span class="panel-stat-num" style="color:{{ $colorProm }}">{{ $prom !== null ? number_format($prom, 1) : '—' }}</span>
                                <span class="panel-stat-label">promedio</span>
                            </div>
                            <div class="panel-stat">
                                <span class="panel-stat-num">{{ $p->total_notas }}</span>
                                <span class="panel-stat-label">notas</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body">
            <div class="empty-state" style="padding:2rem"><p>No hay grados ni secciones configurados todavía.</p></div>
        </div></div>
    @endforelse
</div>

@elseif($vista === 'cursos')
{{-- ── Nivel 2: Cursos dictados en esta sección ─────────────────────────── --}}
<div class="paneles-grid">
    @forelse($cursosSeccion as $c)
        <a href="{{ route('notas.curso', ['curso' => $c->curso_id, 'grado_id' => request('grado_id'), 'seccion' => request('seccion')]) }}" class="panel-grado">
            <div class="panel-grado-header">
                <span class="panel-grado-nombre"><i class="fas fa-book" style="margin-right:.4rem;color:var(--n400)"></i>{{ $c->curso_nombre }}</span>
                <i class="fas fa-chevron-right"></i>
            </div>
            <p style="font-size:.78rem;color:var(--n500);margin-bottom:.6rem">
                <i class="fas fa-chalkboard-teacher" style="margin-right:.3rem"></i>{{ $c->docente_nombre }}
            </p>
            <div class="panel-grado-body">
                <div class="panel-stat">
                    <span class="panel-stat-num">{{ $c->total_clases }}</span>
                    <span class="panel-stat-label">clases</span>
                </div>
                <div class="panel-stat">
                    <span class="panel-stat-num">{{ $c->total_notas }}</span>
                    <span class="panel-stat-label">notas</span>
                </div>
                <div class="panel-stat">
                    <span class="panel-stat-num" style="color:{{ $c->promedio === null ? 'var(--n400)' : ($c->promedio >= 14 ? 'var(--success)' : ($c->promedio >= 11 ? 'var(--warning)' : 'var(--danger)')) }}">
                        {{ $c->promedio !== null ? number_format($c->promedio,1) : '—' }}
                    </span>
                    <span class="panel-stat-label">promedio</span>
                </div>
            </div>
        </a>
    @empty
        <div class="card" style="grid-column:1/-1"><div class="card-body">
            <div class="empty-state" style="padding:2rem"><p>No hay cursos asignados en este grado.</p></div>
        </div></div>
    @endforelse
</div>

@else
{{-- ── Ficha de alumno / listado plano (comportamiento original) ─────────── --}}
@if(!empty($sinCursos))
<div class="card">
    <div class="card-body">
        <div class="empty-state" style="padding:3rem">
            <div class="empty-state-icon" style="background:var(--warning-soft);color:var(--warning)">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="margin-bottom:.5rem">Sin cursos asignados</h3>
            <p>Aún no tienes cursos asignados en el sistema.<br>
               Contacta al promotor para que configure tus asignaciones.</p>
        </div>
    </div>
</div>
@else

{{-- Filtros --}}
<div class="filter-bar">
    <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" class="form-control" id="tableSearch" placeholder="Buscar estudiante...">
    </div>

    <select class="form-control" style="min-width:160px" id="filtroCurso">
        <option value="">
            @if($rol === 'docente') Todos mis cursos @else Todos los cursos @endif
        </option>
        @foreach($cursos as $c)
            <option value="{{ $c->nombre }}">{{ $c->nombre }}</option>
        @endforeach
    </select>

    <select class="form-control" style="min-width:140px" id="filtroTipo">
        <option value="">Tipo evaluación</option>
        <option value="examen">Examen</option>
        <option value="tarea">Tarea</option>
        <option value="practica">Práctica</option>
    </select>

    <select class="form-control" style="min-width:140px" id="filtroPeriodo">
        <option value="">Todos los periodos</option>
        @foreach($periodos as $p)
            <option value="{{ $p->nombre }}">{{ $p->nombre }}</option>
        @endforeach
    </select>

    <button class="btn btn-ghost btn-sm" id="btnLimpiar">
        <i class="fas fa-times"></i> Limpiar
    </button>

    @if(!request('estudiante'))
    <a href="{{ route('notas.index') }}" class="btn btn-ghost btn-sm">
        <i class="fas fa-arrow-left"></i> Volver a paneles
    </a>
    @endif
</div>

{{-- Tabla --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-clipboard-list"></i>
            @if($rol === 'docente') Mis Calificaciones Registradas
            @elseif($rol === 'estudiante') Mis Calificaciones
            @else Calificaciones Registradas
            @endif
        </div>
        <span style="font-size:.8rem;color:var(--n400)">{{ $notas->total() }} registros</span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table id="tablaNotas">
                <thead>
                    <tr>
                        @if($rol !== 'estudiante')<th>Estudiante</th>@endif
                        <th>Curso</th>
                        <th>Tipo</th>
                        <th>Evaluación</th>
                        <th>Nota</th>
                        <th>%</th>
                        <th>Periodo</th>
                        <th>Fecha</th>
                        @if(in_array($rol, ['promotor','docente']))<th>Acciones</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($notas as $n)
                    @php $pct = round(($n->nota / $n->nota_maxima) * 100); @endphp
                    <tr data-curso="{{ $n->curso_nombre }}"
                        data-tipo="{{ $n->tipo_evaluacion }}"
                        data-periodo="{{ $n->periodo_nombre }}">

                        @if($rol !== 'estudiante')
                        <td>
                            <div class="student-cell">
                                <div class="avatar">{{ strtoupper(substr($n->estudiante_nombre, 0, 1)) }}</div>
                                <span style="font-weight:500">{{ $n->estudiante_nombre }}</span>
                            </div>
                        </td>
                        @endif

                        <td><span class="tag tag-info">{{ $n->curso_nombre }}</span></td>
                        <td>
                            <span class="tag {{ $n->tipo_evaluacion==='examen' ? 'tag-danger' : ($n->tipo_evaluacion==='tarea' ? 'tag-success' : 'tag-warning') }}">
                                {{ ucfirst($n->tipo_evaluacion) }}
                            </span>
                        </td>
                        <td style="font-size:.84rem;max-width:180px">
                            {{ Str::limit($n->nombre_evaluacion, 40) }}
                        </td>
                        <td>
                            <span class="nota-val {{ $n->nota>=14 ? 'nota-alta' : ($n->nota>=11 ? 'nota-media' : 'nota-baja') }}"
                                  style="font-size:1rem;font-weight:700">
                                {{ number_format($n->nota, 1) }}
                            </span>
                            <span style="color:var(--n400);font-size:.78rem"> /{{ $n->nota_maxima }}</span>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.4rem">
                                <div class="progress-bar-wrap" style="width:48px">
                                    <div class="progress-bar-fill {{ $n->nota>=14?'good':($n->nota>=11?'medium':'bad') }}"
                                         style="width:{{ $pct }}%"></div>
                                </div>
                                <span style="font-size:.75rem;color:var(--n400)">{{ $pct }}%</span>
                            </div>
                        </td>
                        <td style="font-size:.8rem;color:var(--n400)">{{ $n->periodo_nombre }}</td>
                        <td style="font-size:.8rem;color:var(--n400);white-space:nowrap">
                            {{ \Carbon\Carbon::parse($n->fecha)->format('d/m/Y') }}
                        </td>

                        @if(in_array($rol, ['promotor','docente']))
                        <td>
                            <form action="/notas/{{ $n->id }}" method="POST" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="btn btn-ghost btn-sm btn-icon-only"
                                    style="color:var(--danger)"
                                    data-confirm="¿Eliminar esta nota?"
                                    title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state" style="padding:2rem">
                                <div class="empty-state-icon"><i class="fas fa-clipboard-list"></i></div>
                                <p>
                                    @if($rol === 'docente') Aún no has registrado notas en tus cursos.
                                    @elseif($rol === 'estudiante') Aún no tienes calificaciones registradas.
                                    @else Sin notas registradas.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($notas->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--n200)">
            {{ $notas->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Modal registrar nota (solo promotor y docente) --}}
@if(in_array($rol, ['promotor','docente']))
<div class="modal-overlay" id="modalNota">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-plus-circle" style="color:var(--primary-light);margin-right:.4rem"></i>
                Registrar Nota
            </div>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <form action="/notas" method="POST">
            @csrf
            <div class="modal-body">

                {{-- Alerta de restricción para docente --}}
                @if($rol === 'docente')
                <div style="padding:.75rem 1rem;background:var(--primary-soft);border-radius:8px;
                            margin-bottom:1rem;font-size:.84rem;color:var(--primary);
                            display:flex;align-items:center;gap:.5rem">
                    <i class="fas fa-info-circle"></i>
                    Solo puedes registrar notas en tus cursos asignados y para tus alumnos.
                </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Grado *</label>
                    <select id="selectGrado" name="grado_id" class="form-control" required>
                        <option value="">— Seleccionar grado —</option>
                        @include('partials.grado-options', ['grados' => $grados])
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Estudiante *</label>
                    <select id="selectEstudiante" name="estudiante_id" class="form-control" required disabled>
                        <option value="">— Primero selecciona un grado —</option>
                    </select>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Curso *</label>
                        <select name="curso_id" class="form-control" required>
                            <option value="">— Seleccionar —</option>
                            @foreach($cursos as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Periodo *</label>
                        <select name="periodo_id" class="form-control" required>
                            @foreach($periodos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Tipo de Evaluación *</label>
                        <select name="tipo_evaluacion" class="form-control" required>
                            <option value="examen">Examen</option>
                            <option value="tarea">Tarea</option>
                            <option value="practica">Práctica calificada</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha *</label>
                        <input type="date" name="fecha" class="form-control"
                               value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nombre de la Evaluación *</label>
                    <input type="text" name="nombre_evaluacion" class="form-control"
                           placeholder="Ej: Examen Parcial I, Práctica N°2..." required>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Nota Obtenida *</label>
                        <input type="number" name="nota" class="form-control"
                               min="0" max="20" step="0.5" placeholder="0 — 20" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nota Máxima</label>
                        <input type="number" name="nota_maxima" class="form-control"
                               value="20" min="1" max="100">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-ghost modal-close">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Nota
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@endif {{-- fin !sinCursos --}}
@endif {{-- fin $vista --}}

<style>
    .nivel-bloque{margin-bottom:1.75rem}
    .nivel-titulo{font-size:1rem;font-weight:800;color:var(--primary);margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
    .paneles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.85rem}
    .panel-grado{background:var(--surface);border:1px solid var(--n200);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:1rem;text-decoration:none;color:inherit;transition:var(--t);display:block}
    .panel-grado:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);border-color:var(--primary-light)}
    .panel-grado-header{display:flex;justify-content:space-between;align-items:center;font-weight:700;color:var(--primary);margin-bottom:.6rem}
    .panel-grado-header i{color:var(--n400);font-size:.8rem}
    .panel-grado-body{display:flex;gap:1rem}
    .panel-stat{display:flex;flex-direction:column}
    .panel-stat-num{font-size:1.2rem;font-weight:800;color:var(--n800)}
    .panel-stat-label{font-size:.7rem;color:var(--n500);text-transform:uppercase;letter-spacing:.03em}
</style>

@endsection

@push('scripts')
<script>
// ── Filtros de tabla ────────────────────────────────────────────────────
function aplicarFiltros() {
    const curso   = document.getElementById('filtroCurso')?.value   ?? '';
    const tipo    = document.getElementById('filtroTipo')?.value    ?? '';
    const periodo = document.getElementById('filtroPeriodo')?.value ?? '';
    const search  = document.getElementById('tableSearch')?.value.toLowerCase() ?? '';

    document.querySelectorAll('#tablaNotas tbody tr').forEach(row => {
        const okCurso   = !curso   || row.dataset.curso   === curso;
        const okTipo    = !tipo    || row.dataset.tipo    === tipo;
        const okPeriodo = !periodo || row.dataset.periodo === periodo;
        const okSearch  = !search  || row.textContent.toLowerCase().includes(search);
        row.style.display = (okCurso && okTipo && okPeriodo && okSearch) ? '' : 'none';
    });
}

['filtroCurso', 'filtroTipo', 'filtroPeriodo', 'tableSearch'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.addEventListener('input',  aplicarFiltros);
        el.addEventListener('change', aplicarFiltros);
    }
});

document.getElementById('btnLimpiar')?.addEventListener('click', function () {
    ['filtroCurso', 'filtroTipo', 'filtroPeriodo', 'tableSearch'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.querySelectorAll('#tablaNotas tbody tr').forEach(r => r.style.display = '');
});

// ── AJAX: cargar alumnos al seleccionar grado ────────────────────────────
const selectGrado      = document.getElementById('selectGrado');
const selectEstudiante = document.getElementById('selectEstudiante');

if (selectGrado && selectEstudiante) {
    selectGrado.addEventListener('change', function () {
        const gradoId = this.value;

        selectEstudiante.innerHTML = '<option value="">— Cargando... —</option>';
        selectEstudiante.disabled = true;

        if (!gradoId) {
            selectEstudiante.innerHTML = '<option value="">— Primero selecciona un grado —</option>';
            return;
        }

        fetch('/notas/estudiantes-por-grado?grado_id=' + encodeURIComponent(gradoId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            selectEstudiante.innerHTML = '<option value="">— Seleccionar alumno —</option>';
            if (data.length === 0) {
                selectEstudiante.innerHTML = '<option value="">Sin alumnos en este grado</option>';
            } else {
                data.forEach(est => {
                    const opt = document.createElement('option');
                    opt.value = est.id;
                    opt.textContent = est.nombre;
                    selectEstudiante.appendChild(opt);
                });
                selectEstudiante.disabled = false;
            }
        })
        .catch(() => {
            selectEstudiante.innerHTML = '<option value="">Error al cargar alumnos</option>';
        });
    });
}
</script>
@endpush
