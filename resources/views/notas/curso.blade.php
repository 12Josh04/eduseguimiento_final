@extends('layouts.app')
@section('title', $curso->nombre)
@section('content')

<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i></a>
            <span style="margin:0 .3rem">/</span>
            <a href="{{ route('notas.index') }}">Notas</a>
            <span style="margin:0 .3rem">/</span>
            <a href="{{ route('notas.index', ['grado_id' => $grado->id, 'seccion' => $seccion]) }}">{{ $grado->nombre }} "{{ $seccion }}"</a>
            <span style="margin:0 .3rem">/</span>
            <span>{{ $curso->nombre }}</span>
        </div>
        <h1 class="page-title"><i class="fas fa-book"></i> {{ $curso->nombre }}</h1>
        <p class="page-subtitle">
            {{ $grado->nombre }} "{{ $seccion }}" — {{ $asignacion->docente_nombre ?? 'Sin docente asignado' }}
            <span class="tag" style="margin-left:.5rem;background:{{ $pctClases >= 75 ? 'var(--success-soft)' : ($pctClases >= 40 ? 'var(--warning-soft)' : 'var(--danger-soft)') }};color:{{ $pctClases >= 75 ? 'var(--success)' : ($pctClases >= 40 ? 'var(--warning)' : 'var(--danger)') }}">
                <i class="fas fa-chalkboard"></i> {{ $pctClases }}% clases desarrolladas
            </span>
        </p>
    </div>
    <a href="{{ route('notas.index', ['grado_id' => $grado->id, 'seccion' => $seccion]) }}" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Volver a cursos
    </a>
</div>

<div class="grid-2" style="align-items:start;margin-bottom:1rem">

    {{-- ── Clases Desarrolladas ──────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-chalkboard"></i> Clases Desarrolladas</div>
            @if(in_array($rol, ['promotor','docente']))
            <button class="btn btn-primary btn-sm" data-modal-open="modalClase">
                <i class="fas fa-plus"></i> Nueva clase
            </button>
            @endif
        </div>
        <div class="card-body" style="padding:0;max-height:420px;overflow-y:auto">
            @forelse($clases as $c)
                <div style="padding:.9rem 1.1rem;border-bottom:1px solid var(--n200)">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem">
                        <strong style="font-size:.9rem">{{ $c->tema }}</strong>
                        <span style="font-size:.75rem;color:var(--n400);white-space:nowrap">{{ \Carbon\Carbon::parse($c->fecha)->format('d/m/Y') }}</span>
                    </div>
                    @if($c->descripcion)
                        <p style="font-size:.82rem;color:var(--n500);margin-top:.3rem">{{ $c->descripcion }}</p>
                    @endif
                    @if(in_array($rol, ['promotor','docente']))
                    <form action="{{ route('notas.clases.destroy', $c->id) }}" method="POST" style="margin-top:.4rem" onsubmit="return confirm('¿Eliminar esta clase?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);padding:.15rem .5rem">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </form>
                    @endif
                </div>
            @empty
                <div class="empty-state" style="padding:2rem">
                    <div class="empty-state-icon"><i class="fas fa-chalkboard"></i></div>
                    <p>Aún no hay clases registradas para este curso.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── Resumen del curso ──────────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-pie"></i> Resumen del Curso</div></div>
        <div class="card-body">
            <div style="margin-bottom:1.1rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;font-size:.85rem">
                    <span style="font-weight:600">Clases desarrolladas</span>
                    <span>{{ $clases->count() }} / 16 sesiones (año)</span>
                </div>
                <div class="progress-bar-wrap"><div class="progress-bar-fill {{ $pctClases >= 75 ? 'good' : ($pctClases >= 40 ? 'medium' : 'bad') }}" style="width:{{ $pctClases }}%"></div></div>
            </div>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap">
                <div style="text-align:center;padding:.75rem 1.1rem;background:var(--primary-soft);border-radius:10px">
                    <div style="font-size:1.5rem;font-weight:800;color:var(--primary)">{{ $gradebook->count() }}</div>
                    <div style="font-size:.7rem;color:var(--n400);font-weight:600">ESTUDIANTES</div>
                </div>
                @php $promGeneral = $gradebook->pluck('promedio_final')->filter(fn($v)=>$v!==null); @endphp
                <div style="text-align:center;padding:.75rem 1.1rem;background:var(--success-soft);border-radius:10px">
                    <div style="font-size:1.5rem;font-weight:800;color:{{ $promGeneral->avg() >= 14 ? 'var(--success)' : ($promGeneral->avg() >= 11 ? 'var(--warning)' : 'var(--danger)') }}">
                        @if(!$promGeneral->count())
                            —
                        @elseif($grado->nivel === 'Primaria')
                            {{ nivel_logro_letra($promGeneral->avg()) }}
                        @else
                            {{ number_format($promGeneral->avg(),1) }}
                        @endif
                    </div>
                    <div style="font-size:.7rem;color:var(--n400);font-weight:600">PROMEDIO SECCIÓN</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Actividades del Bimestre (trabajos con indicaciones, calificadas por estudiante) ──────── --}}
<div class="card" style="margin-bottom:1rem">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-tasks"></i> Actividades del Bimestre</div>
        @if(in_array($rol, ['promotor','docente']))
        <button class="btn btn-primary btn-sm" data-modal-open="modalActividad">
            <i class="fas fa-plus"></i> Nueva actividad
        </button>
        @endif
    </div>
    <div class="card-body" style="padding:0">
        @forelse($actividades as $act)
            <div style="padding:.9rem 1.1rem;border-bottom:1px solid var(--n200);display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                <div style="flex:1;min-width:220px">
                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                        <strong style="font-size:.9rem">{{ $act->titulo }}</strong>
                        <span class="tag {{ $act->tipo_evaluacion==='examen'?'tag-danger':($act->tipo_evaluacion==='tarea'?'tag-success':'tag-warning') }}" style="font-size:.68rem">{{ ucfirst($act->tipo_evaluacion) }}</span>
                        <span class="tag tag-info" style="font-size:.68rem">{{ $act->periodo_nombre }}</span>
                    </div>
                    <p style="font-size:.78rem;color:var(--n500);margin-top:.25rem">
                        @if($act->fecha_entrega)<i class="fas fa-calendar" style="margin-right:.3rem"></i>Entrega: {{ \Carbon\Carbon::parse($act->fecha_entrega)->format('d/m/Y') }}@endif
                        @if($act->archivo_path)<i class="fas fa-paperclip" style="margin-left:.6rem;margin-right:.2rem"></i>Con indicaciones adjuntas @endif
                    </p>
                </div>
                <div style="display:flex;align-items:center;gap:1rem">
                    <div style="text-align:center">
                        <div style="font-size:.72rem;color:var(--n400)">Calificados</div>
                        <div style="font-weight:800">{{ $act->total_calificados }}/{{ $act->total_estudiantes }}</div>
                    </div>
                    @if($act->total_entregados > 0)
                        <span class="tag tag-warning">{{ $act->total_entregados }} por revisar</span>
                    @endif
                    <a href="{{ route('actividades.show', $act->id) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-clipboard-check"></i> {{ in_array($rol,['promotor','docente']) ? 'Calificar' : 'Ver' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="empty-state" style="padding:2rem">
                <div class="empty-state-icon"><i class="fas fa-tasks"></i></div>
                <p>Aún no hay actividades creadas para este curso. Crea trabajos, exámenes o prácticas y sus notas se sumarán automáticamente al bimestre.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- ── Libro de Calificaciones: estudiantes x bimestre ──────────────────────── --}}
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-table"></i> Libro de Calificaciones</div>
        <span style="font-size:.8rem;color:var(--n400)">Haz clic en un estudiante para ver todos sus cursos</span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        @foreach($periodos as $p)<th style="text-align:center">{{ $p->nombre }}</th>@endforeach
                        <th style="text-align:center">Promedio Final</th>
                        <th style="text-align:center">Evaluaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gradebook as $est)
                    <tr>
                        <td>
                            <a href="{{ route('notas.estudiante.cursos', ['estudiante' => $est->id, 'grado_id' => $grado->id, 'seccion' => $seccion]) }}" style="text-decoration:none;color:inherit">
                                <div class="student-cell">
                                    <div class="avatar">{{ strtoupper(substr($est->nombre,0,1)) }}</div>
                                    <span style="font-weight:500">{{ $est->nombre }}</span>
                                    <i class="fas fa-chevron-right" style="color:var(--n400);font-size:.75rem;margin-left:.3rem"></i>
                                </div>
                            </a>
                        </td>
                        @foreach($periodos as $p)
                            @php $b = $est->bimestres[$p->id] ?? null; @endphp
                            <td style="text-align:center">
                                @if($b !== null)
                                    @if($grado->nivel === 'Primaria')
                                        <span style="font-weight:700;color:{{ nivel_logro_color(nivel_logro_letra($b)) }}">{{ nivel_logro_letra($b) }}</span>
                                    @else
                                        <span class="{{ $b>=14?'nota-alta':($b>=11?'nota-media':'nota-baja') }}" style="font-weight:700">{{ number_format($b,1) }}</span>
                                    @endif
                                @else
                                    <span style="color:var(--n400)">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td style="text-align:center">
                            @if($est->promedio_final !== null)
                                @if($grado->nivel === 'Primaria')
                                    <span style="font-weight:800;font-size:1rem;color:{{ nivel_logro_color(nivel_logro_letra($est->promedio_final)) }}">{{ nivel_logro_letra($est->promedio_final) }}</span>
                                @else
                                    <span class="{{ $est->promedio_final>=14?'nota-alta':($est->promedio_final>=11?'nota-media':'nota-baja') }}" style="font-weight:800;font-size:1rem">{{ number_format($est->promedio_final,1) }}</span>
                                @endif
                            @else
                                <span style="color:var(--n400)">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;color:var(--n400);font-size:.82rem">{{ $est->total_evaluaciones }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ 3 + count($periodos) }}"><div class="empty-state" style="padding:2rem"><p>No hay estudiantes matriculados en esta sección.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Llenado de Libreta: Nivel de Logro y conclusión descriptiva, a mano por el docente ──── --}}
@if(in_array($rol, ['promotor','docente']))
<div class="card" style="margin-top:1rem">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-file-signature"></i> Llenado de Libreta (Nivel de Logro)</div>
        <span style="font-size:.78rem;color:var(--n500)">Estos son los mismos recuadros que verá el estudiante en su libreta oficial</span>
    </div>
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
            <table class="tabla-libreta">
                <thead>
                    <tr>
                        <th style="min-width:160px">Estudiante</th>
                        @foreach($periodos as $p)<th style="text-align:center;min-width:180px">{{ $p->nombre }}</th>@endforeach
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gradebook as $est)
                    @php $formId = 'lib-'.$est->id; @endphp
                    <tr>
                        <td>
                            <div class="student-cell">
                                <div class="avatar">{{ strtoupper(substr($est->nombre,0,1)) }}</div>
                                <span style="font-weight:500;font-size:.85rem">{{ $est->nombre }}</span>
                            </div>
                        </td>
                        @foreach($periodos as $p)
                            @php $l = $est->libreta[$p->id]; @endphp
                            <td>
                                <div style="display:flex;gap:.35rem;align-items:flex-start">
                                    <select form="{{ $formId }}" name="periodos[{{ $p->id }}][nivel_logro]" class="form-control nl-select {{ $l['nivel_logro'] ? 'nl-'.$l['nivel_logro'] : '' }}" style="width:66px;flex:none;font-weight:800;text-align:center" onchange="this.className='form-control nl-select'+(this.value?' nl-'+this.value:'')">
                                        <option value="">—</option>
                                        <option value="AD" {{ $l['nivel_logro']==='AD'?'selected':'' }}>AD</option>
                                        <option value="A" {{ $l['nivel_logro']==='A'?'selected':'' }}>A</option>
                                        <option value="B" {{ $l['nivel_logro']==='B'?'selected':'' }}>B</option>
                                        <option value="C" {{ $l['nivel_logro']==='C'?'selected':'' }}>C</option>
                                    </select>
                                    <input form="{{ $formId }}" type="text" name="periodos[{{ $p->id }}][conclusion]" class="form-control" style="font-size:.78rem"
                                           value="{{ $l['conclusion'] }}"
                                           placeholder="{{ $l['sugerido'] ? 'Sugerido: '.$l['sugerido'] : 'Conclusión descriptiva...' }}">
                                </div>
                            </td>
                        @endforeach
                        <td style="text-align:center">
                            <form id="{{ $formId }}" action="{{ route('notas.libreta.store', $curso->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="estudiante_id" value="{{ $est->id }}">
                                <button type="submit" class="btn btn-primary btn-sm btn-icon-only" title="Guardar libreta de este estudiante">
                                    <i class="fas fa-save"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ 2 + count($periodos) }}"><div class="empty-state" style="padding:2rem"><p>No hay estudiantes matriculados en esta sección.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>
    .tabla-libreta td{vertical-align:top;padding-top:.6rem}
    .nl-select.nl-AD{color:#2e7d32;border-color:#2e7d32}
    .nl-select.nl-A{color:#1565c0;border-color:#1565c0}
    .nl-select.nl-B{color:#e08a00;border-color:#e08a00}
    .nl-select.nl-C{color:#c62828;border-color:#c62828}
</style>
@endif

{{-- Modal nueva clase --}}
@if(in_array($rol, ['promotor','docente']))
<div class="modal-overlay" id="modalClase">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-chalkboard" style="color:var(--primary-light);margin-right:.4rem"></i> Nueva Clase Desarrollada</div>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('notas.clases.store', $curso->id) }}" method="POST">
            @csrf
            <input type="hidden" name="grado_id" value="{{ $grado->id }}">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input type="date" name="fecha" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tema *</label>
                    <input type="text" name="tema" class="form-control" maxlength="160" required placeholder="Ej: Ecuaciones de primer grado">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" placeholder="Detalle de lo desarrollado en la sesión..."></textarea>
                </div>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;color:var(--n500)">
                    <input type="checkbox" name="seccion_especifica" value="1">
                    Registrar solo para la sección "{{ $seccion }}" (por defecto aplica a ambas secciones del grado)
                </label>
                <input type="hidden" name="seccion" value="{{ $seccion }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost modal-close">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Clase</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Modal nueva actividad --}}
@if(in_array($rol, ['promotor','docente']))
<div class="modal-overlay" id="modalActividad">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fas fa-tasks" style="color:var(--primary-light);margin-right:.4rem"></i> Nueva Actividad</div>
            <button class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('actividades.store', $curso->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="grado_id" value="{{ $grado->id }}">
            <div class="modal-body">
                <div style="padding:.75rem 1rem;background:var(--primary-soft);border-radius:8px;margin-bottom:1rem;font-size:.84rem;color:var(--primary)">
                    <i class="fas fa-info-circle"></i>
                    Crea un trabajo, examen o práctica con indicaciones. El estudiante lo verá, podrá entregarlo, y la nota que registres se sumará automáticamente al promedio del bimestre.
                </div>
                <div class="form-group">
                    <label class="form-label">Título *</label>
                    <input type="text" name="titulo" class="form-control" maxlength="160" required placeholder="Ej: Trabajo N.º 1 — Ecuaciones lineales">
                </div>
                <div class="form-group">
                    <label class="form-label">Indicaciones para el estudiante *</label>
                    <textarea name="instrucciones" class="form-control" rows="4" required placeholder="Describe qué debe hacer el estudiante, formato de entrega, criterios de evaluación..."></textarea>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo_evaluacion" class="form-control" required onchange="sugerirPeso(this, 'pesoActividad')">
                            <option value="tarea" data-peso="1" selected>Tarea</option>
                            <option value="practica" data-peso="1.5">Práctica calificada</option>
                            <option value="examen" data-peso="2">Examen</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Periodo *</label>
                        <select name="periodo_id" class="form-control" required>
                            @foreach($periodos as $p)<option value="{{ $p->id }}">{{ $p->nombre }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Fecha de entrega</label>
                        <input type="date" name="fecha_entrega" class="form-control" value="{{ now()->addDays(7)->format('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Peso de la actividad</label>
                        <input type="number" name="peso" id="pesoActividad" class="form-control" value="1" min="0.1" max="10" step="0.1">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Archivo de indicaciones (opcional)</label>
                    <input type="file" name="archivo" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;color:var(--n500)">
                    <input type="checkbox" name="seccion_especifica" value="1">
                    Asignar solo a la sección "{{ $seccion }}" (por defecto aplica a ambas secciones del grado)
                </label>
                <input type="hidden" name="seccion" value="{{ $seccion }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost modal-close">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear Actividad</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function sugerirPeso(select, inputId) {
    const opt = select.options[select.selectedIndex];
    if (opt.dataset.peso) document.getElementById(inputId).value = opt.dataset.peso;
}
</script>
@endpush
