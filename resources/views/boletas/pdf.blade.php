<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Libreta de Notas — {{ $estudiante->nombre }} {{ $estudiante->apellido }}</title>
<style>
    * { box-sizing: border-box; font-family: 'DejaVu Sans', sans-serif; }
    body { font-size: 10px; color: #111; margin: 18px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #6b7280; padding: 4px 6px; vertical-align: middle; }
    .titulo-anio { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 10px; color: #1a3a6b; }
    .lb-datos td { border: 1px solid #6b7280; padding: 5px 8px; }
    .lb-label { background: #e8eef8; font-weight: bold; width: 20%; color: #1a3a6b; }
    .lb-logo { width: 90px; text-align: center; vertical-align: middle; }
    .crest { width: 60px; height: 60px; border-radius: 50%; background: #1a3a6b; color: #fff; text-align: center;
             font-weight: bold; font-size: 16px; line-height: 60px; margin: 0 auto; }
    .area-header { background: #1a3a6b; color: #fff; font-weight: bold; text-align: center; }
    .bim-header { background: #2554a0; color: #fff; text-align: center; font-weight: bold; font-size: 9px; }
    .col-header { background: #f0f2f6; text-align: center; font-weight: bold; font-size: 9px; }
    .nl-cell { text-align: center; font-weight: bold; width: 26px; }
    .nl-AD { color: #2e7d32; } .nl-A { color: #1565c0; } .nl-B { color: #e08a00; } .nl-C { color: #c62828; }
    .area-nombre { background: #f8f9fb; font-weight: bold; }
    .conclusion-txt { font-size: 8px; color: #5a6478; }
    .leyenda-letra { font-weight: bold; text-align: center; width: 30px; font-size: 11px; }
    .puesto { display: inline-block; background: #e6f7f3; color: #0ea27a; border-radius: 6px; padding: 4px 10px;
              font-weight: bold; font-size: 10px; margin-bottom: 8px; }
    .firma { text-align: center; padding-top: 30px; border: none; border-top: 1px solid #333; width: 50%; }
    .footer-note { margin-top: 10px; font-size: 8px; color: #9aa3b2; text-align: right; }
</style>
</head>
<body>
    <div class="titulo-anio">LIBRETA DE NOTAS — {{ $anioEscolar }}</div>

    <table class="lb-datos">
        <tr>
            <td class="lb-label">DRE</td><td>{{ $dre }}</td>
            <td class="lb-label">UGEL</td><td>{{ $ugel }}</td>
            <td class="lb-logo" rowspan="5"><div class="crest">{{ mb_strtoupper(substr($colegio,0,2)) }}</div></td>
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

    <br>
    @if($ordenMerito)
        <div class="puesto">Orden de mérito: {{ $ordenMerito['puesto'] }}.º de {{ $ordenMerito['total'] }} en {{ $estudiante->grado }} "{{ $estudiante->seccion }}"</div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="col-header" rowspan="2" style="width:15%">Área curricular</th>
                <th class="col-header" rowspan="2" style="width:15%">Competencias / Curso</th>
                @foreach($periodos as $periodo)
                    <th class="bim-header" colspan="2">{{ mb_strtoupper($periodo->nombre) }}</th>
                @endforeach
                <th class="col-header" rowspan="2" style="width:7%">NL final</th>
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
                <tr><td colspan="{{ 3 + count($periodos)*2 }}" style="text-align:center;padding:12px">Aún no hay calificaciones registradas.</td></tr>
            @endforelse

            @if(count($areas))
            <tr>
                <td colspan="{{ 2 + count($periodos)*2 }}" style="text-align:right;font-weight:bold">Promedio general y nivel de logro alcanzado</td>
                <td class="nl-cell {{ $nlGeneral ? 'nl-'.$nlGeneral : '' }}">{{ $nlGeneral ?? '' }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <br>
    <table>
        <tr><td class="leyenda-letra nl-AD">AD</td><td><strong>LOGRO DESTACADO.</strong> El estudiante evidencia un nivel superior a lo esperado, con aprendizajes que van más allá de la competencia.</td></tr>
        <tr><td class="leyenda-letra nl-A">A</td><td><strong>LOGRO ESPERADO.</strong> El estudiante evidencia el nivel esperado, con manejo satisfactorio en las tareas propuestas.</td></tr>
        <tr><td class="leyenda-letra nl-B">B</td><td><strong>EN PROCESO.</strong> El estudiante está próximo al nivel esperado y requiere acompañamiento para lograrlo.</td></tr>
        <tr><td class="leyenda-letra nl-C">C</td><td><strong>EN INICIO.</strong> El estudiante muestra progreso mínimo y requiere mayor tiempo de acompañamiento.</td></tr>
    </table>

    <br>
    <table>
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
                <td style="text-align:center;font-weight:bold">{{ $a['periodo'] }}</td>
                <td style="text-align:center">{{ $a['inasistencias_justificadas'] ?: '-' }}</td>
                <td style="text-align:center">{{ $a['inasistencias_injustificadas'] ?: '-' }}</td>
                <td style="text-align:center">{{ $a['tardanzas_justificadas'] ?: '-' }}</td>
                <td style="text-align:center">{{ $a['tardanzas_injustificadas'] ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <br>
    <table>
        <tr><td class="lb-label" style="width:20%">Situación al finalizar el período</td><td>{{ $nlGeneral ? nivel_logro_texto($nlGeneral) : 'En proceso de evaluación' }}</td></tr>
    </table>

    <br><br>
    <table style="border:none">
        <tr>
            <td class="firma">Firma del Docente o Tutor(a)</td>
            <td class="firma">Firma y sello del Director(a)</td>
        </tr>
    </table>

    <div class="footer-note">
        Fecha de emisión: {{ now()->translatedFormat('l, d \d\e F \d\e Y') }} — Generado por Sistema EduSeguimiento
    </div>
</body>
</html>
