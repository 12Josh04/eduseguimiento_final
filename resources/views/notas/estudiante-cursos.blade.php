@extends('layouts.app')
@section('title', $estudiante->nombre . ' ' . $estudiante->apellido)
@section('content')

@php $nombreCompleto = $estudiante->nombre . ' ' . $estudiante->apellido; @endphp

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="{{ route('notas.index') }}">Notas</a>
            @if($grado)
            <span style="margin:0 .3rem">/</span>
            <a href="{{ route('notas.index', ['grado_id' => $grado->id, 'seccion' => $seccion]) }}">{{ $grado->nombre }} "{{ $seccion }}"</a>
            @endif
            <span style="margin:0 .3rem">/</span>
            <span>{{ $nombreCompleto }}</span>
        </div>
        <h1 class="page-title"><i class="fas fa-user-graduate"></i> {{ $nombreCompleto }}</h1>
        <p class="page-subtitle">
            @if($grado){{ $grado->nombre }} "{{ $seccion }}" — @endif
            Notas por curso, clases desarrolladas y libreta
        </p>
    </div>
    <div style="display:flex;gap:.5rem">
        @if($rol === 'promotor')
        <a href="{{ route('boletas.show', $estudiante->id) }}" target="_blank" class="btn btn-ghost">
            <i class="fas fa-file-alt"></i> Ver Libreta completa
        </a>
        @endif
        <a href="{{ route('estudiantes.show', $estudiante->id) }}" class="btn btn-ghost">
            <i class="fas fa-id-card"></i> Ficha del estudiante
        </a>
    </div>
</div>

<div class="paneles-grid">
    @forelse($cursosPanel as $c)
        @php
            $colorProm = $c->promedio === null ? 'var(--n400)' : ($c->promedio >= 14 ? 'var(--success)' : ($c->promedio >= 11 ? 'var(--warning)' : 'var(--danger)'));
            $colorPct = $c->pct_clases >= 75 ? 'good' : ($c->pct_clases >= 40 ? 'medium' : 'bad');
        @endphp
        <div class="panel-grado" style="cursor:default">
            <div class="panel-grado-header">
                <span class="panel-grado-nombre"><i class="fas fa-book" style="margin-right:.4rem;color:var(--n400)"></i>{{ $c->curso_nombre }}</span>
                @if($grado)
                <a href="{{ route('notas.curso', ['curso' => $c->curso_id, 'grado_id' => $grado->id, 'seccion' => $seccion]) }}" title="Ver curso completo" style="color:var(--n400)">
                    <i class="fas fa-external-link-alt"></i>
                </a>
                @endif
            </div>
            <p style="font-size:.78rem;color:var(--n500);margin-bottom:.6rem">
                <i class="fas fa-chalkboard-teacher" style="margin-right:.3rem"></i>{{ $c->docente_nombre }}
            </p>

            <div style="margin-bottom:.7rem">
                <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--n500);margin-bottom:.2rem">
                    <span>Clases desarrolladas</span><span>{{ $c->pct_clases }}%</span>
                </div>
                <div class="progress-bar-wrap"><div class="progress-bar-fill {{ $colorPct }}" style="width:{{ $c->pct_clases }}%"></div></div>
            </div>

            <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.7rem">
                @foreach($periodos as $p)
                    @php $b = $c->bimestres[$p->id] ?? null; @endphp
                    <span class="tag" style="background:var(--n100);color:var(--n600);font-size:.7rem">
                        {{ Str::limit($p->nombre, 12) }}: {{ $b !== null ? ($c->es_primaria ? nivel_logro_letra($b) : number_format($b,1)) : '—' }}
                    </span>
                @endforeach
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem">
                <div>
                    <span style="font-size:1.4rem;font-weight:800;color:{{ $colorProm }}">{{ $c->promedio !== null ? ($c->es_primaria ? nivel_logro_letra($c->promedio) : number_format($c->promedio,1)) : '—' }}</span>
                    <span style="font-size:.72rem;color:var(--n400)"> {{ $c->es_primaria ? 'nivel de logro' : '/20 promedio' }}</span>
                </div>
                @if(in_array($rol, ['promotor','docente']))
                <button class="btn btn-primary btn-sm" onclick="abrirModalNota('{{ $c->curso_id }}', '{{ addslashes($c->curso_nombre) }}', {{ $c->es_primaria ? 'true' : 'false' }})">
                    <i class="fas fa-plus"></i> Registrar nota
                </button>
                @endif
            </div>

            @if($c->evaluaciones->isNotEmpty())
            <button type="button" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center" onclick="toggleEvaluaciones('{{ $c->curso_id }}')">
                <i class="fas fa-list" style="margin-right:.3rem"></i> Ver las {{ $c->evaluaciones->count() }} evaluaciones
            </button>
            <div id="evals-{{ $c->curso_id }}" style="display:none;margin-top:.6rem;border-top:1px dashed var(--n200);padding-top:.6rem">
                <table style="width:100%;font-size:.78rem">
                    <thead>
                        <tr style="color:var(--n400);text-align:left">
                            <th style="padding-bottom:.3rem">Evaluación</th><th>Peso</th><th>Nota</th><th>Bimestre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($c->evaluaciones as $ev)
                        <tr>
                            <td style="padding:.2rem 0">{{ Str::limit($ev->nombre_evaluacion, 22) }}</td>
                            <td>×{{ number_format($ev->peso, 1) }}</td>
                            @if($ev->nota_letra)
                                <td style="font-weight:700;color:{{ nivel_logro_color($ev->nota_letra) }}">{{ $ev->nota_letra }}</td>
                            @else
                                <td class="{{ $ev->nota>=14?'nota-alta':($ev->nota>=11?'nota-media':'nota-baja') }}" style="font-weight:700">{{ number_format($ev->nota,1) }}</td>
                            @endif
                            <td style="color:var(--n400)">{{ Str::limit($ev->periodo_nombre ?? '—', 10) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    @empty
        <div class="card" style="grid-column:1/-1"><div class="card-body">
            <div class="empty-state" style="padding:2rem"><p>No hay cursos asignados para este estudiante.</p></div>
        </div></div>
    @endforelse
</div>

<style>
    .paneles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem}
    .panel-grado{background:var(--surface);border:1px solid var(--n200);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:1.1rem}
</style>

{{-- Modal registrar nota rápida (pre-llenado con estudiante + curso) --}}
@if(in_array($rol, ['promotor','docente']))
<div class="modal-overlay" id="modalNotaRapida">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-plus-circle" style="color:var(--primary-light);margin-right:.4rem"></i> Registrar Nota — <span id="notaRapidaCurso"></span></div>
            <button class="modal-close" type="button" onclick="cerrarModalNota()"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('notas.store') }}" method="POST">
            @csrf
            <input type="hidden" name="estudiante_id" value="{{ $estudiante->id }}">
            <input type="hidden" name="curso_id" id="notaRapidaCursoId">
            <div class="modal-body">
                <div style="padding:.75rem;background:var(--primary-soft);border-radius:8px;margin-bottom:1rem;font-size:.85rem">
                    <i class="fas fa-user-graduate" style="color:var(--primary-light);margin-right:.4rem"></i>Estudiante: <strong>{{ $nombreCompleto }}</strong>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Periodo *</label>
                        <select name="periodo_id" class="form-control" required>
                            @foreach($periodos as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de Evaluación *</label>
                        <select name="tipo_evaluacion" class="form-control" required onchange="sugerirPeso(this, 'pesoNotaRapida')">
                            <option value="examen" data-peso="2">Examen</option>
                            <option value="tarea" data-peso="1" selected>Tarea</option>
                            <option value="practica" data-peso="1.5">Práctica calificada</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre de la Evaluación *</label>
                    <input type="text" name="nombre_evaluacion" class="form-control" placeholder="Ej: Examen Parcial I" required>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group" id="grupoNotaRapidaNumero">
                        <label class="form-label">Nota Obtenida *</label>
                        <input type="number" name="nota" id="inputNotaRapidaNumero" class="form-control" min="0" max="20" step="0.5">
                    </div>
                    <div class="form-group" id="grupoNotaRapidaLetra" style="display:none">
                        <label class="form-label">Nivel de Logro *</label>
                        <select name="nota_letra" id="inputNotaRapidaLetra" class="form-control">
                            <option value="">— Seleccionar —</option>
                            <option value="AD">AD — Logro destacado</option>
                            <option value="A">A — Logro esperado</option>
                            <option value="B">B — En proceso</option>
                            <option value="C">C — En inicio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha *</label>
                        <input type="date" name="fecha" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Peso de esta evaluación</label>
                    <input type="number" name="peso" id="pesoNotaRapida" class="form-control" value="1" min="0.1" max="10" step="0.1">
                </div>
                <input type="hidden" name="nota_maxima" value="20">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalNota()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Nota</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function sugerirPeso(select, inputId) {
    const opt = select.options[select.selectedIndex];
    if (opt.dataset.peso) document.getElementById(inputId).value = opt.dataset.peso;
}
function toggleEvaluaciones(cursoId) {
    const el = document.getElementById('evals-' + cursoId);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function abrirModalNota(cursoId, cursoNombre, esPrimaria) {
    document.getElementById('notaRapidaCursoId').value = cursoId;
    document.getElementById('notaRapidaCurso').textContent = cursoNombre;
    document.getElementById('grupoNotaRapidaNumero').style.display = esPrimaria ? 'none' : '';
    document.getElementById('grupoNotaRapidaLetra').style.display = esPrimaria ? '' : 'none';
    document.getElementById('inputNotaRapidaNumero').required = !esPrimaria;
    document.getElementById('inputNotaRapidaLetra').required = esPrimaria;
    document.getElementById('modalNotaRapida').style.display = 'flex';
}
function cerrarModalNota() {
    document.getElementById('modalNotaRapida').style.display = 'none';
}
document.getElementById('modalNotaRapida')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalNota();
});
</script>
@endpush
