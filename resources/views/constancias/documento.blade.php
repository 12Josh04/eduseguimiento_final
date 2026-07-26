<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>{{ $titulo }} — {{ $estudiante->nombre }} {{ $estudiante->apellido }}</title>
<style>
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { margin: 50px 60px; color: #1e2532; }
    .border-box { border: 3px double #1a3a6b; padding: 40px 50px; min-height: 620px; }
    .header { text-align: center; margin-bottom: 10px; }
    .header .dre-ugel { font-size: 10px; color: #5a6478; }
    .header .colegio { font-size: 16px; font-weight: bold; color: #1a3a6b; margin: 6px 0 2px; }
    .header .codigo-modular { font-size: 9px; color: #9aa3b2; }
    .titulo { text-align: center; font-size: 22px; font-weight: bold; color: #1a3a6b; margin: 40px 0 30px;
              text-transform: uppercase; letter-spacing: 2px; }
    .cuerpo { font-size: 13px; line-height: 2; text-align: justify; margin: 0 10px; }
    .cuerpo strong { color: #1a3a6b; }
    .fecha { text-align: center; margin-top: 60px; font-size: 12px; }
    .firma { margin-top: 70px; text-align: center; }
    .firma .linea { border-top: 1px solid #1e2532; width: 260px; margin: 0 auto 6px; }
    .footer-note { margin-top: 40px; text-align: center; font-size: 9px; color: #9aa3b2; }
</style>
</head>
<body>
    <div class="border-box">
        <div class="header">
            <div class="dre-ugel">{{ $dre }} — {{ $ugel }}</div>
            <div class="colegio">{{ mb_strtoupper($colegio) }}</div>
            @if($codigoModular)<div class="codigo-modular">Código Modular: {{ $codigoModular }}</div>@endif
        </div>

        <div class="titulo">{{ $titulo }}</div>

        <p class="cuerpo">{!! $cuerpo !!}</p>

        <p class="cuerpo" style="margin-top:20px">
            Se expide la presente constancia a solicitud de la parte interesada, para los fines que estime conveniente.
        </p>

        <div class="fecha">{{ $dre }} y {{ $ugel }} — Emitido el {{ $fechaEmision }}</div>

        <div class="firma">
            <div class="linea"></div>
            {{ $director }}
        </div>
    </div>
    <div class="footer-note">Documento generado por el Sistema EduSeguimiento — {{ $colegio }}</div>
</body>
</html>
