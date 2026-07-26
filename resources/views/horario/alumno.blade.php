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
        <h1 class="page-title">Mi Horario Escolar</h1>
        <p class="page-subtitle">Cursos, docentes y aulas — Año Escolar 2026</p>
    </div>
</div>

@if(!empty($sinHorario))
{{-- Sin horario --}}
<div class="card">
    <div class="card-body">
        <div class="empty-state" style="padding:3rem">
            <div class="empty-state-icon"><i class="fas fa-calendar-times"></i></div>
            <h3>Sin horario disponible</h3>
            <p>Tu horario aún no ha sido configurado.<br>Consulta con la administración del colegio.</p>
        </div>
    </div>
</div>

@else
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-calendar-week"></i> Horario Semanal</div>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th style="width:110px">Día</th>
                        <th style="width:140px">Hora</th>
                        <th>Curso</th>
                        <th>Docente</th>
                        <th>Aula</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dias as $numDia => $nombreDia)
                        @if(isset($horario[$numDia]))
                            @foreach($horario[$numDia] as $idx => $h)
                            <tr>
                                @if($idx === 0)
                                <td rowspan="{{ $horario[$numDia]->count() }}"
                                    style="background:var(--accent-soft);
                                           font-weight:700;
                                           color:var(--accent);
                                           vertical-align:middle;
                                           text-align:center;
                                           border-right:2px solid var(--accent)">
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
                                <td style="font-size:.85rem">
                                    <div class="student-cell" style="gap:.45rem">
                                        <div class="avatar" style="width:28px;height:28px;font-size:.7rem">
                                            {{ strtoupper(substr($h->docente, 0, 1)) }}
                                        </div>
                                        <span>{{ $h->docente }}</span>
                                    </div>
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

                    @if($horario->isEmpty())
                    <tr>
                        <td colspan="5">
                            <div class="empty-state" style="padding:2rem">
                                <div class="empty-state-icon"><i class="fas fa-calendar"></i></div>
                                <p>No hay clases programadas.</p>
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
