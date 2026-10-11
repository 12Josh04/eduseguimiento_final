@extends('layouts.app')
@section('title', 'Estudiantes')
@section('content')

@php
    $rol = Auth::user()->rol ?? 'promotor';
@endphp

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <span>{{ $rol === 'docente' ? 'Mis Alumnos' : 'Estudiantes' }}</span>
        </div>
        <h1 class="page-title">
            {{ $rol === 'docente' ? 'Mis Alumnos' : 'Estudiantes' }}
        </h1>
        <p class="page-subtitle">
            @if($rol === 'docente')
                Alumnos matriculados en tus grados asignados
            @else
                Seguimiento académico y conductual
            @endif
        </p>
    </div>
    @if($rol === 'promotor')
        <button class="btn btn-primary" data-modal-open="modalEstudiante">
            <i class="fas fa-user-plus"></i> Nuevo Estudiante
        </button>
    @endif
</div>

{{-- Sin grados asignados (docente) --}}
@if(!empty($sinGrados))
<div class="card">
    <div class="card-body">
        <div class="empty-state" style="padding:3rem">
            <div class="empty-state-icon" style="background:var(--warning-soft);color:var(--warning)">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="margin-bottom:.5rem">Sin grados asignados</h3>
            <p>Aún no tienes grados ni cursos asignados.<br>
               Contacta al promotor para configurar tus asignaciones.</p>
        </div>
    </div>
</div>
@else

{{-- Filtros conectados al servidor --}}
<form method="GET" action="{{ route('estudiantes.index') }}" id="filtroForm">
    <div class="filter-bar">
        <label style="font-size:.78rem;font-weight:700;color:var(--n400)">
            <i class="fas fa-filter"></i> Filtrar:
        </label>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input
                type="text"
                name="search"
                class="form-control"
                id="tableSearch"
                placeholder="Buscar estudiante..."
                value="{{ request('search') }}"
                autocomplete="off"
            >
        </div>
        <select name="nivel" class="form-control" style="min-width:150px" onchange="this.form.submit()">
            <option value="">Todos los niveles</option>
            <option value="Primaria" {{ request('nivel') === 'Primaria' ? 'selected' : '' }}>📘 Primaria</option>
            <option value="Secundaria" {{ request('nivel') === 'Secundaria' ? 'selected' : '' }}>🎓 Secundaria</option>
        </select>
        <select name="grado_id" class="form-control" style="min-width:180px" onchange="this.form.submit()">
            <option value="">Todos los grados</option>
            @include('partials.grado-options', ['grados' => $rol === 'docente' ? $grados->whereIn('id', $gradoIdsPermitidos ?? []) : $grados, 'seleccionado' => request('grado_id')])
        </select>
        @if($rol !== 'psicologa')
        <select class="form-control" style="min-width:150px" onchange="filtrarEstado(this.value)">
            <option value="">Todos los estados</option>
            <option value="aprobado" {{ request('estado') === 'aprobado' ? 'selected' : '' }}>✓ Aprobado</option>
            <option value="riesgo" {{ request('estado') === 'riesgo' ? 'selected' : '' }}>⚠ En riesgo</option>
            <option value="desaprobado" {{ request('estado') === 'desaprobado' ? 'selected' : '' }}>✗ Desaprobado</option>
        </select>
        @endif
        <button type="submit" class="btn btn-ghost btn-sm" style="white-space:nowrap">
            <i class="fas fa-search"></i> Buscar
        </button>
        @if(request('search') || request('grado_id') || request('estado') || request('nivel'))
            <a href="{{ route('estudiantes.index') }}" class="btn btn-ghost btn-sm" title="Limpiar filtros">
                <i class="fas fa-times"></i> Limpiar
            </a>
        @endif
    </div>
</form>

@if($vista === 'paneles')
{{-- Paneles por Nivel → Grado → Sección --}}
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
                    <a href="{{ route('estudiantes.index', ['grado_id' => $p->grado_id, 'seccion' => $p->seccion]) }}" class="panel-grado">
                        <div class="panel-grado-header">
                            <span class="panel-grado-nombre">{{ $p->grado_nombre }} "{{ $p->seccion }}"</span>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <div class="panel-grado-body">
                            <div class="panel-stat">
                                <span class="panel-stat-num">{{ $p->total_estudiantes }}</span>
                                <span class="panel-stat-label">estudiante{{ $p->total_estudiantes == 1 ? '' : 's' }}</span>
                            </div>
                            @if($rol !== 'psicologa')
                            <div class="panel-stat">
                                <span class="panel-stat-num" style="color:{{ $colorProm }}">
                                    @if($prom === null) —
                                    @elseif($nivel === 'Primaria') {{ nivel_logro_letra($prom) }}
                                    @else {{ number_format($prom, 1) }}
                                    @endif
                                </span>
                                <span class="panel-stat-label">promedio</span>
                            </div>
                            @endif
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
{{-- Tabla --}}
<div style="display:flex;justify-content:space-between;margin-bottom:.75rem">
    <a href="{{ route('estudiantes.index') }}" class="btn btn-ghost btn-sm">
        <i class="fas fa-arrow-left"></i> Volver a paneles
    </a>
    <a href="{{ route('estudiantes.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-ghost btn-sm">
        <i class="fas fa-file-csv"></i> Exportar CSV
    </a>
</div>
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fas fa-list"></i>
            {{ $rol === 'docente' ? 'Mis Alumnos' : 'Lista de Estudiantes' }}
            @if(request('grado_id') && request('seccion'))
                <span style="font-weight:400;color:var(--n500)">— {{ optional($grados->firstWhere('id', request('grado_id')))->nombre }} "{{ request('seccion') }}"</span>
            @endif
        </div>
        <span style="font-size:.8rem;color:var(--n400)">
            {{ $estudiantes->total() }} estudiante(s)
        </span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Código</th>
                        <th>Grado</th>
                        @if($rol !== 'psicologa')
                        <th>Promedio</th>
                        <th>Estado</th>
                        @endif
                        <th>Conducta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estudiantes as $est)
                    @php
                        $prom = $est->promedio ?? 0;
                        $estado = $prom >= 14 ? 'aprobado' : ($prom >= 11 ? 'riesgo' : 'desaprobado');
                        $nombre = trim($est->nombre . ' ' . $est->apellido);
                        $initial = $est->nombre ? strtoupper(substr($est->nombre, 0, 1)) : '?';
                    @endphp
                    <tr data-estado="{{ $estado }}" data-grado="{{ $est->grado }}">
                        <td>
                            <div class="student-cell">
                                <div class="avatar">{{ $initial }}</div>
                                <span style="font-weight:500">{{ $nombre }}</span>
                            </div>
                        </td>
                        <td style="font-family:monospace;font-size:.82rem;color:var(--n400)">
                            {{ $est->codigo ?? '—' }}
                        </td>
                        <td>
                            <span class="tag tag-info">{{ $est->grado ?? '—' }}</span>
                            @if($est->nivel)
                                <span class="tag" style="background:var(--n100);color:var(--n600);font-size:.68rem"><i class="fas {{ $est->nivel === 'Primaria' ? 'fa-child' : 'fa-user-graduate' }}"></i> {{ $est->nivel }}</span>
                            @endif
                        </td>
                        @if($rol !== 'psicologa')
                        <td>
                            @if($est->nivel === 'Primaria')
                                <span style="font-weight:700;color:{{ nivel_logro_color(nivel_logro_letra($prom)) }}">{{ nivel_logro_letra($prom) }}</span>
                            @else
                                <span class="{{ $prom>=14?'nota-alta':($prom>=11?'nota-media':'nota-baja') }}">
                                    {{ number_format($prom, 1) }}
                                </span>
                                <span style="color:var(--n400);font-size:.78rem"> /20</span>
                            @endif
                        </td>
                        <td>
                            @if($estado === 'aprobado')
                                <span class="tag tag-success">✓ Aprobado</span>
                            @elseif($estado === 'riesgo')
                                <span class="tag tag-warning">⚠ En riesgo</span>
                            @else
                                <span class="tag tag-danger">✗ Desaprobado</span>
                            @endif
                        </td>
                        @endif
                        <td>
                            <div style="display:flex;gap:.3rem">
                                @if($est->conductas_pos > 0)
                                    <span class="tag tag-success">+{{ $est->conductas_pos }}</span>
                                @endif
                                @if($est->conductas_neg > 0)
                                    <span class="tag tag-danger">−{{ $est->conductas_neg }}</span>
                                @endif
                                @if(!$est->conductas_pos && !$est->conductas_neg)
                                    <span style="color:var(--n400)">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:.3rem;align-items:center">
                                <a href="{{ route('estudiantes.show', $est->id) }}"
                                   class="btn btn-ghost btn-sm btn-icon-only" title="Ver perfil">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($rol !== 'psicologa')
                                <a href="{{ route('notas.index', ['estudiante' => $est->id]) }}"
                                   class="btn btn-ghost btn-sm btn-icon-only" title="Ver notas">
                                    <i class="fas fa-clipboard-list"></i>
                                </a>
                                @endif
                                @if(in_array($rol, ['promotor','docente','psicologa']))
                                    <a href="{{ route('conductas.index', ['estudiante' => $est->id]) }}"
                                       class="btn btn-ghost btn-sm btn-icon-only" title="Ver conductas">
                                        <i class="fas fa-heart"></i>
                                    </a>
                                @endif
                                @if($rol === 'promotor')
                                    <a href="{{ route('estudiantes.edit', $est->id) }}"
                                       class="btn btn-ghost btn-sm btn-icon-only" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-sm btn-icon-only"
                                        style="color:var(--danger)"
                                        title="Eliminar estudiante"
                                        onclick="confirmarEliminar('{{ $est->id }}', '{{ addslashes($nombre) }}')"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $rol !== 'psicologa' ? 7 : 5 }}">
                            <div class="empty-state" style="padding:2rem">
                                <div class="empty-state-icon"><i class="fas fa-user-graduate"></i></div>
                                <p>
                                    @if($rol === 'docente')
                                        No hay alumnos en tus grados asignados.
                                    @else
                                        No hay estudiantes registrados.
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($estudiantes->hasPages())
            <div style="padding:1rem 1.25rem;border-top:1px solid var(--n200)">
                {{ $estudiantes->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endif {{-- fin $vista --}}

@endif {{-- fin !sinGrados --}}

{{-- Modal de confirmación de eliminación --}}
<div class="modal-overlay" id="modalEliminarEstudiante" style="display:none">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-exclamation-triangle" style="color:var(--danger);margin-right:.4rem"></i>
                Confirmar eliminación
            </div>
            <button class="modal-close" type="button" onclick="cerrarModalEliminar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="padding:.75rem;background:var(--danger-soft);border-radius:8px;margin-bottom:1rem;font-size:.85rem">
                <i class="fas fa-user-graduate" style="color:var(--danger);margin-right:.4rem"></i>
                Estudiante: <strong id="nombreEstudianteModal">—</strong>
            </div>
            <p style="font-size:.9rem;color:var(--n500)">
                Esta acción desactivará al estudiante y bloqueará su acceso al sistema.
                No se eliminarán sus datos académicos ni historial.
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="cerrarModalEliminar()">Cancelar</button>
            <form id="formEliminarEstudiante" method="POST" action="">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Sí, eliminar
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Modal de Nuevo Estudiante --}}
@if($rol === 'promotor')
<div class="modal-overlay" id="modalEstudiante">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-user-plus" style="color:var(--primary-light);margin-right:.4rem"></i>
                Nuevo Estudiante
            </div>
            <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('estudiantes.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required maxlength="60" placeholder="Ej: Juan" value="{{ old('nombre') }}">
                        @error('nombre')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apellido *</label>
                        <input type="text" name="apellido" class="form-control" required maxlength="60" placeholder="Ej: Pérez" value="{{ old('apellido') }}">
                        @error('apellido')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Código *</label>
                        <input type="text" name="codigo" class="form-control" required maxlength="15" placeholder="Ej: 2025001" value="{{ old('codigo') }}">
                        @error('codigo')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento') }}">
                        @error('fecha_nacimiento')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo *</label>
                        <input type="email" name="email" class="form-control" required placeholder="correo@colegio.edu.pe" value="{{ old('email') }}">
                        @error('email')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" required minlength="8" placeholder="********">
                        @error('password')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Grado</label>
                        <select name="grado_id" id="gradoSelect" class="form-control" onchange="filtrarSecciones(this.value)">
                            <option value="">— Sin grado —</option>
                            @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => old('grado_id')])
                        </select>
                        @error('grado_id')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sección</label>
                        <select name="seccion" id="seccionSelect" class="form-control">
                            <option value="">— Sin sección —</option>
                            @foreach($secciones as $s)
                                <option value="{{ $s->nombre }}" data-grado="{{ $s->grado_id }}" {{ old('seccion') == $s->nombre ? 'selected' : '' }}>
                                    {{ $s->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('seccion')
                            <span class="form-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--n200)">
                    <label class="form-label" style="font-weight:700;color:var(--n600)">
                        <i class="fas fa-user-friends" style="margin-right:.3rem"></i> Apoderado (opcional, puedes agregarlo después desde la ficha)
                    </label>
                    <div class="grid-2" style="margin-top:.6rem">
                        <div class="form-group">
                            <label class="form-label">Nombre del apoderado</label>
                            <input type="text" name="apoderado_nombre" class="form-control" maxlength="80" placeholder="Ej: María Pérez" value="{{ old('apoderado_nombre') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Parentesco</label>
                            <select name="apoderado_parentesco" class="form-control">
                                <option value="padre">Padre</option>
                                <option value="madre">Madre</option>
                                <option value="tutor">Tutor(a)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="apoderado_telefono" class="form-control" maxlength="15" placeholder="987654321" value="{{ old('apoderado_telefono') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Correo del apoderado</label>
                            <input type="email" name="apoderado_email" class="form-control" placeholder="apoderado@correo.com" value="{{ old('apoderado_email') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
// ── Filtro de estado (cliente) ─────────────────────────────────────────────
function filtrarEstado(val) {
    document.querySelectorAll('tbody tr').forEach(r => {
        r.style.display = (!val || r.dataset.estado === val) ? '' : 'none';
    });
}

// ── Búsqueda con debounce al servidor ─────────────────────────────────────
(function() {
    const input = document.getElementById('tableSearch');
    const form  = document.getElementById('filtroForm');
    if (!input || !form) return;
    let timer;
    input.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 500);
    });
})();

// ── Modal eliminar ─────────────────────────────────────────────────────────
function confirmarEliminar(id, nombre) {
    document.getElementById('nombreEstudianteModal').textContent = nombre;
    document.getElementById('formEliminarEstudiante').action = '/estudiantes/' + id;
    document.getElementById('modalEliminarEstudiante').style.display = 'flex';
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminarEstudiante').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalEliminarEstudiante').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEliminar();
});

// ── Filtrar secciones por grado ────────────────────────────────────────────
function filtrarSecciones(gradoId) {
    const seccionSelect = document.getElementById('seccionSelect');
    if (!seccionSelect) return;
    
    const opciones = seccionSelect.querySelectorAll('option[data-grado]');
    opciones.forEach(function(opt) {
        const pertenece = !gradoId || opt.dataset.grado === gradoId;
        opt.style.display = pertenece ? '' : 'none';
        opt.disabled = !pertenece;
    });

    // Resetear si la seleccionada ya no es válida
    const seleccionada = seccionSelect.querySelector('option[data-grado]:checked');
    if (seleccionada && gradoId && seleccionada.dataset.grado !== gradoId) {
        seccionSelect.value = '';
    }
}

// ── Manejo de modales ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Abrir modal
    document.querySelectorAll('[data-modal-open]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.dataset.modalOpen;
            const modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'flex';
        });
    });

    // Cerrar modal
    document.querySelectorAll('[data-modal-close], .modal-close').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.style.display = 'none';
        });
    });

    // Cerrar modal al hacer clic fuera
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    });
});
</script>
@endpush