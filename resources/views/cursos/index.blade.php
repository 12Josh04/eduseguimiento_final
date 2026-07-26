@extends('layouts.app')
@section('title','Cursos')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Cursos</span></div>
        <h1 class="page-title">Gestión de Cursos</h1>
        <p class="page-subtitle">Administración de cursos por grado, sección y año académico</p>
    </div>
    <button class="btn btn-primary" data-modal-open="modalCurso"><i class="fas fa-plus"></i> Nuevo Curso</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Curso</th><th>Código</th><th>Grado</th><th>Sección</th><th>Año</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($cursos as $curso)
                    <tr>
                        <td><span class="tag tag-info">{{ $curso->nombre }}</span></td>
                        <td>{{ $curso->codigo }}</td>
                        <td>{{ $curso->grado ?? '—' }}</td>
                        <td>{{ $curso->seccion ?? '—' }}</td>
                        <td>{{ $curso->anio_escolar ?? '—' }}</td>
                        <td>
                            <div style="display:flex;gap:.3rem">
                                <button class="btn btn-ghost btn-sm btn-icon-only" title="Editar" onclick="abrirEditar('{{ $curso->id }}','{{ addslashes($curso->nombre) }}','{{ $curso->codigo }}','{{ $curso->grado_id }}','{{ $curso->seccion }}','{{ $curso->anio_escolar }}')"><i class="fas fa-edit"></i></button>
                                <form action="/cursos/{{ $curso->id }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm btn-icon-only" style="color:var(--danger)" data-confirm="¿Eliminar este curso?" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-book"></i></div><p>No hay cursos registrados.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalCurso">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fas fa-book"></i> Nuevo Curso</div><button class="modal-close"><i class="fas fa-times"></i></button></div>
        <form action="/cursos" method="POST">@csrf
            <div class="modal-body">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                <div class="form-group"><label>Código</label><input type="text" name="codigo" class="form-control" required></div>
                <div class="form-row cols-2">
                    <div class="form-group"><label>Grado</label><select name="grado_id" class="form-control" required>@include('partials.grado-options', ['grados' => $grados])</select></div>
                    <div class="form-group"><label>Sección</label><select name="seccion" class="form-control" required><option value="">Seleccione una sección</option>@foreach($secciones as $s)<option value="{{ $s->nombre }}">{{ $s->nombre }}</option>@endforeach</select></div>
                </div>
                <div class="form-group"><label>Año escolar</label><input type="number" name="anio_escolar" class="form-control" value="{{ now()->year }}" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button></div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalEditarCurso">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fas fa-edit"></i> Editar Curso</div><button class="modal-close"><i class="fas fa-times"></i></button></div>
        <form id="formEditarCurso" action="" method="POST">@csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group"><label>Nombre</label><input type="text" name="nombre" id="editCursoNombre" class="form-control" required></div>
                <div class="form-group"><label>Código</label><input type="text" name="codigo" id="editCursoCodigo" class="form-control" required></div>
                <div class="form-row cols-2">
                    <div class="form-group"><label>Grado</label><select name="grado_id" id="editCursoGrado" class="form-control" required>@include('partials.grado-options', ['grados' => $grados])</select></div>
                    <div class="form-group"><label>Sección</label><select name="seccion" id="editCursoSeccion" class="form-control" required><option value="">Seleccione una sección</option>@foreach($secciones as $s)<option value="{{ $s->nombre }}">{{ $s->nombre }}</option>@endforeach</select></div>
                </div>
                <div class="form-group"><label>Año escolar</label><input type="number" name="anio_escolar" id="editCursoAnio" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Guardar</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function abrirEditar(id, nombre, codigo, gradoId, seccion, anio) {
    document.getElementById('formEditarCurso').action = '/cursos/' + id;
    document.getElementById('editCursoNombre').value = nombre;
    document.getElementById('editCursoCodigo').value = codigo;
    document.getElementById('editCursoGrado').value = gradoId;
    document.getElementById('editCursoSeccion').value = seccion;
    document.getElementById('editCursoAnio').value = anio;
    document.getElementById('modalEditarCurso').classList.add('open');
}
</script>
@endpush
