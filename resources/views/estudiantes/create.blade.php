@extends('layouts.app')
@section('title','Nuevo estudiante')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="/estudiantes">Estudiantes</a>
            <span style="margin:0 .3rem">/</span>
            <span>Registrar</span>
        </div>
        <h1 class="page-title">Registrar estudiante</h1>
        <p class="page-subtitle">Crea la cuenta del estudiante y su matrícula inicial</p>
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
        <form action="/estudiantes" method="POST">
            @csrf
            <div class="grid-2">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control"
                           value="{{ old('nombre') }}" required maxlength="60"
                           placeholder="Ej: Juan">
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" class="form-control"
                           value="{{ old('apellido') }}" required maxlength="60"
                           placeholder="Ej: Pérez García">
                </div>
                <div class="form-group">
                    <label>Código *</label>
                    <input type="text" name="codigo" class="form-control"
                           value="{{ old('codigo') }}" required maxlength="15"
                           placeholder="Ej: 2025001">
                </div>
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-control"
                           value="{{ old('fecha_nacimiento') }}">
                </div>
                <div class="form-group">
                    <label>Correo electrónico *</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" required
                           placeholder="Ej: juan.perez@colegio.edu.pe">
                </div>
                <div class="form-group">
                    <label>Contraseña *</label>
                    <input type="password" name="password" class="form-control"
                           required minlength="8"
                           placeholder="Mínimo 8 caracteres">
                </div>

                {{-- Grado --}}
                <div class="form-group">
                    <label>Grado</label>
                    <select name="grado_id" id="gradoSelect" class="form-control"
                            onchange="filtrarSecciones(this.value)">
                        <option value="">— Sin grado —</option>
                        @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => old('grado_id')])
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
                                {{ old('seccion') == $s->nombre ? 'selected' : '' }}>
                            {{ $s->nombre }}
                        </option>
                        @endforeach
                    </select>
                    <small style="color:var(--n400);font-size:.75rem">
                        <i class="fas fa-info-circle"></i> Selecciona primero un grado para filtrar las secciones.
                    </small>
                </div>
            </div>

            <div style="display:flex;gap:.75rem;margin-top:1.25rem">
                <a href="/estudiantes" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Registrar estudiante
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

    // Si la sección actualmente seleccionada no pertenece al nuevo grado, resetear
    const seleccionada = seccionSelect.querySelector('option[data-grado]:checked');
    if (seleccionada && gradoId && seleccionada.dataset.grado !== gradoId) {
        seccionSelect.value = '';
    }
}

// Aplicar filtro al cargar si hay un grado preseleccionado (old input)
document.addEventListener('DOMContentLoaded', function() {
    const gradoActual = document.getElementById('gradoSelect').value;
    if (gradoActual) filtrarSecciones(gradoActual);
});
</script>
@endpush
