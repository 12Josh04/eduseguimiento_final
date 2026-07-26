@extends('layouts.app')
@section('title','Matrícula')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Matrícula</span></div>
        <h1 class="page-title"><i class="fas fa-user-plus"></i> Solicitudes de Matrícula</h1>
        <p class="page-subtitle">Revisa el comprobante de pago y confirma o rechaza cada solicitud</p>
    </div>
    <a href="{{ route('matricula.formulario') }}" target="_blank" class="btn btn-ghost">
        <i class="fas fa-external-link-alt"></i> Ver formulario público
    </a>
</div>

<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.25rem">
    <div class="card"><div class="card-body">
        <div style="font-size:1.5rem;font-weight:800;color:var(--warning)">{{ $conteos['pendiente'] ?? 0 }}</div>
        <div style="color:var(--n500);font-size:.85rem">Pendientes</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:1.5rem;font-weight:800;color:var(--success)">{{ $conteos['confirmado'] ?? 0 }}</div>
        <div style="color:var(--n500);font-size:.85rem">Confirmadas</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:1.5rem;font-weight:800;color:var(--danger)">{{ $conteos['rechazado'] ?? 0 }}</div>
        <div style="color:var(--n500);font-size:.85rem">Rechazadas</div>
    </div></div>
</div>

<div class="filter-bar" style="margin-bottom:1rem">
    @foreach(['pendiente'=>'Pendientes','confirmado'=>'Confirmadas','rechazado'=>'Rechazadas','todas'=>'Todas'] as $val => $label)
        <a href="{{ route('matricula.pendientes', ['estado' => $val]) }}"
           class="btn btn-sm {{ $estado === $val ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th>Postulante</th><th>Grado</th><th>Apoderado</th><th>Teléfono</th>
                    <th>Monto</th><th>Voucher</th><th>Estado</th><th>Fecha</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($solicitudes as $s)
                <tr>
                    <td>{{ $s->nombre }} {{ $s->apellido }}</td>
                    <td>{{ $s->grado_nombre ?? '—' }} <span style="color:var(--n400);font-size:.75rem">({{ $s->grado_nivel }})</span></td>
                    <td>{{ $s->apoderado_nombre }}</td>
                    <td>{{ $s->apoderado_telefono }}</td>
                    <td>{{ $s->monto ? 'S/ '.number_format($s->monto,2) : '—' }}</td>
                    <td>
                        @if($s->voucher_path)
                            <a href="{{ route('matricula.voucher', $s->id) }}" target="_blank" class="btn btn-ghost btn-sm"><i class="fas fa-receipt"></i> Ver</a>
                        @else
                            <span style="color:var(--n400)">—</span>
                        @endif
                    </td>
                    <td>
                        @if($s->estado === 'pendiente')
                            <span class="tag tag-warning">Pendiente</span>
                        @elseif($s->estado === 'confirmado')
                            <span class="tag tag-success">Confirmado</span>
                        @else
                            <span class="tag tag-danger">Rechazado</span>
                        @endif
                    </td>
                    <td style="font-size:.78rem;color:var(--n500)">{{ \Carbon\Carbon::parse($s->created_at)->format('d/m/Y') }}</td>
                    <td>
                        @if($s->estado === 'pendiente')
                        <div style="display:flex;gap:.3rem">
                            <form method="POST" action="{{ route('matricula.confirmar', $s->id) }}" onsubmit="return confirm('¿Confirmar el pago y matricular a {{ addslashes($s->nombre.' '.$s->apellido) }}?');">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Confirmar</button>
                            </form>
                            <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('rechazo-{{ $s->id }}').style.display='flex'">
                                <i class="fas fa-times"></i> Rechazar
                            </button>
                        </div>
                        <div id="rechazo-{{ $s->id }}" style="display:none;margin-top:.5rem">
                            <form method="POST" action="{{ route('matricula.rechazar', $s->id) }}">
                                @csrf
                                <input type="text" name="observacion_promotor" class="form-control" placeholder="Motivo del rechazo (opcional)" style="margin-bottom:.4rem">
                                <button type="submit" class="btn btn-danger btn-sm">Confirmar rechazo</button>
                            </form>
                        </div>
                        @elseif($s->estado === 'confirmado')
                            <a href="{{ route('estudiantes.show', $s->estudiante_id) }}" class="btn btn-ghost btn-sm"><i class="fas fa-eye"></i> Ver estudiante</a>
                        @else
                            <span style="color:var(--n400);font-size:.8rem">{{ $s->observacion_promotor ?? 'Sin motivo registrado' }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9"><div class="empty-state" style="padding:2rem"><p>No hay solicitudes de matrícula en este estado.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        @if($solicitudes->hasPages())
            <div style="padding:1rem 1.25rem;border-top:1px solid var(--n200)">{{ $solicitudes->links() }}</div>
        @endif
    </div>
</div>
@endsection
