@extends('layouts.app')
@section('title','Institución')
@section('content')
<div class="page-header">
    <div>
        <div class="breadcrumb"><a href="/dashboard"><i class="fas fa-home"></i></a><span style="margin:0 .3rem">/</span><span>Institución</span></div>
        <h1 class="page-title"><i class="fas fa-school"></i> Configuración Institucional</h1>
        <p class="page-subtitle">Datos generales del colegio, usados en la libreta de notas y en la matrícula</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('institucion.update') }}" method="POST">
            @csrf
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Nombre del colegio *</label>
                    <input type="text" name="nombre_colegio" class="form-control" value="{{ $configuraciones['nombre_colegio'] ?? '' }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Año escolar *</label>
                    <input type="number" name="anio_escolar" class="form-control" value="{{ $configuraciones['anio_escolar'] ?? now()->year }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">DRE</label>
                    <input type="text" name="dre" class="form-control" value="{{ $configuraciones['dre'] ?? '' }}" placeholder="Ej: DRE ANCASH">
                </div>
                <div class="form-group">
                    <label class="form-label">UGEL</label>
                    <input type="text" name="ugel" class="form-control" value="{{ $configuraciones['ugel'] ?? '' }}" placeholder="Ej: UGEL Huaraz">
                </div>
                <div class="form-group">
                    <label class="form-label">Código Modular</label>
                    <input type="text" name="codigo_modular" class="form-control" value="{{ $configuraciones['codigo_modular'] ?? '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Nivel educativo</label>
                    <input type="text" name="nivel_educativo" class="form-control" value="{{ $configuraciones['nivel_educativo'] ?? '' }}" placeholder="Ej: Primaria y Secundaria">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Número de WhatsApp para matrícula (formato: 51987654321, sin "+")</label>
                    <input type="text" name="whatsapp_matricula" class="form-control" value="{{ $configuraciones['whatsapp_matricula'] ?? '' }}" placeholder="51987654321">
                    <p style="font-size:.78rem;color:var(--n500);margin-top:.3rem">
                        Este número recibe los mensajes del botón "Abrir WhatsApp" del formulario público de matrícula.
                    </p>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar configuración</button>
        </form>
    </div>
</div>
@endsection
