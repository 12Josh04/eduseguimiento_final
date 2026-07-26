@extends('layouts.app')
@section('title','Reportes')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Reportes</span></div>
        <h1 class="page-title">Reportes Académicos</h1>
        <p class="page-subtitle">Resumen de rendimiento por curso</p>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Curso</th><th>Promedio</th></tr></thead>
                <tbody>
                    @forelse($promedios as $p)
                    <tr><td>{{ $p->nombre }}</td><td>{{ number_format($p->promedio, 1) }}</td></tr>
                    @empty
                    <tr><td colspan="2"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-chart-line"></i></div><p>No hay reportes disponibles.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
