@extends('layouts.app')
@section('title','Perfil Estudiante')
@section('content')
@php
    $nombre  = $estudiante->nombre.' '.$estudiante->apellido;
    $promedio= $promediosCurso->avg('promedio') ?? 0;
    $estado  = $promedio>=14?'aprobado':($promedio>=11?'riesgo':'desaprobado');
    $posit   = $conductas->where('tipo','positivo')->count();
    $negat   = $conductas->where('tipo','negativo')->count();
    $obsrv   = $conductas->where('tipo','observacion')->count();
@endphp

<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><a href="/estudiantes">Estudiantes</a><span style="margin:0 .3rem">/</span><span>{{ $nombre }}</span></div>
        <h1 class="page-title">Perfil del Estudiante</h1>
    </div>
    <div class="page-actions">
        <a href="{{ route('constancias.estudios', $estudiante->id) }}" target="_blank" class="btn btn-ghost btn-sm"><i class="fas fa-file-alt"></i> Constancia de Estudios</a>
        <a href="{{ route('constancias.matricula', $estudiante->id) }}" target="_blank" class="btn btn-ghost btn-sm"><i class="fas fa-file-signature"></i> Constancia de Matrícula</a>
        <a href="{{ route('constancias.conducta', $estudiante->id) }}" target="_blank" class="btn btn-ghost btn-sm"><i class="fas fa-file-medical"></i> Constancia de Conducta</a>
        @if(in_array(Auth::user()->rol,['promotor','docente','psicologa']))
            <button class="btn btn-success btn-sm" data-modal-open="modalConductaR"><i class="fas fa-plus"></i> Registrar Conducta</button>
        @endif
    </div>
</div>

<!-- Tarjeta perfil -->
<div class="card" style="margin-bottom:1rem">
    <div class="card-body" style="padding:1.5rem">
        <div style="display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap">
            <div style="width:80px;height:80px;border-radius:20px;background:linear-gradient(135deg,var(--primary),var(--primary-light));display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:800;font-family:'Nunito',sans-serif;flex-shrink:0;box-shadow:var(--shadow-md)">
                {{ strtoupper(substr($estudiante->nombre,0,1)) }}
            </div>
            <div style="flex:1;min-width:200px">
                <h2 style="font-family:'Nunito',sans-serif;font-size:1.4rem;font-weight:800;margin-bottom:.3rem">{{ $nombre }}</h2>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem">
                    <span class="tag tag-info">{{ $estudiante->grado ?? 'Sin grado' }}</span>
                    <span class="tag tag-neutral">Cód: {{ $estudiante->codigo }}</span>
                    @if($estado==='aprobado')<span class="tag tag-success">✓ Aprobado</span>
                    @elseif($estado==='riesgo')<span class="tag tag-warning">⚠ En riesgo</span>
                    @else<span class="tag tag-danger">✗ Desaprobado</span>@endif
                </div>
                @if($estudiante->fecha_nacimiento)
                <p style="font-size:.84rem;color:var(--n400)"><i class="fas fa-birthday-cake" style="margin-right:.3rem"></i>{{ \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->format('d/m/Y') }} ({{ \Carbon\Carbon::parse($estudiante->fecha_nacimiento)->age }} años)</p>
                @endif
            </div>
            <div style="display:flex;gap:1rem;flex-wrap:wrap">
                <div style="text-align:center;padding:.75rem 1.25rem;background:var(--primary-soft);border-radius:10px">
                    <div style="font-size:1.6rem;font-weight:800;font-family:'Nunito',sans-serif;color:{{ $promedio>=14?'var(--success)':($promedio>=11?'var(--warning)':'var(--danger)') }}">{{ number_format($promedio,1) }}</div>
                    <div style="font-size:.72rem;color:var(--n400);font-weight:600">PROMEDIO</div>
                </div>
                <div style="text-align:center;padding:.75rem 1.25rem;background:var(--success-soft);border-radius:10px">
                    <div style="font-size:1.6rem;font-weight:800;font-family:'Nunito',sans-serif;color:var(--success)">{{ $posit }}</div>
                    <div style="font-size:.72rem;color:var(--n400);font-weight:600">CONDUCTAS +</div>
                </div>
                <div style="text-align:center;padding:.75rem 1.25rem;background:var(--danger-soft);border-radius:10px">
                    <div style="font-size:1.6rem;font-weight:800;font-family:'Nunito',sans-serif;color:var(--danger)">{{ $negat }}</div>
                    <div style="font-size:.72rem;color:var(--n400);font-weight:600">CONDUCTAS −</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:1rem">
    <!-- Promedios por curso -->
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-chart-bar"></i> Promedios por Curso</div></div>
        <div class="card-body">
            @forelse($promediosCurso as $pc)
            @php $pct=min(100,($pc->promedio/20)*100); $cls=$pc->promedio>=14?'good':($pc->promedio>=11?'medium':'bad'); @endphp
            <div style="margin-bottom:.9rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
                    <span style="font-size:.85rem;font-weight:500">{{ $pc->nombre }}</span>
                    <span class="{{ $pc->promedio>=14?'nota-alta':($pc->promedio>=11?'nota-media':'nota-baja') }}">{{ number_format($pc->promedio,1) }}</span>
                </div>
                <div class="progress-bar-wrap"><div class="progress-bar-fill {{ $cls }}" style="width:{{ $pct }}%"></div></div>
            </div>
            @empty
            <div class="empty-state" style="padding:1.5rem"><div class="empty-state-icon"><i class="fas fa-clipboard"></i></div><p>Sin notas aún.</p></div>
            @endforelse
        </div>
    </div>
    <!-- Apoderados -->
    <div class="card">
        <div class="card-header"><div class="card-title"><i class="fas fa-users"></i> Apoderados</div></div>
        <div class="card-body" style="padding:0">
            @forelse($apoderados as $ap)
            <div style="display:flex;align-items:center;gap:.8rem;padding:.85rem 1.25rem;border-bottom:1px solid var(--n100)">
                <div class="avatar" style="background:var(--accent-soft);color:var(--accent)">{{ strtoupper(substr($ap->nombre,0,1)) }}</div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:.9rem">{{ $ap->nombre }}</div>
                    <div style="font-size:.78rem;color:var(--n400)">{{ ucfirst($ap->parentesco) }}</div>
                </div>
                @if($ap->telefono)<div style="font-size:.78rem;color:var(--n400)"><i class="fas fa-phone"></i> {{ $ap->telefono }}</div>@endif
            </div>
            @empty
            <div class="empty-state" style="padding:1.5rem"><div class="empty-state-icon"><i class="fas fa-user-friends"></i></div><p>Sin apoderados registrados.</p></div>
            @endforelse
        </div>
    </div>
</div>

@if($evolucion->count() >= 2)
<div class="card" style="margin-bottom:1rem">
    <div class="card-header"><div class="card-title"><i class="fas fa-chart-line"></i> Evolución del Promedio por Bimestre</div></div>
    <div class="card-body">
        <canvas class="bar-chart-simple" style="width:100%;height:200px"
            data-values='{{ json_encode($evolucion->pluck("promedio")->toArray()) }}'
            data-labels='{{ json_encode($evolucion->pluck("nombre")->toArray()) }}'></canvas>
    </div>
</div>
@endif

<!-- Notas -->
<div class="card" style="margin-bottom:1rem">
    <div class="card-header"><div class="card-title"><i class="fas fa-clipboard-list"></i> Últimas Evaluaciones</div><a href="/notas?estudiante={{ $estudiante->id }}" class="btn btn-ghost btn-sm">Ver todas</a></div>
    <div class="card-body" style="padding:0">
        <table>
            <thead><tr><th>Curso</th><th>Evaluación</th><th>Tipo</th><th>Nota</th><th>Periodo</th><th>Fecha</th></tr></thead>
            <tbody>
                @forelse($notas->take(8) as $n)
                <tr>
                    <td><span class="tag tag-info">{{ $n->curso }}</span></td>
                    <td style="font-size:.84rem">{{ $n->nombre_evaluacion }}</td>
                    <td><span class="tag {{ $n->tipo_evaluacion==='examen'?'tag-danger':($n->tipo_evaluacion==='tarea'?'tag-success':'tag-warning') }}">{{ ucfirst($n->tipo_evaluacion) }}</span></td>
                    <td><span class="nota-val {{ $n->nota>=14?'nota-alta':($n->nota>=11?'nota-media':'nota-baja') }}" style="font-size:1rem">{{ number_format($n->nota,1) }}</span><span style="color:var(--n400);font-size:.78rem"> /{{ $n->nota_maxima }}</span></td>
                    <td style="font-size:.8rem;color:var(--n400)">{{ $n->periodo }}</td>
                    <td style="font-size:.8rem;color:var(--n400)">{{ \Carbon\Carbon::parse($n->fecha)->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state" style="padding:1.5rem"><p>Sin evaluaciones.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Conductas -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-heart"></i> Historial Conductual</div>
        <div style="display:flex;gap:.4rem"><span class="tag tag-success">+{{ $posit }}</span><span class="tag tag-danger">−{{ $negat }}</span><span class="tag tag-warning">● {{ $obsrv }}</span></div>
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead><tr><th>Tipo</th><th>Descripción</th><th>Lugar</th><th>Reportado por</th><th>Fecha</th></tr></thead>
            <tbody>
                @forelse($conductas->take(6) as $c)
                <tr>
                    <td><span class="tag conducta-{{ $c->tipo }}">{{ ucfirst($c->tipo) }}</span></td>
                    <td style="font-size:.84rem;max-width:280px">{{ Str::limit($c->descripcion,90) }}</td>
                    <td style="font-size:.8rem;color:var(--n400)">{{ $c->lugar ?? '—' }}</td>
                    <td style="font-size:.82rem">{{ $c->reportador }}</td>
                    <td style="font-size:.8rem;color:var(--n400);white-space:nowrap">{{ \Carbon\Carbon::parse($c->fecha_hora)->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state" style="padding:1.5rem"><p>Sin registros conductuales.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(in_array(Auth::user()->rol, ['promotor','psicologa']))
<!-- Sesiones de Psicología -->
<div class="card" style="margin-top:1rem;border-left:3px solid #5e35b1">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-brain" style="color:#5e35b1"></i> Sesiones de Psicología</div>
        @if(Auth::user()->rol === 'psicologa')
        <button class="btn btn-primary btn-sm" data-modal-open="modalSesionR"><i class="fas fa-plus"></i> Nueva sesión</button>
        @endif
    </div>
    <div class="card-body" style="padding:0">
        <table>
            <thead><tr><th>Fecha</th><th>Motivo</th><th>Observaciones</th><th>Próxima cita</th><th>Psicólogo(a)</th></tr></thead>
            <tbody>
                @forelse($sesionesPsicologia as $sp)
                <tr>
                    <td style="font-size:.8rem;color:var(--n400);white-space:nowrap">{{ \Carbon\Carbon::parse($sp->fecha)->format('d/m/Y') }}</td>
                    <td style="font-size:.84rem">{{ $sp->motivo }}</td>
                    <td style="font-size:.82rem;color:var(--n500);max-width:260px">{{ Str::limit($sp->observaciones, 100) }}</td>
                    <td style="font-size:.8rem;color:var(--n400)">{{ $sp->proxima_cita ? \Carbon\Carbon::parse($sp->proxima_cita)->format('d/m/Y') : '—' }}</td>
                    <td style="font-size:.82rem">{{ $sp->psicologo_nombre }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="empty-state" style="padding:1.5rem"><p>Sin sesiones de psicología registradas.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(Auth::user()->rol === 'psicologa')
<div class="modal-overlay" id="modalSesionR">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fas fa-brain" style="color:#5e35b1;margin-right:.4rem"></i>Registrar Sesión de Psicología</div><button class="modal-close"><i class="fas fa-times"></i></button></div>
        <form action="{{ route('sesiones.store') }}" method="POST">
            @csrf
            <input type="hidden" name="estudiante_id" value="{{ $estudiante->id }}">
            <div class="modal-body">
                <div style="padding:.75rem;background:var(--primary-soft);border-radius:8px;margin-bottom:1rem;font-size:.85rem">
                    <i class="fas fa-user-graduate" style="color:var(--primary-light);margin-right:.4rem"></i>Estudiante: <strong>{{ $nombre }}</strong>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Fecha *</label><input type="date" name="fecha" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                    <div class="form-group"><label class="form-label">Próxima cita</label><input type="date" name="proxima_cita" class="form-control"></div>
                </div>
                <div class="form-group"><label class="form-label">Motivo *</label><input type="text" name="motivo" class="form-control" maxlength="160" required placeholder="Ej: Seguimiento por bajo rendimiento"></div>
                <div class="form-group"><label class="form-label">Observaciones *</label><textarea name="observaciones" class="form-control" rows="3" required placeholder="Detalle de la sesión..."></textarea></div>
                <div class="form-group"><label class="form-label">Acuerdos</label><textarea name="acuerdos" class="form-control" rows="2" placeholder="Compromisos o seguimiento acordado..."></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Sesión</button></div>
        </form>
    </div>
</div>
@endif
@endif

<!-- Modal conducta rápida -->
<div class="modal-overlay" id="modalConductaR">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fas fa-plus-circle" style="color:var(--accent);margin-right:.4rem"></i>Registrar Conducta</div><button class="modal-close"><i class="fas fa-times"></i></button></div>
        <form action="/conductas" method="POST">
            @csrf
            <input type="hidden" name="estudiante_id" value="{{ $estudiante->id }}">
            <div class="modal-body">
                <div style="padding:.75rem;background:var(--primary-soft);border-radius:8px;margin-bottom:1rem;font-size:.85rem">
                    <i class="fas fa-user-graduate" style="color:var(--primary-light);margin-right:.4rem"></i>Estudiante: <strong>{{ $nombre }}</strong>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group"><label class="form-label">Tipo *</label><select name="tipo" class="form-control" required><option value="positivo">✓ Positivo</option><option value="negativo">✗ Negativo</option><option value="observacion">● Observación</option></select></div>
                    <div class="form-group"><label class="form-label">Lugar</label><input type="text" name="lugar" class="form-control" placeholder="Aula, Patio..."></div>
                </div>
                <div class="form-group"><label class="form-label">Descripción *</label><textarea name="descripcion" class="form-control" rows="3" required placeholder="Describa el comportamiento..."></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost modal-close">Cancelar</button><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button></div>
        </form>
    </div>
</div>
@endsection
