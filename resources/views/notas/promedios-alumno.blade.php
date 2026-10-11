@extends('layouts.app')
@section('title','Mis Promedios')
@section('content')

@php $esPrimaria = ($grado->nivel ?? null) === 'Primaria'; @endphp

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <span>Mis Promedios</span>
        </div>
        <h1 class="page-title">Mis Promedios</h1>
        <p class="page-subtitle">{{ $grado->nombre ?? '—' }} @if($grado ?? null) — {{ $grado->nivel }} @endif</p>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:1rem">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-star"></i></div>
        <div>
            <div class="stat-value">
                @if($promedioGeneral === null) —
                @elseif($esPrimaria) {{ nivel_logro_letra($promedioGeneral) }}
                @else {{ number_format($promedioGeneral, 1) }}
                @endif
            </div>
            <div class="stat-label">Promedio general</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-book-open"></i></div>
        <div><div class="stat-value">{{ $cursos->count() }}</div><div class="stat-label">Cursos con notas</div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-chart-bar"></i> Promedio por Curso</div>
    </div>
    <div class="card-body">
        @forelse($cursos as $c)
        @php
            $nl = $esPrimaria ? nivel_logro_letra($c->promedio) : null;
            $pct = min(100, ($c->promedio / 20) * 100);
            $cls = $c->promedio >= 14 ? 'good' : ($c->promedio >= 11 ? 'medium' : 'bad');
        @endphp
        <div style="margin-bottom:.9rem">
            <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
                <span style="font-size:.85rem;font-weight:500">{{ $c->nombre }}</span>
                @if($esPrimaria)
                    <span style="font-weight:700;color:{{ nivel_logro_color($nl) }}">{{ $nl }}</span>
                @else
                    <span class="{{ $c->promedio>=14?'nota-alta':($c->promedio>=11?'nota-media':'nota-baja') }}" style="font-size:.85rem">{{ number_format($c->promedio,1) }}</span>
                @endif
            </div>
            <div class="progress-bar-wrap"><div class="progress-bar-fill {{ $cls }}" style="width:{{ $pct }}%"></div></div>
        </div>
        @empty
        <div class="empty-state"><div class="empty-state-icon"><i class="fas fa-chart-bar"></i></div><p>Aún no tienes notas registradas.</p></div>
        @endforelse
    </div>
</div>

@endsection
