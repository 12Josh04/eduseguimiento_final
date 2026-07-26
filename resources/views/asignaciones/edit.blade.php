@extends('layouts.app')
@section('title','Editar Asignación')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><a href="{{ route('asignaciones.index') }}">Asignaciones</a><span style="margin:0 .3rem">/</span><span>Editar</span></div>
        <h1 class="page-title"><i class="fas fa-edit"></i> Editar Asignación</h1>
    </div>
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
    <div class="card-body">
        <form action="{{ route('asignaciones.update', $asignacion->id) }}" method="POST" id="formAsignacionEdit">
            @csrf @method('PUT')
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Docente *</label>
                    <select name="docente_id" class="form-control" required>
                        @foreach($docentes as $d)<option value="{{ $d->id }}" @selected($asignacion->docente_id == $d->id)>{{ $d->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Curso *</label>
                    <select name="curso_id" class="form-control" required>
                        @foreach($cursos as $c)<option value="{{ $c->id }}" @selected($asignacion->curso_id == $c->id)>{{ $c->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Año escolar *</label>
                    <input type="number" name="anio_escolar" class="form-control" value="{{ $asignacion->anio_escolar }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de asignación *</label>
                    <select name="tipo_asignacion" id="tipoAsignacion" class="form-control" required onchange="toggleGradoSeccion(this.value)">
                        <option value="general" @selected($asignacion->isGeneral())>General (todos los grados)</option>
                        <option value="especifico" @selected(!$asignacion->isGeneral())>Específico (un grado y sección)</option>
                    </select>
                </div>
                <div class="form-group" id="grupoGrado" style="{{ $asignacion->isGeneral() ? 'display:none' : '' }}">
                    <label class="form-label">Grado</label>
                    <select name="grado_id" id="asigGradoSelect" class="form-control" onchange="cargarSeccionesAsignacion(this.value, null)">
                        <option value="">— Selecciona —</option>
                        @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => $asignacion->grado_id])
                    </select>
                </div>
                <div class="form-group" id="grupoSeccion" style="{{ $asignacion->isGeneral() ? 'display:none' : '' }}">
                    <label class="form-label">Sección</label>
                    <select name="seccion" id="asigSeccionSelect" class="form-control">
                        <option value="">— Selecciona —</option>
                    </select>
                </div>
            </div>

            <h3 style="font-size:.9rem;margin:1rem 0 .5rem;color:var(--primary)"><i class="fas fa-calendar-week"></i> Horarios</h3>
            <div id="horariosContainer"></div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="agregarHorario()"><i class="fas fa-plus"></i> Agregar bloque de horario</button>

            <div style="margin-top:1.5rem;display:flex;gap:.5rem">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar cambios</button>
                <a href="{{ route('asignaciones.index') }}" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const SECCIONES_POR_GRADO = @json($seccionesPorGrado);
const HORARIOS_EXISTENTES = @json($horariosExistentes);
let horarioIndex = 0;

function toggleGradoSeccion(tipo) {
    const mostrar = tipo === 'especifico';
    document.getElementById('grupoGrado').style.display = mostrar ? 'block' : 'none';
    document.getElementById('grupoSeccion').style.display = mostrar ? 'block' : 'none';
}

function cargarSeccionesAsignacion(gradoId, seleccionada) {
    const sel = document.getElementById('asigSeccionSelect');
    const opciones = SECCIONES_POR_GRADO[gradoId] || [];
    sel.innerHTML = '<option value="">— Selecciona —</option>' + opciones.map(s => `<option value="${s}" ${s === seleccionada ? 'selected' : ''}>${s}</option>`).join('');
}

function agregarHorario(datos) {
    datos = datos || {};
    const i = horarioIndex++;
    const div = document.createElement('div');
    div.className = 'grid-2';
    div.style.cssText = 'border:1px solid var(--n200);border-radius:8px;padding:.75rem;margin-bottom:.6rem';
    const dias = {1:'Lunes',2:'Martes',3:'Miércoles',4:'Jueves',5:'Viernes'};
    const opcionesDias = Object.entries(dias).map(([v,l]) => `<option value="${v}" ${String(datos.dia)===v?'selected':''}>${l}</option>`).join('');
    div.innerHTML = `
        <div class="form-group">
            <label class="form-label">Día *</label>
            <select name="horarios[${i}][dia]" class="form-control" required>${opcionesDias}</select>
        </div>
        <div class="form-group">
            <label class="form-label">Aula</label>
            <input type="text" name="horarios[${i}][aula]" class="form-control" value="${datos.aula || ''}" placeholder="Ej: Aula 101">
        </div>
        <div class="form-group">
            <label class="form-label">Hora inicio *</label>
            <input type="time" name="horarios[${i}][hora_inicio]" class="form-control" value="${datos.hora_inicio || ''}" required>
        </div>
        <div class="form-group">
            <label class="form-label">Hora fin *</label>
            <input type="time" name="horarios[${i}][hora_fin]" class="form-control" value="${datos.hora_fin || ''}" required>
        </div>
        <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i> Quitar bloque</button>
    `;
    document.getElementById('horariosContainer').appendChild(div);
}

document.addEventListener('DOMContentLoaded', () => {
    cargarSeccionesAsignacion('{{ $asignacion->grado_id }}', @json($asignacion->seccion));
    if (HORARIOS_EXISTENTES.length) {
        HORARIOS_EXISTENTES.forEach(h => agregarHorario(h));
    } else {
        agregarHorario();
    }
});
</script>
@endpush
