@extends('layouts.app')
@section('title','Auditoría')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Auditoría</span></div>
        <h1 class="page-title">Bitácora de Auditoría</h1>
        <p class="page-subtitle">Seguimiento de cambios, accesos y acciones críticas del sistema</p>
    </div>
</div>
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Usuario</th><th>Rol</th><th>Acción</th><th>Módulo</th><th>Fecha</th></tr></thead>
                <tbody>
                    @forelse($registros as $r)
                    <tr>
                        <td>{{ $r->usuario ?? 'Sistema' }}</td>
                        <td>{{ $r->rol ?? '—' }}</td>
                        <td>{{ $r->accion }}</td>
                        <td>{{ $r->modulo }}</td>
                        <td>{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-history"></i></div><p>No hay registros de auditoría.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registros->hasPages())
        <div style="padding:1rem 1.25rem;border-top:1px solid var(--n200)">{{ $registros->links() }}</div>
        @endif
    </div>
</div>
@endsection
