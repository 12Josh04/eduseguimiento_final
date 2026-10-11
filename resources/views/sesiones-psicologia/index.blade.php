@extends('layouts.app')
@section('title','Sesiones de Psicología')
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Sesiones de Psicología</span></div>
        <h1 class="page-title"><i class="fas fa-brain"></i> Sesiones de Psicología</h1>
        <p class="page-subtitle">Acompañamiento psicológico individual — información confidencial, visible solo para Promotor y Psicóloga</p>
    </div>
    @if(Auth::user()->rol === 'psicologa')
    <button class="btn btn-primary" data-modal-open="modalSesion"><i class="fas fa-plus"></i> Registrar Sesión</button>
    @endif
</div>

@if(Auth::user()->rol === 'promotor')
<div style="padding:.85rem 1.1rem;background:var(--primary-soft);border-radius:var(--radius);margin-bottom:1rem;font-size:.85rem;color:var(--primary);display:flex;align-items:center;gap:.6rem">
    <i class="fas fa-info-circle"></i>
    Solo la <strong>psicóloga</strong> puede registrar sesiones nuevas, desde su propia cuenta. Como promotor, aquí solo puedes consultar el historial con fines de supervisión.
</div>
@endif

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:.75rem;align-items:center;width:100%">
        <select name="estudiante" class="form-control" style="min-width:220px" onchange="this.form.submit()" data-searchable data-placeholder="Buscar estudiante...">
            <option value="">Todos los estudiantes</option>
            @foreach($estudiantes as $e)
                <option value="{{ $e->id }}" {{ request('estudiante') == $e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
            @endforeach
        </select>
        @if(request('estudiante'))
            <a href="{{ route('sesiones.index') }}" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i> Limpiar</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-comments"></i> Historial de Sesiones</div></div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th><th>Grado</th><th>Motivo</th><th>Observaciones</th>
                        <th>Próxima cita</th><th>Fecha</th>
                        @if(Auth::user()->rol === 'psicologa')<th>Acciones</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($sesiones as $s)
                    <tr>
                        <td><div class="student-cell"><div class="avatar">{{ strtoupper(substr($s->estudiante_nombre,0,1)) }}</div><span style="font-weight:500">{{ $s->estudiante_nombre }}</span></div></td>
                        <td><span class="tag tag-info">{{ $s->grado ?? '—' }} {{ $s->seccion ? '"'.$s->seccion.'"' : '' }}</span></td>
                        <td style="font-size:.84rem;max-width:180px">{{ $s->motivo }}</td>
                        <td style="font-size:.82rem;color:var(--n500);max-width:260px">{{ Str::limit($s->observaciones, 90) }}</td>
                        <td style="font-size:.8rem;color:var(--n400)">{{ $s->proxima_cita ? \Carbon\Carbon::parse($s->proxima_cita)->format('d/m/Y') : '—' }}</td>
                        <td style="font-size:.8rem;color:var(--n400);white-space:nowrap">{{ \Carbon\Carbon::parse($s->fecha)->format('d/m/Y') }}</td>
                        @if(Auth::user()->rol === 'psicologa')
                        <td>
                            <form action="{{ route('sesiones.destroy', $s->id) }}" method="POST" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm btn-icon-only" style="color:var(--danger)" data-confirm="¿Eliminar este registro?" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-brain"></i></div><p>Sin sesiones registradas.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sesiones->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--n200)">{{ $sesiones->links() }}</div>
        @endif
    </div>
</div>

@if(Auth::user()->rol === 'psicologa')
<div class="modal-overlay" id="modalSesion">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fas fa-brain" style="color:var(--primary-light);margin-right:.4rem"></i>Registrar Sesión de Psicología</div><button class="modal-close"><i class="fas fa-times"></i></button></div>
        <form action="{{ route('sesiones.store') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Estudiante *</label>
                    <select name="estudiante_id" class="form-control" required data-searchable data-placeholder="Escribe el nombre del estudiante...">
                        <option value="">— Seleccionar —</option>
                        @foreach($estudiantes as $e)<option value="{{ $e->id }}">{{ $e->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Fecha *</label><input type="date" name="fecha" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                    <div class="form-group"><label class="form-label">Próxima cita</label><input type="date" name="proxima_cita" class="form-control"></div>
                </div>
                <div class="form-group"><label class="form-label">Motivo *</label><input type="text" name="motivo" class="form-control" maxlength="160" required placeholder="Ej: Seguimiento por bajo rendimiento"></div>
                <div class="form-group"><label class="form-label">Observaciones *</label><textarea name="observaciones" class="form-control" rows="3" required placeholder="Detalle de la sesión..."></textarea></div>
                <div class="form-group"><label class="form-label">Acuerdos</label><textarea name="acuerdos" class="form-control" rows="2" placeholder="Compromisos o seguimiento acordado..."></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Sesión</button></div>
        </form>
    </div>
</div>
@endif
@endsection
