@extends('layouts.app')
@section('title', Auth::user()->rol === 'estudiante' ? 'Mi Conducta' : 'Conductas')
@section('content')

@php $esEstudiante = Auth::user()->rol === 'estudiante'; @endphp

<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>{{ $esEstudiante ? 'Mi Conducta' : 'Conductas' }}</span></div>
        <h1 class="page-title">{{ $esEstudiante ? 'Mi Conducta' : 'Sistema Conductual' }}</h1>
        <p class="page-subtitle">{{ $esEstudiante ? 'Tus registros de comportamiento' : 'Registro y seguimiento de comportamiento estudiantil' }}</p>
    </div>
    @if(in_array(Auth::user()->rol,['promotor','docente','psicologa']))
    <button class="btn btn-success" data-modal-open="modalConducta"><i class="fas fa-plus"></i> Registrar Conducta</button>
    @endif
</div>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1.25rem">
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-smile"></i></div><div><div class="stat-value" style="color:var(--success)">{{ $stats['positivas'] }}</div><div class="stat-label">Positivas</div></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="fas fa-frown"></i></div><div><div class="stat-value" style="color:var(--danger)">{{ $stats['negativas'] }}</div><div class="stat-label">Negativas</div></div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-meh"></i></div><div><div class="stat-value" style="color:var(--warning)">{{ $stats['observaciones'] }}</div><div class="stat-label">Observaciones</div></div></div>
</div>

<div class="filter-bar">
    @unless($esEstudiante)
    <div class="search-bar"><i class="fas fa-search"></i><input type="text" class="form-control" id="tableSearch" placeholder="Buscar estudiante..."></div>
    @endunless
    <select class="form-control" style="min-width:140px" id="filtroTipo">
        <option value="">Todos los tipos</option>
        <option value="positivo">✓ Positivo</option>
        <option value="negativo">✗ Negativo</option>
        <option value="observacion">● Observación</option>
    </select>
    <button class="btn btn-ghost btn-sm" onclick="document.getElementById('filtroTipo').value='';var ts=document.getElementById('tableSearch');if(ts)ts.value='';document.querySelectorAll('tbody tr').forEach(r=>r.style.display='')">
        <i class="fas fa-times"></i> Limpiar
    </button>
</div>

<div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-clipboard-check"></i> Historial de Conductas</div></div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table id="tablaConductas">
                <thead><tr><th>Estudiante</th><th>Tipo</th><th>Descripción</th><th>Lugar</th><th>Reportado por</th><th>Fecha</th>
                    @if(in_array(Auth::user()->rol,['promotor','psicologa']))<th>Acciones</th>@endif
                </tr></thead>
                <tbody>
                    @forelse($conductas as $c)
                    <tr data-tipo="{{ $c->tipo }}">
                        <td><div class="student-cell"><div class="avatar">{{ strtoupper(substr($c->estudiante_nombre,0,1)) }}</div><span style="font-weight:500">{{ $c->estudiante_nombre }}</span></div></td>
                        <td><span class="tag conducta-{{ $c->tipo }}">{{ ucfirst($c->tipo) }}</span></td>
                        <td style="max-width:250px;font-size:.84rem">{{ Str::limit($c->descripcion,80) }}</td>
                        <td style="color:var(--n400);font-size:.82rem">{{ $c->lugar ?? '—' }}</td>
                        <td style="font-size:.82rem">
                            {{ $c->reportador_nombre }}
                            @if($c->reportador_rol === 'psicologa')
                                <span class="tag" style="background:#ede7f6;color:#5e35b1;font-size:.68rem;margin-left:.3rem"><i class="fas fa-brain"></i> Registro psicológico</span>
                            @endif
                        </td>
                        <td style="color:var(--n400);font-size:.8rem;white-space:nowrap">{{ \Carbon\Carbon::parse($c->fecha_hora)->format('d/m/Y H:i') }}</td>
                        @if(in_array(Auth::user()->rol,['promotor','psicologa']))
                        <td>
                            <form action="/conductas/{{ $c->id }}" method="POST" style="display:inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm btn-icon-only" style="color:var(--danger)" data-confirm="¿Eliminar este registro?" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-clipboard-check"></i></div><p>Sin conductas registradas.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($conductas->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--n200)">{{ $conductas->links() }}</div>
        @endif
    </div>
</div>

<!-- Modal -->
@if(in_array(Auth::user()->rol,['promotor','docente','psicologa']))
<div class="modal-overlay" id="modalConducta">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fas fa-plus-circle" style="color:var(--accent);margin-right:.4rem"></i>Registrar Conducta</div><button class="modal-close"><i class="fas fa-times"></i></button></div>
        <form action="/conductas" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Estudiante *</label>
                    <select name="estudiante_id" class="form-control" required data-searchable data-placeholder="Escribe el nombre del estudiante...">
                        <option value="">— Seleccionar —</option>
                        @foreach($estudiantes as $est)<option value="{{ $est->id }}">{{ $est->nombre }}</option>@endforeach
                    </select>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Tipo *</label>
                        <select name="tipo" class="form-control" required><option value="positivo">✓ Positivo</option><option value="negativo">✗ Negativo</option><option value="observacion">● Observación</option></select>
                    </div>
                    <div class="form-group"><label class="form-label">Lugar</label><input type="text" name="lugar" class="form-control" placeholder="Aula, Patio..."></div>
                </div>
                <div class="form-group"><label class="form-label">Descripción *</label><textarea name="descripcion" class="form-control" rows="3" required placeholder="Describa el comportamiento observado..."></textarea></div>
                <div class="form-group"><label class="form-label">Fecha y Hora</label><input type="datetime-local" name="fecha_hora" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button></div>
        </form>
    </div>
</div>
@endif
@endsection
@push('scripts')
<script>
document.getElementById('filtroTipo').addEventListener('change',function(){
    const t=this.value,s=(document.getElementById('tableSearch')?.value||'').toLowerCase();
    document.querySelectorAll('#tablaConductas tbody tr').forEach(r=>{
        r.style.display=(!t||r.dataset.tipo===t)&&(!s||r.textContent.toLowerCase().includes(s))?'':'none';
    });
});
</script>
@endpush
