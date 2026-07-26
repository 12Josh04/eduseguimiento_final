@extends('layouts.app')
@section('title','Secciones')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Secciones</span></div>
        <h1 class="page-title">Gestión de Secciones</h1>
        <p class="page-subtitle">Organiza grados, secciones y años académicos</p>
    </div>
    <button class="btn btn-primary" data-modal-open="modalSeccion"><i class="fas fa-plus"></i> Nueva Sección</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Grado</th><th>Sección</th><th>Año</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($secciones as $s)
                    <tr>
                        <td>{{ $s->grado }}</td>
                        <td><span class="tag tag-info">{{ $s->nombre }}</span></td>
                        <td>{{ $s->anio_escolar }}</td>
                        <td>
                            <div style="display:flex;gap:.3rem">
                                <button class="btn btn-ghost btn-sm btn-icon-only" title="Editar" onclick="abrirEditar('{{ $s->id }}','{{ $s->grado_id }}','{{ $s->nombre }}','{{ $s->anio_escolar }}')"><i class="fas fa-edit"></i></button>
                                <form action="/secciones/{{ $s->id }}" method="POST" style="display:inline">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm btn-icon-only" style="color:var(--danger)" data-confirm="¿Eliminar esta sección?" title="Eliminar"><i class="fas fa-trash"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-layer-group"></i></div><p>No hay secciones registradas.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalSeccion">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fas fa-layer-group"></i> Nueva Sección</div><button class="modal-close"><i class="fas fa-times"></i></button></div>
        <form action="/secciones" method="POST">@csrf
            <div class="modal-body">
                <div class="form-group"><label>Grado</label><select name="grado_id" class="form-control" required>@include('partials.grado-options', ['grados' => $grados])</select></div>
                <div class="form-group"><label>Nombre de la sección</label><input type="text" name="nombre" class="form-control" placeholder="A" required></div>
                <div class="form-group"><label>Año escolar</label><input type="number" name="anio_escolar" class="form-control" value="{{ now()->year }}" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button></div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalEditarSeccion">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fas fa-edit"></i> Editar Sección</div><button class="modal-close"><i class="fas fa-times"></i></button></div>
        <form id="formEditarSeccion" action="" method="POST">@csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group"><label>Grado</label><select name="grado_id" id="editSeccionGrado" class="form-control" required>@include('partials.grado-options', ['grados' => $grados])</select></div>
                <div class="form-group"><label>Nombre de la sección</label><input type="text" name="nombre" id="editSeccionNombre" class="form-control" required></div>
                <div class="form-group"><label>Año escolar</label><input type="number" name="anio_escolar" id="editSeccionAnio" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Guardar</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function abrirEditar(id, gradoId, nombre, anio) {
    document.getElementById('formEditarSeccion').action = '/secciones/' + id;
    document.getElementById('editSeccionGrado').value = gradoId;
    document.getElementById('editSeccionNombre').value = nombre;
    document.getElementById('editSeccionAnio').value = anio;
    document.getElementById('modalEditarSeccion').classList.add('open');
}
</script>
@endpush
