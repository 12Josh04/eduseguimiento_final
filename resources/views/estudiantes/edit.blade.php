@extends('layouts.app')
@section('title','Editar estudiante')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="/estudiantes">Estudiantes</a>
            <span style="margin:0 .3rem">/</span>
            <span>Editar</span>
        </div>
        <h1 class="page-title">Editar estudiante</h1>
        <p class="page-subtitle">Actualiza datos básicos y asignación de grado / sección</p>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:1rem;padding:.85rem 1.1rem;background:var(--danger-soft);border-radius:10px;border-left:4px solid var(--danger)">
    <i class="fas fa-exclamation-circle" style="color:var(--danger);margin-right:.4rem"></i>
    <strong>Corrige los siguientes errores:</strong>
    <ul style="margin:.4rem 0 0 1.2rem;font-size:.85rem">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
    <div class="card-body">
        <form action="/estudiantes/{{ $estudiante->id }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid-2">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control"
                           value="{{ old('nombre', $estudiante->nombre) }}" required maxlength="60">
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" class="form-control"
                           value="{{ old('apellido', $estudiante->apellido) }}" required maxlength="60">
                </div>
                <div class="form-group">
                    <label>Código</label>
                    <input type="text" class="form-control"
                           value="{{ $estudiante->codigo }}" disabled
                           style="background:var(--n100);cursor:not-allowed">
                    <small style="color:var(--n400);font-size:.75rem">
                        <i class="fas fa-lock"></i> El código no puede modificarse.
                    </small>
                </div>
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-control"
                           value="{{ old('fecha_nacimiento', $estudiante->fecha_nacimiento) }}">
                </div>

                {{-- Grado --}}
                <div class="form-group">
                    <label>Grado</label>
                    <select name="grado_id" id="gradoSelect" class="form-control"
                            onchange="filtrarSecciones(this.value)">
                        <option value="">— Sin grado —</option>
                        @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => old('grado_id', $matricula->grado_id ?? '')])
                    </select>
                </div>

                {{-- Sección (filtrada por grado) --}}
                <div class="form-group">
                    <label>Sección</label>
                    <select name="seccion" id="seccionSelect" class="form-control">
                        <option value="">— Sin sección —</option>
                        @foreach($secciones as $s)
                        <option value="{{ $s->nombre }}"
                                data-grado="{{ $s->grado_id }}"
                                {{ (old('seccion', $matricula->seccion ?? '')) == $s->nombre ? 'selected' : '' }}>
                            {{ $s->nombre }}
                        </option>
                        @endforeach
                    </select>
                    <small style="color:var(--n400);font-size:.75rem">
                        <i class="fas fa-info-circle"></i> Selecciona el grado para filtrar las secciones disponibles.
                    </small>
                </div>
            </div>

            <div style="display:flex;gap:.75rem;margin-top:1.25rem">
                <a href="/estudiantes" class="btn btn-ghost">Cancelar</a>
                <a href="/estudiantes/{{ $estudiante->id }}" class="btn btn-ghost">
                    <i class="fas fa-eye"></i> Ver perfil
                </a>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Muestra solo las secciones del grado seleccionado.
 * Las opciones tienen data-grado con el UUID del grado.
 */
function filtrarSecciones(gradoId) {
    const seccionSelect = document.getElementById('seccionSelect');
    const opciones = seccionSelect.querySelectorAll('option[data-grado]');

    opciones.forEach(function(opt) {
        const pertenece = !gradoId || opt.dataset.grado === gradoId;
        opt.style.display = pertenece ? '' : 'none';
        opt.disabled = !pertenece;
    });

    // Si la sección actual no pertenece al nuevo grado, resetear
    const seleccionada = seccionSelect.querySelector('option[data-grado]:checked');
    if (seleccionada && gradoId && seleccionada.dataset.grado !== gradoId) {
        seccionSelect.value = '';
    }
}

// Aplicar filtro al cargar con los datos actuales de la matrícula
document.addEventListener('DOMContentLoaded', function() {
    const gradoActual = document.getElementById('gradoSelect').value;
    if (gradoActual) filtrarSecciones(gradoActual);
});
</script>
@endpush
