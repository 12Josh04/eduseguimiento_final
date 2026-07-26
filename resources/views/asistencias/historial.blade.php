@extends('layouts.app')
@section('title','Historial de Asistencia')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Mi asistencia</span></div>
        <h1 class="page-title">Historial de Asistencia</h1>
        <p class="page-subtitle">Resumen personal de faltas, presentes y tardanzas</p>
    </div>
</div>
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:1rem">
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check"></i></div><div><div class="stat-value">{{ $stats['presente'] }}</div><div class="stat-label">Presentes</div></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="fas fa-times"></i></div><div><div class="stat-value">{{ $stats['falta'] }}</div><div class="stat-label">Faltas</div></div></div>
    <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-clock"></i></div><div><div class="stat-value">{{ $stats['tardanza'] }}</div><div class="stat-label">Tardanzas</div></div></div>
</div>
<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Fecha</th><th>Estado</th><th>Observación</th></tr></thead>
                <tbody>
                    @forelse($asistencias as $a)
                    <tr><td>{{ 
Carbon\Carbon::parse($a->fecha)->format('d/m/Y') }}</td><td><span class="tag {{ $a->estado === 'falta' ? 'tag-danger' : ($a->estado === 'tardanza' ? 'tag-warning' : 'tag-success') }}">{{ ucfirst($a->estado) }}</span></td><td>{{ $a->observacion ?? '—' }}</td></tr>
                    @empty
                    <tr><td colspan="3"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-calendar"></i></div><p>No hay asistencias registradas.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
