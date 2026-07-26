@extends('layouts.app')
@section('title','Historial Conductual')
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="/conductas">Conductas</a>
            <span style="margin:0 .3rem">/</span>
            <span>Historial</span>
        </div>
        <h1 class="page-title">Historial Conductual</h1>
        <p class="page-subtitle">Resumen acumulado de comportamiento por estudiante</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-history"></i> Resumen por Estudiante</div>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" class="form-control" id="tableSearch" placeholder="Buscar estudiante...">
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th style="color:var(--success)">😊 Positivas</th>
                        <th style="color:var(--danger)">😟 Negativas</th>
                        <th style="color:var(--warning)">👁 Observaciones</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historial as $h)
                    @php $balance = $h->positivas - $h->negativas; @endphp
                    <tr>
                        <td>
                            <div class="student-cell">
                                <div class="avatar">{{ strtoupper(substr($h->nombre, 0, 1)) }}</div>
                                <span style="font-weight:500">{{ $h->nombre }}</span>
                            </div>
                        </td>
                        <td>
                            @if($h->positivas > 0)
                                <span class="tag tag-success">+{{ $h->positivas }}</span>
                            @else
                                <span style="color:var(--n400)">—</span>
                            @endif
                        </td>
                        <td>
                            @if($h->negativas > 0)
                                <span class="tag tag-danger">{{ $h->negativas }}</span>
                            @else
                                <span style="color:var(--n400)">—</span>
                            @endif
                        </td>
                        <td>
                            @if($h->observaciones > 0)
                                <span class="tag tag-warning">{{ $h->observaciones }}</span>
                            @else
                                <span style="color:var(--n400)">—</span>
                            @endif
                        </td>
                        <td style="font-weight:600">{{ $h->total }}</td>
                        <td>
                            <span style="font-weight:700;font-size:1rem;color:{{ $balance > 0 ? 'var(--success)' : ($balance < 0 ? 'var(--danger)' : 'var(--n400)') }}">
                                {{ $balance > 0 ? '+' : '' }}{{ $balance }}
                            </span>
                        </td>
                        <td>
                            <a href="/conductas?search={{ urlencode($h->nombre) }}" class="btn btn-ghost btn-sm">
                                <i class="fas fa-eye"></i> Ver detalle
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state" style="padding:2rem">
                                <div class="empty-state-icon"><i class="fas fa-history"></i></div>
                                <p>Sin registros conductuales.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
