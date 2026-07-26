@extends('layouts.app')
@section('title','Mi Horario')
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <span>Mi Horario</span>
        </div>
        <h1 class="page-title">Mi Horario Semanal</h1>
        <p class="page-subtitle">Cursos asignados — Año Escolar 2026</p>
    </div>
</div>

{{-- Resumen de cursos asignados --}}
@if($cursos->isNotEmpty())
<div class="card" style="margin-bottom:1rem">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-book"></i> Mis Cursos Asignados</div>
        <span style="font-size:.8rem;color:var(--n400)">{{ $cursos->count() }} asignación(es)</span>
    </div>
    <div class="card-body" style="display:flex;gap:.6rem;flex-wrap:wrap;padding:1rem 1.25rem">
        @foreach($cursos as $c)
        <div style="display:flex;align-items:center;gap:.5rem;padding:.5rem 1rem;
                    background:var(--primary-soft);border-radius:8px;font-size:.85rem">
            <i class="fas fa-book-open" style="color:var(--primary-light)"></i>
            <strong style="color:var(--primary)">{{ $c->curso }}</strong>
            <span style="color:var(--n400)">—</span>
            <span style="color:var(--n600)">{{ $c->grado }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Horario semanal --}}
@if($horario->isEmpty())
<div class="card">
    <div class="card-body">
        <div class="empty-state" style="padding:3rem">
            <div class="empty-state-icon"><i class="fas fa-calendar-times"></i></div>
            <h3>Sin horario registrado</h3>
            <p>Aún no tienes bloques de horario configurados.<br>Contacta al administrador del sistema.</p>
        </div>
    </div>
</div>

@else
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-calendar-week"></i> Horario por Día</div>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:110px">Día</th>
                        <th style="width:140px">Hora</th>
                        <th>Curso</th>
                        <th>Grado</th>
                        <th>Aula</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dias as $numDia => $nombreDia)
                        @if(isset($horario[$numDia]))
                            @foreach($horario[$numDia] as $idx => $h)
                            <tr>
                                {{-- Celda de día con rowspan --}}
                                @if($idx === 0)
                                <td rowspan="{{ $horario[$numDia]->count() }}"
                                    style="background:var(--primary-soft);
                                           font-weight:700;
                                           color:var(--primary);
                                           vertical-align:middle;
                                           text-align:center;
                                           border-right:2px solid var(--primary-light)">
                                    {{ $nombreDia }}
                                </td>
                                @endif

                                <td style="font-family:monospace;font-size:.85rem;white-space:nowrap">
                                    <i class="fas fa-clock" style="color:var(--n400);margin-right:.3rem"></i>
                                    {{ substr($h->hora_inicio, 0, 5) }} – {{ substr($h->hora_fin, 0, 5) }}
                                </td>
                                <td>
                                    <span class="tag tag-info">{{ $h->curso }}</span>
                                </td>
                                <td style="color:var(--n600);font-size:.85rem">
                                    {{ $h->grado }}
                                </td>
                                <td>
                                    @if($h->aula)
                                    <span style="display:inline-flex;align-items:center;gap:.35rem;font-size:.84rem">
                                        <i class="fas fa-door-open" style="color:var(--accent)"></i>
                                        {{ $h->aula }}
                                    </span>
                                    @else
                                    <span style="color:var(--n400)">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
