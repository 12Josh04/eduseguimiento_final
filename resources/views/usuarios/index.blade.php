@extends('layouts.app')
@section('title','Usuarios')
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <span>Usuarios</span>
        </div>
        <h1 class="page-title">Gestión de Usuarios</h1>
        <p class="page-subtitle">Administración de cuentas y roles del sistema</p>
    </div>
    <button class="btn btn-primary" data-modal-open="modalUsuario">
        <i class="fas fa-user-plus"></i> Nuevo Usuario
    </button>
</div>

{{-- Stats por rol --}}
@php
    $conts = [
        'promotor'   => $usuarios->where('rol','promotor')->count(),
        'docente'    => $usuarios->where('rol','docente')->count(),
        'psicologa'  => $usuarios->where('rol','psicologa')->count(),
        'estudiante' => $usuarios->where('rol','estudiante')->count(),
    ];
@endphp
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.25rem">
    <div class="stat-card"><div class="stat-icon red"><i class="fas fa-crown"></i></div><div><div class="stat-value">{{ $conts['promotor'] }}</div><div class="stat-label">Promotores</div></div></div>
    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div><div><div class="stat-value">{{ $conts['docente'] }}</div><div class="stat-label">Docentes</div></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-brain"></i></div><div><div class="stat-value">{{ $conts['psicologa'] }}</div><div class="stat-label">Psicólogas</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-user-graduate"></i></div><div><div class="stat-value">{{ $conts['estudiante'] }}</div><div class="stat-label">Estudiantes/Padres</div></div></div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-users"></i> Lista de Usuarios</div>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control" id="tableSearch" placeholder="Buscar usuario...">
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Registrado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $u)
                    <tr>
                        <td>
                            <div class="student-cell">
                                <div class="avatar">{{ strtoupper(substr($u->nombre, 0, 1)) }}</div>
                                <span style="font-weight:500">{{ $u->nombre }}</span>
                            </div>
                        </td>
                        <td style="font-size:.84rem;color:var(--n600)">{{ $u->email }}</td>
                        <td>
                            <span class="role-badge role-{{ $u->rol }}"
                                  style="font-size:.75rem;padding:3px 10px;border-radius:10px">
                                {{ ucfirst($u->rol) }}
                            </span>
                        </td>
                        <td>
                            @if($u->activo)
                                <span class="tag tag-success"><i class="fas fa-circle" style="font-size:.45rem"></i> Activo</span>
                            @else
                                <span class="tag tag-neutral"><i class="fas fa-circle" style="font-size:.45rem"></i> Inactivo</span>
                            @endif
                        </td>
                        <td style="font-size:.8rem;color:var(--n400)">
                            {{ \Carbon\Carbon::parse($u->creado_en)->format('d/m/Y') }}
                        </td>
                        <td>
                            <div style="display:flex;gap:.3rem">
                                <button class="btn btn-ghost btn-sm btn-icon-only"
                                    title="Editar"
                                    onclick="abrirEditar('{{ $u->id }}','{{ addslashes($u->nombre) }}','{{ $u->email }}','{{ $u->rol_id }}','{{ $u->activo }}')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if($u->id !== Auth::id())
                                <form action="/usuarios/{{ $u->id }}" method="POST" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="btn btn-ghost btn-sm btn-icon-only"
                                        style="color:var(--danger)"
                                        data-confirm="¿Desactivar este usuario?"
                                        title="Desactivar">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state" style="padding:2rem">
                                <div class="empty-state-icon"><i class="fas fa-users"></i></div>
                                <p>Sin usuarios registrados.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal: Nuevo usuario --}}
<div class="modal-overlay" id="modalUsuario">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-user-plus" style="color:var(--primary-light);margin-right:.4rem"></i>
                Nuevo Usuario
            </div>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <form action="/usuarios" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Prof. Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo electrónico *</label>
                    <input type="email" name="email" class="form-control" placeholder="correo@colegio.edu.pe" required>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select name="rol_id" class="form-control" required>
                            <option value="1">Promotor</option>
                            <option value="2">Docente</option>
                            <option value="3">Psicóloga</option>
                            <option value="4">Estudiante / Padre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" placeholder="Mín. 8 caracteres" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost modal-close">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Editar usuario --}}
<div class="modal-overlay" id="modalEditar">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fas fa-edit" style="color:var(--warning);margin-right:.4rem"></i>
                Editar Usuario
            </div>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <form id="formEditar" action="" method="POST">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" name="nombre" id="editNombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" id="editEmail" class="form-control" disabled
                           style="background:var(--n50);color:var(--n400)">
                    <small style="color:var(--n400);font-size:.75rem">El email no se puede modificar.</small>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select name="rol_id" id="editRol" class="form-control" required>
                            <option value="1">Promotor</option>
                            <option value="2">Docente</option>
                            <option value="3">Psicóloga</option>
                            <option value="4">Estudiante / Padre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <select name="activo" id="editActivo" class="form-control">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nueva contraseña <span style="color:var(--n400)">(dejar vacío para no cambiar)</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Mín. 8 caracteres">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost modal-close">Cancelar</button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function abrirEditar(id, nombre, email, rolId, activo) {
    document.getElementById('formEditar').action = '/usuarios/' + id;
    document.getElementById('editNombre').value  = nombre;
    document.getElementById('editEmail').value   = email;
    document.getElementById('editRol').value     = rolId;
    document.getElementById('editActivo').value  = activo;
    document.getElementById('modalEditar').classList.add('open');
}
</script>
@endpush
