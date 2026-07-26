@extends('layouts.app')
@section('title','Reportes de asistencia')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Reportes</span></div>
        <h1 class="page-title">Reportes de Asistencia</h1>
        <p class="page-subtitle">Resumen mensual de registros de asistencia</p>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Mes</th><th>Total de registros</th></tr></thead>
                <tbody>
                    @forelse($porMes as $r)
                    <tr><td>{{ $r->mes }}</td><td>{{ $r->total }}</td></tr>
                    @empty
                    <tr><td colspan="2"><div class="empty-state" style="padding:2rem"><div class="empty-state-icon"><i class="fas fa-chart-pie"></i></div><p>No hay datos de asistencia.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
