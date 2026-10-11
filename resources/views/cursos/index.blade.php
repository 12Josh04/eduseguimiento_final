@extends('layouts.app')
@section('title','Cursos')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Cursos</span></div>
        <h1 class="page-title">Gestión de Cursos</h1>
        <p class="page-subtitle">Los cursos son generales para todo el colegio; para dictarlos en un grado y con un docente específico, usa el módulo de <a href="{{ route('asignaciones.index') }}">Asignaciones</a>.</p>
    </div>
    <button class="btn btn-primary" data-modal-open="modalCurso"><i class="fas fa-plus"></i> Nuevo Curso</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Curso</th><th>Código</th><th>Área curricular</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($cursos as $curso)
                    <tr>
                        <td><span class="tag tag-info">{{ $curso->nombre }}</span></td>
                        <td>{{ $curso->codigo }}</td>
                        <td>{{ $curso->area_curricular ?? '—' }}</td>
                        <td>
                            <div style="display:flex;gap:.3rem">
                                <button class="btn btn-ghost btn-sm btn-icon-only" title="Editar" onclick="abrirEditar('{{ $curso->id }}','{{ addslashes($curso->nombre) }}','{{ $curso->codigo }}','{{ addslashes($curso->area_curricular ?? '') }}')"><i class="fas fa-edit"></i></button>
                                <form action="/cursos/{{ $curso->id }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm btn-icon-only" style="color:var(--danger)" data-confirm="¿Eliminar este curso?" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-book"></i></div><p>No hay cursos registrados.</p></div></td></tr>
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
                <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombre" class="form-control" required placeholder="Ej: Trigonometría"></div>
                <div class="form-group"><label class="form-label">Código *</label><input type="text" name="codigo" class="form-control" required placeholder="Ej: TRI01"></div>
                <div class="form-group">
                    <label class="form-label">Área curricular</label>
                    <input type="text" name="area_curricular" class="form-control" placeholder="Ej: Matemática" list="areasCurriculares">
                    <datalist id="areasCurriculares">
                        <option value="Matemática"><option value="Comunicación"><option value="Ciencia y Tecnología">
                        <option value="Desarrollo Personal, Ciudadanía y Cívica"><option value="Inglés">
                        <option value="Educación Física"><option value="Arte y Cultura"><option value="Educación Religiosa">
                        <option value="Educación para el Trabajo">
                    </datalist>
                </div>
                <p style="font-size:.82rem;color:var(--n500)">
                    <i class="fas fa-info-circle"></i> El curso queda disponible para todo el colegio. Para dictarlo en un grado con un docente y horario, ve a <a href="{{ route('asignaciones.index') }}">Asignaciones</a> y crea una nueva asignación con este curso.
                </p>
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
                <div class="form-group"><label class="form-label">Nombre *</label><input type="text" name="nombre" id="editCursoNombre" class="form-control" required></div>
                <div class="form-group"><label class="form-label">Código *</label><input type="text" name="codigo" id="editCursoCodigo" class="form-control" required></div>
                <div class="form-group">
                    <label class="form-label">Área curricular</label>
                    <input type="text" name="area_curricular" id="editCursoArea" class="form-control" list="areasCurriculares">
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Guardar</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function abrirEditar(id, nombre, codigo, area) {
    document.getElementById('formEditarCurso').action = '/cursos/' + id;
    document.getElementById('editCursoNombre').value = nombre;
    document.getElementById('editCursoCodigo').value = codigo;
    document.getElementById('editCursoArea').value = area;
    document.getElementById('modalEditarCurso').classList.add('open');
}
</script>
@endpush
