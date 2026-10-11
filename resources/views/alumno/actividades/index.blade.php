@extends('layouts.app')
@section('title', 'Mis Actividades')
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Mis Actividades</span></div>
        <h1 class="page-title"><i class="fas fa-tasks"></i> Mis Actividades</h1>
        <p class="page-subtitle">Trabajos, exámenes y prácticas asignados por tus docentes</p>
    </div>
</div>

@forelse($actividades as $cursoNombre => $items)
<div class="card" style="margin-bottom:1rem">
    <div class="card-header"><div class="card-title"><i class="fas fa-book"></i> {{ $cursoNombre }}</div></div>
    <div class="card-body" style="padding:0">
        @foreach($items as $it)
        <a href="{{ route('alumno.actividad.show', $it->actividad_id) }}" style="text-decoration:none;color:inherit;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;padding:.9rem 1.1rem;border-bottom:1px solid var(--n200)">
            <div style="flex:1;min-width:200px">
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                    <strong style="font-size:.9rem;color:var(--n800)">{{ $it->titulo }}</strong>
                    <span class="tag {{ $it->tipo_evaluacion==='examen'?'tag-danger':($it->tipo_evaluacion==='tarea'?'tag-success':'tag-warning') }}" style="font-size:.68rem">{{ ucfirst($it->tipo_evaluacion) }}</span>
                </div>
                <p style="font-size:.78rem;color:var(--n500);margin-top:.25rem">
                    {{ $it->periodo_nombre }}
                    @if($it->fecha_entrega) · Entrega: {{ \Carbon\Carbon::parse($it->fecha_entrega)->format('d/m/Y') }}@endif
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:.75rem">
                @if($it->estado === 'calificado')
                    @if($it->nota_letra)
                        <span style="font-weight:800;font-size:1.1rem;color:{{ nivel_logro_color($it->nota_letra) }}">{{ $it->nota_letra }}</span>
                    @else
                        <span style="font-weight:800;font-size:1.1rem;color:{{ $it->nota>=14?'var(--success)':($it->nota>=11?'var(--warning)':'var(--danger)') }}">{{ number_format($it->nota,1) }}</span>
                    @endif
                    <span class="tag tag-success">Calificado</span>
                @elseif($it->estado === 'entregado')
                    <span class="tag tag-warning"><i class="fas fa-inbox"></i> Entregado</span>
                @else
                    <span class="tag" style="background:var(--n100);color:var(--n500)"><i class="fas fa-clock"></i> Pendiente</span>
                @endif
                <i class="fas fa-chevron-right" style="color:var(--n400);font-size:.75rem"></i>
            </div>
        </a>
        @endforeach
    </div>
</div>
@empty
<div class="card"><div class="card-body">
    <div class="empty-state" style="padding:3rem">
        <div class="empty-state-icon"><i class="fas fa-tasks"></i></div>
        <h3 style="margin-bottom:.5rem">Sin actividades por ahora</h3>
        <p>Cuando tus docentes creen trabajos, exámenes o prácticas, aparecerán aquí.</p>
    </div>
</div></div>
@endforelse
@endsection
