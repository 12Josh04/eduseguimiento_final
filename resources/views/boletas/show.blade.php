@extends('layouts.app')
@section('title','Libreta de Notas')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Libreta de Notas</span></div>
        <h1 class="page-title">Libreta de Notas</h1>
        <p class="page-subtitle">{{ $estudiante->nombre }} {{ $estudiante->apellido }} — {{ $estudiante->grado ?? '—' }} "{{ $estudiante->seccion ?? '—' }}"</p>
    </div>
    <div style="display:flex;gap:.5rem" class="no-print">
        <button class="btn btn-ghost" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
        <a href="{{ url()->current() }}?pdf=1" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Descargar PDF</a>
    </div>
</div>

<style>
    .libreta{background:#fff;border:1px solid var(--n200);border-radius:var(--radius);box-shadow:var(--shadow-sm);padding:1.5rem;font-size:.82rem;color:#111}
    .libreta table{width:100%;border-collapse:collapse}
    .libreta th,.libreta td{border:1px solid #9aa3b2;padding:.35rem .5rem;vertical-align:middle}
    .libreta .titulo-anio{text-align:center;font-weight:800;font-size:1rem;margin-bottom:.75rem;color:var(--primary)}
    .lb-datos{width:100%;border-collapse:collapse;margin-bottom:1rem}
    .lb-datos td{border:1px solid #9aa3b2;padding:.4rem .6rem}
    .lb-datos .lb-label{background:var(--primary-soft);font-weight:700;width:22%;color:var(--primary)}
    .lb-logo{width:110px;text-align:center;vertical-align:middle}
    .lb-logo img,.lb-logo .crest{width:70px;height:70px;border-radius:50%;object-fit:cover;margin:0 auto}
    .area-header{background:var(--primary);color:#fff;font-weight:700;text-align:center}
    .bim-header{background:var(--primary-light);color:#fff;text-align:center;font-weight:700}
    .col-header{background:var(--n100);text-align:center;font-weight:700;font-size:.75rem}
    .nl-cell{text-align:center;font-weight:800;width:32px}
    .nl-AD{color:#2e7d32}.nl-A{color:#1565c0}.nl-B{color:#e08a00}.nl-C{color:#c62828}
    .area-nombre{background:var(--n50);font-weight:700;writing-mode:horizontal-tb}
    .conclusion-txt{font-size:.72rem;color:var(--n600)}
    .leyenda-tabla td{padding:.5rem .6rem}
    .leyenda-letra{font-weight:800;text-align:center;width:40px;font-size:1rem}
    .puesto-box{display:inline-flex;align-items:center;gap:.4rem;background:var(--accent-soft);color:var(--accent);border-radius:8px;padding:.35rem .8rem;font-weight:700;font-size:.85rem}
    @media print{
        .no-print,.navbar,.sidebar,.page-header .no-print{display:none !important}
        .main-content{margin:0 !important;padding:0 !important}
        .libreta{box-shadow:none;border:none}
    }
</style>

<div class="libreta">
    <div class="titulo-anio">LIBRETA DE NOTAS — {{ $anioEscolar }}</div>

    <table class="lb-datos">
        <tr>
            <td class="lb-label">DRE</td><td>{{ $dre }}</td>
            <td class="lb-label">UGEL</td><td>{{ $ugel }}</td>
            <td class="lb-logo" rowspan="5"><div class="crest" style="background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem">{{ mb_strtoupper(substr($colegio,0,2)) }}</div></td>
        </tr>
        <tr>
            <td class="lb-label">Nivel</td><td>{{ $nivelEducativo }}</td>
            <td class="lb-label">Código Modular</td><td>{{ $codigoModular ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lb-label">Institución educativa</td><td colspan="3">{{ $colegio }}</td>
        </tr>
        <tr>
            <td class="lb-label">Grado</td><td>{{ $estudiante->grado ?? '—' }}</td>
            <td class="lb-label">Sección</td><td>{{ $estudiante->seccion ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lb-label">Estudiante</td><td colspan="3">{{ mb_strtoupper($estudiante->apellido.', '.$estudiante->nombre) }}</td>
        </tr>
        <tr>
            <td class="lb-label">Código del estudiante</td><td>{{ $estudiante->codigo }}</td>
            <td class="lb-label">DNI</td><td>{{ $estudiante->dni ?? '—' }}</td>
            <td></td>
        </tr>
        <tr>
            <td class="lb-label">Docente / Tutor(a)</td><td colspan="4">{{ $docenteTutor ?? 'Por asignar' }}</td>
        </tr>
    </table>

    @if($ordenMerito)
        <p style="margin-bottom:.75rem"><span class="puesto-box"><i class="fas fa-medal"></i> Orden de mérito: {{ $ordenMerito['puesto'] }}.º de {{ $ordenMerito['total'] }} en {{ $estudiante->grado }} "{{ $estudiante->seccion }}"</span></p>
    @endif

    <div style="overflow-x:auto">
    <table>
        <thead>
            <tr>
                <th class="col-header" rowspan="2" style="width:16%">Área curricular</th>
                <th class="col-header" rowspan="2" style="width:16%">Competencias / Curso</th>
                @foreach($periodos as $periodo)
                    <th class="bim-header" colspan="2">{{ mb_strtoupper($periodo->nombre) }}</th>
                @endforeach
                <th class="col-header" rowspan="2" style="width:8%">NL final</th>
            </tr>
            <tr>
                @foreach($periodos as $periodo)
                    <th class="col-header">NL</th>
                    <th class="col-header">Conclusión descriptiva</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($areas as $areaNombre => $cursos)
                @foreach($cursos as $i => $curso)
                <tr>
                    @if($i === 0)
                        <td class="area-nombre" rowspan="{{ count($cursos) }}">{{ $areaNombre }}</td>
                    @endif
                    <td>{{ $curso['nombre'] }}</td>
                    @foreach($periodos as $periodo)
                        @php $b = $curso['bimestres'][$periodo->id] ?? ['nl'=>null,'conclusion'=>null]; @endphp
                        <td class="nl-cell {{ $b['nl'] ? 'nl-'.$b['nl'] : '' }}">{{ $b['nl'] ?? '' }}</td>
                        <td class="conclusion-txt">{{ $b['conclusion'] ?? '' }}</td>
                    @endforeach
                    <td class="nl-cell {{ $curso['nl_final'] ? 'nl-'.$curso['nl_final'] : '' }}">{{ $curso['nl_final'] ?? '' }}</td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="{{ 3 + count($periodos)*2 }}"><div class="empty-state" style="padding:1.5rem"><p>Aún no hay calificaciones registradas.</p></div></td></tr>
            @endforelse

            @if(count($areas))
            <tr>
                <td colspan="{{ 2 + count($periodos)*2 }}" style="text-align:right;font-weight:700">Promedio general y nivel de logro alcanzado</td>
                <td class="nl-cell {{ $nlGeneral ? 'nl-'.$nlGeneral : '' }}">{{ $nlGeneral ?? '' }}</td>
            </tr>
            @endif
        </tbody>
    </table>
    </div>

    <table class="leyenda-tabla" style="margin-top:1rem">
        <tr><td class="leyenda-letra nl-AD">AD</td><td><strong>LOGRO DESTACADO.</strong> El estudiante evidencia un nivel superior a lo esperado, con aprendizajes que van más allá de la competencia.</td></tr>
        <tr><td class="leyenda-letra nl-A">A</td><td><strong>LOGRO ESPERADO.</strong> El estudiante evidencia el nivel esperado, con manejo satisfactorio en las tareas propuestas.</td></tr>
        <tr><td class="leyenda-letra nl-B">B</td><td><strong>EN PROCESO.</strong> El estudiante está próximo al nivel esperado y requiere acompañamiento para lograrlo.</td></tr>
        <tr><td class="leyenda-letra nl-C">C</td><td><strong>EN INICIO.</strong> El estudiante muestra progreso mínimo y requiere mayor tiempo de acompañamiento.</td></tr>
    </table>

    <table style="margin-top:1rem">
        <thead>
            <tr>
                <th class="col-header" rowspan="2">Bimestre</th>
                <th class="col-header" colspan="2">Inasistencias</th>
                <th class="col-header" colspan="2">Tardanzas</th>
            </tr>
            <tr>
                <th class="col-header">Justificadas</th><th class="col-header">Injustificadas</th>
                <th class="col-header">Justificadas</th><th class="col-header">Injustificadas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($asistenciaPorBimestre as $a)
            <tr>
                <td style="text-align:center;font-weight:700">{{ $a['periodo'] }}</td>
                <td style="text-align:center">{{ $a['inasistencias_justificadas'] ?: '-' }}</td>
                <td style="text-align:center">{{ $a['inasistencias_injustificadas'] ?: '-' }}</td>
                <td style="text-align:center">{{ $a['tardanzas_justificadas'] ?: '-' }}</td>
                <td style="text-align:center">{{ $a['tardanzas_injustificadas'] ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top:1rem">
        <tr><td class="lb-label" style="width:20%">Situación al finalizar el período</td><td>{{ $nlGeneral ? nivel_logro_texto($nlGeneral) : 'En proceso de evaluación' }}</td></tr>
    </table>

    <table style="margin-top:1.5rem">
        <tr>
            <td style="text-align:center;padding-top:2.5rem;border:none;border-top:1px solid #333;width:50%">Firma del Docente o Tutor(a)</td>
            <td style="text-align:center;padding-top:2.5rem;border:none;border-top:1px solid #333;width:50%">Firma y sello del Director(a)</td>
        </tr>
    </table>

    <p style="margin-top:1rem;font-size:.7rem;color:var(--n400);text-align:right">
        Fecha de emisión: {{ now()->translatedFormat('l, d \d\e F \d\e Y') }} — Generado por Sistema EduSeguimiento
    </p>
</div>
@endsection
