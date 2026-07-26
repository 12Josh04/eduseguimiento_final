@extends('layouts.app')
@section('title', 'Docentes')
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <span>Docentes</span>
        </div>
        <h1 class="page-title">Docentes</h1>
        <p class="page-subtitle">Cuerpo docente agrupado por nivel, con sus cursos y grados asignados</p>
    </div>
    <a href="{{ route('usuarios.index') }}" class="btn btn-ghost">
        <i class="fas fa-user-plus"></i> Gestionar cuentas
    </a>
</div>

<div class="paneles-nivel">
    @php $hayDocentes = !empty($paneles['Primaria']) || !empty($paneles['Secundaria']); @endphp
    @if(!$hayDocentes)
        <div class="card"><div class="card-body">
            <div class="empty-state" style="padding:2rem"><p>Aún no hay docentes con asignaciones activas.</p></div>
        </div></div>
    @endif

    @foreach(['Primaria', 'Secundaria'] as $nivel)
        @if(!empty($paneles[$nivel]))
        <div class="nivel-bloque">
            <h2 class="nivel-titulo">
                <i class="fas {{ $nivel === 'Primaria' ? 'fa-child' : 'fa-user-graduate' }}"></i>
                {{ $nivel }}
            </h2>
            <div class="paneles-grid">
                @foreach($paneles[$nivel] as $d)
                    @php
                        $prom = null;
                        $iniciales = collect(explode(' ', trim($d->nombre)))->map(fn($p) => mb_strtoupper(mb_substr($p,0,1)))->take(2)->implode('');
                    @endphp
                    <a href="{{ route('docentes.show', $d->id) }}" class="panel-grado">
                        <div class="panel-grado-header">
                            <span class="panel-grado-nombre" style="display:flex;align-items:center;gap:.5rem">
                                <span class="avatar" style="width:30px;height:30px;font-size:.75rem">{{ $iniciales }}</span>
                                {{ $d->nombre }}
                            </span>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <div style="margin-bottom:.6rem;display:flex;flex-wrap:wrap;gap:.3rem">
                            @foreach($d->cursos->take(4) as $c)
                                <span class="tag tag-info">{{ $c }}</span>
                            @endforeach
                            @if($d->cursos->count() > 4)
                                <span class="tag" style="color:var(--n400)">+{{ $d->cursos->count() - 4 }}</span>
                            @endif
                        </div>
                        <div class="panel-grado-body">
                            <div class="panel-stat">
                                <span class="panel-stat-num">{{ $d->total_grados }}</span>
                                <span class="panel-stat-label">grado{{ $d->total_grados == 1 ? '' : 's' }}</span>
                            </div>
                            <div class="panel-stat">
                                <span class="panel-stat-num">{{ $d->total_estudiantes }}</span>
                                <span class="panel-stat-label">estudiantes</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
</div>

<style>
    .nivel-bloque{margin-bottom:1.75rem}
    .nivel-titulo{font-size:1rem;font-weight:800;color:var(--primary);margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
    .paneles-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:.85rem}
    .panel-grado{background:var(--surface);border:1px solid var(--n200);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:1rem;text-decoration:none;color:inherit;transition:var(--t);display:block}
    .panel-grado:hover{box-shadow:var(--shadow-md);transform:translateY(-2px);border-color:var(--primary-light)}
    .panel-grado-header{display:flex;justify-content:space-between;align-items:center;font-weight:700;color:var(--primary);margin-bottom:.6rem}
    .panel-grado-header i{color:var(--n400);font-size:.8rem}
    .panel-grado-body{display:flex;gap:1.25rem}
    .panel-stat{display:flex;flex-direction:column}
    .panel-stat-num{font-size:1.3rem;font-weight:800;color:var(--n800)}
    .panel-stat-label{font-size:.72rem;color:var(--n500);text-transform:uppercase;letter-spacing:.03em}
</style>
@endsection
