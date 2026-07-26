@extends('layouts.app')
@section('title','Orden de Mérito')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Orden de Mérito</span></div>
        <h1 class="page-title"><i class="fas fa-medal"></i> Orden de Mérito</h1>
        <p class="page-subtitle">Ranking de estudiantes por promedio general, dentro de su grado y sección</p>
    </div>
    @if($ranking->count())
        <a href="{{ route('reportes.ordenMerito', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-ghost">
            <i class="fas fa-file-csv"></i> Exportar CSV
        </a>
    @endif
</div>

<div class="card" style="margin-bottom:1rem">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end">
            <div>
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">Grado</label>
                <select name="grado_id" class="form-control" onchange="this.form.submit()">
                    @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => $gradoId])
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem">Sección</label>
                <select name="seccion" class="form-control" onchange="this.form.submit()">
                    @foreach($secciones as $s)
                        <option value="{{ $s->nombre }}" @selected($seccion == $s->nombre)>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-list-ol"></i> Ranking — {{ optional($grados->firstWhere('id',$gradoId))->nombre }} "{{ $seccion }}"</div>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead>
                <tr>
                    <th style="width:70px">Puesto</th>
                    <th>Estudiante</th>
                    <th>Código</th>
                    <th>Promedio</th>
                    <th>Nivel de logro</th>
                    <th>Conductas negativas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ranking as $r)
                <tr>
                    <td>
                        @if($r->puesto <= 3)
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;font-weight:800;color:#fff;background:{{ $r->puesto==1?'#d4af37':($r->puesto==2?'#a8a8a8':'#a0622d') }}">{{ $r->puesto }}</span>
                        @else
                            <strong>{{ $r->puesto }}.º</strong>
                        @endif
                    </td>
                    <td>{{ $r->nombre }}</td>
                    <td>{{ $r->codigo }}</td>
                    <td><strong>{{ $r->promedio ?? '—' }}</strong></td>
                    <td>
                        @if($r->nl)
                            <span style="font-weight:800;color:{{ nivel_logro_color($r->nl) }}">{{ $r->nl }}</span>
                            <span style="font-size:.75rem;color:var(--n500)">— {{ nivel_logro_texto($r->nl) }}</span>
                        @else
                            <span style="color:var(--n400)">Sin registro</span>
                        @endif
                    </td>
                    <td>{{ $r->conductas_neg }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state" style="padding:2rem"><p>No hay estudiantes matriculados en esta sección, o aún no tienen notas registradas.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
