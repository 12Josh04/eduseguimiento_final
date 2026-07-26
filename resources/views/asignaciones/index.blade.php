@extends('layouts.app')
@section('title','Asignaciones Docentes')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Asignaciones</span></div>
        <h1 class="page-title"><i class="fas fa-user-check"></i> Asignaciones Docentes</h1>
        <p class="page-subtitle">Asigna cursos, grados/secciones y horarios a cada docente</p>
    </div>
    <button class="btn btn-primary" data-modal-open="modalAsignacion">
        <i class="fas fa-plus"></i> Nueva Asignación
    </button>
</div>

@if($errors->any())
    <div class="alert" style="background:#fdecea;color:#c0392b;padding:.9rem 1.1rem;border-radius:8px;margin-bottom:1rem">
        <strong>Revisa los datos:</strong>
        <ul style="margin:.4rem 0 0 1.1rem">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Docente</th><th>Curso</th><th>Tipo</th><th>Grado / Sección</th>
                    <th>Año</th><th>Horarios</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($asignaciones as $a)
                <tr>
                    <td>{{ $a->docente->nombre ?? '—' }}</td>
                    <td>{{ $a->curso->nombre ?? '—' }}</td>
                    <td>
                        @if($a->isGeneral())
                            <span class="tag tag-info">General</span>
                        @else
                            <span class="tag tag-warning">Específico</span>
                        @endif
                    </td>
                    <td>{{ $a->grado->nombre ?? 'Todos' }} {{ $a->seccion ? '"'.$a->seccion.'"' : '' }}</td>
                    <td>{{ $a->anio_escolar }}</td>
                    <td><span class="tag tag-success">{{ $a->horarios->count() }} bloque(s)</span></td>
                    <td>
                        <div style="display:flex;gap:.3rem">
                            <a href="{{ route('asignaciones.detalles', $a->id) }}" class="btn btn-ghost btn-sm btn-icon-only" title="Ver detalles"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('asignaciones.edit', $a->id) }}" class="btn btn-ghost btn-sm btn-icon-only" title="Editar"><i class="fas fa-edit"></i></a>
                            <form method="POST" action="{{ route('asignaciones.destroy', $a->id) }}" onsubmit="return confirm('¿Eliminar esta asignación y sus horarios?');" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm btn-icon-only" style="color:var(--danger)" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="empty-state" style="padding:2rem"><p>No hay asignaciones registradas todavía.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Nueva Asignación --}}
<div class="modal-overlay" id="modalAsignacion">
    <div class="modal" style="max-width:640px">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-user-check"></i> Nueva Asignación</div>
            <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('asignaciones.store') }}" method="POST" id="formAsignacion">
            @csrf
            <div class="modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Docente *</label>
                        <select name="docente_id" class="form-control" required>
                            <option value="">— Selecciona —</option>
                            @foreach($docentes as $d)<option value="{{ $d->id }}">{{ $d->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Curso *</label>
                        <select name="curso_id" class="form-control" required>
                            <option value="">— Selecciona —</option>
                            @foreach($cursos as $c)<option value="{{ $c->id }}">{{ $c->nombre }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Año escolar *</label>
                        <input type="number" name="anio_escolar" class="form-control" value="{{ now()->year }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de asignación *</label>
                        <select name="tipo_asignacion" id="tipoAsignacion" class="form-control" required onchange="toggleGradoSeccion(this.value)">
                            <option value="general">General (todos los grados)</option>
                            <option value="especifico">Específico (un grado y sección)</option>
                        </select>
                    </div>
                    <div class="form-group" id="grupoGrado" style="display:none">
                        <label class="form-label">Grado</label>
                        <select name="grado_id" id="asigGradoSelect" class="form-control" onchange="cargarSeccionesAsignacion(this.value)">
                            <option value="">— Selecciona —</option>
                            @include('partials.grado-options', ['grados' => $grados])
                        </select>
                    </div>
                    <div class="form-group" id="grupoSeccion" style="display:none">
                        <label class="form-label">Sección</label>
                        <select name="seccion" id="asigSeccionSelect" class="form-control">
                            <option value="">— Selecciona un grado primero —</option>
                        </select>
                    </div>
                </div>

                <h3 style="font-size:.9rem;margin:1rem 0 .5rem;color:var(--primary)"><i class="fas fa-calendar-week"></i> Horarios</h3>
                <div id="horariosContainer"></div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="agregarHorario()"><i class="fas fa-plus"></i> Agregar bloque de horario</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar asignación</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const SECCIONES_POR_GRADO = @json($seccionesPorGrado);
let horarioIndex = 0;

function toggleGradoSeccion(tipo) {
    const mostrar = tipo === 'especifico';
    document.getElementById('grupoGrado').style.display = mostrar ? 'block' : 'none';
    document.getElementById('grupoSeccion').style.display = mostrar ? 'block' : 'none';
}

function cargarSeccionesAsignacion(gradoId) {
    const sel = document.getElementById('asigSeccionSelect');
    const opciones = SECCIONES_POR_GRADO[gradoId] || [];
    sel.innerHTML = '<option value="">— Selecciona —</option>' + opciones.map(s => `<option value="${s}">${s}</option>`).join('');
}

function agregarHorario() {
    const i = horarioIndex++;
    const div = document.createElement('div');
    div.className = 'grid-2';
    div.style.cssText = 'border:1px solid var(--n200);border-radius:8px;padding:.75rem;margin-bottom:.6rem';
    div.innerHTML = `
        <div class="form-group">
            <label class="form-label">Día *</label>
            <select name="horarios[${i}][dia]" class="form-control" required>
                <option value="1">Lunes</option><option value="2">Martes</option><option value="3">Miércoles</option>
                <option value="4">Jueves</option><option value="5">Viernes</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Aula</label>
            <input type="text" name="horarios[${i}][aula]" class="form-control" placeholder="Ej: Aula 101">
        </div>
        <div class="form-group">
            <label class="form-label">Hora inicio *</label>
            <input type="time" name="horarios[${i}][hora_inicio]" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Hora fin *</label>
            <input type="time" name="horarios[${i}][hora_fin]" class="form-control" required>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i> Quitar bloque</button>
    `;
    document.getElementById('horariosContainer').appendChild(div);
}

// Al menos un bloque de horario por defecto
document.addEventListener('DOMContentLoaded', () => agregarHorario());
</script>
@endpush
