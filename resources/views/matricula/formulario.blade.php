<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Matrícula — {{ nombre_institucion() }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { --primary-blue: #003366; --accent: #0ea27a; }
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; margin: 0; background: #f0f2f6; color: #1e2532; }
        .topbar { background: var(--primary-blue); color: #fff; padding: 1.25rem 2rem; text-align: center; }
        .topbar h1 { margin: 0; font-size: 1.3rem; }
        .topbar p { margin: .25rem 0 0; opacity: .85; font-size: .9rem; }
        .wrap { max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,.08); padding: 2rem; }
        h2.section { font-size: 1rem; color: var(--primary-blue); border-bottom: 2px solid #e2e6ee; padding-bottom: .5rem; margin: 1.75rem 0 1rem; }
        h2.section:first-of-type { margin-top: 0; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .35rem; color: #444; }
        .form-control { width: 100%; padding: .6rem .8rem; border: 1.5px solid #e2e6ee; border-radius: 8px; font-size: .9rem; font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--primary-blue); }
        .btn { display: inline-flex; align-items: center; gap: .5rem; padding: .7rem 1.4rem; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; font-size: .9rem; }
        .btn-primary { background: var(--primary-blue); color: #fff; width: 100%; justify-content: center; }
        .btn-whatsapp { background: #25D366; color: #fff; }
        .error { color: #c0392b; font-size: .78rem; margin-top: .25rem; }
        .alert { padding: .9rem 1.1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: .88rem; }
        .alert-info { background: #e8eef8; color: var(--primary-blue); }
        .voucher-box { border: 2px dashed #c8d0dc; border-radius: 10px; padding: 1.25rem; text-align: center; background: #f8f9fb; }
        .whatsapp-box { display: flex; align-items: center; justify-content: space-between; gap: 1rem; background: #e8f9f1; border-radius: 10px; padding: 1rem 1.25rem; margin-top: 1.25rem; flex-wrap: wrap; }
    </style>
</head>
<body>

<div class="topbar">
    <h1><i class="fas fa-school"></i> {{ nombre_institucion() }}</h1>
    <p>Solicitud de Matrícula — Año Escolar {{ anio_escolar_actual() }}</p>
</div>

<div class="wrap">
    <div class="card">
        @if($errors->any())
            <div class="alert" style="background:#fdecea;color:#c0392b">
                <strong>Revisa los siguientes datos:</strong>
                <ul style="margin:.4rem 0 0 1.1rem">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Completa los datos del postulante y adjunta el <strong>voucher (comprobante) de pago de matrícula</strong>.
            Nuestro equipo confirmará el pago y activará la matrícula en un plazo breve.
        </div>

        <form method="POST" action="{{ route('matricula.enviar') }}" enctype="multipart/form-data">
            @csrf

            <h2 class="section"><i class="fas fa-user-graduate"></i> Datos del postulante</h2>
            <div class="grid-2">
                <div class="form-group">
                    <label>Nombres *</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required maxlength="60">
                </div>
                <div class="form-group">
                    <label>Apellidos *</label>
                    <input type="text" name="apellido" class="form-control" value="{{ old('apellido') }}" required maxlength="60">
                </div>
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-control" value="{{ old('fecha_nacimiento') }}">
                </div>
                <div class="form-group">
                    <label>DNI</label>
                    <input type="text" name="dni" class="form-control" value="{{ old('dni') }}" maxlength="12">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Grado al que postula *</label>
                    <select name="grado_id" class="form-control" required>
                        <option value="">— Selecciona un grado —</option>
                        @include('partials.grado-options', ['grados' => $grados, 'seleccionado' => old('grado_id')])
                    </select>
                </div>
            </div>

            <h2 class="section"><i class="fas fa-user-shield"></i> Datos del apoderado</h2>
            <div class="grid-2">
                <div class="form-group">
                    <label>Nombre completo *</label>
                    <input type="text" name="apoderado_nombre" class="form-control" value="{{ old('apoderado_nombre') }}" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Teléfono / WhatsApp *</label>
                    <input type="text" name="apoderado_telefono" class="form-control" value="{{ old('apoderado_telefono') }}" required maxlength="20" placeholder="9XXXXXXXX">
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Correo electrónico (para la cuenta del estudiante en el sistema)</label>
                    <input type="email" name="apoderado_email" class="form-control" value="{{ old('apoderado_email') }}" placeholder="correo@ejemplo.com">
                </div>
            </div>

            <h2 class="section"><i class="fas fa-file-invoice-dollar"></i> Comprobante de pago</h2>
            <div class="form-group">
                <label>Monto pagado (S/)</label>
                <input type="number" step="0.01" min="0" name="monto" class="form-control" value="{{ old('monto') }}" style="max-width:200px">
            </div>
            <div class="voucher-box">
                <i class="fas fa-receipt" style="font-size:1.6rem;color:#0ea27a"></i>
                <p style="margin:.5rem 0 .75rem;font-size:.85rem;color:#555">Sube una foto o PDF del voucher / constancia de depósito o transferencia.</p>
                <input type="file" name="voucher" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>

            <div class="whatsapp-box">
                <div>
                    <strong><i class="fab fa-whatsapp" style="color:#25D366"></i> ¿Prefieres enviarlo por WhatsApp?</strong>
                    <p style="margin:.25rem 0 0;font-size:.82rem;color:#555">También puedes enviarnos el voucher directamente por chat.</p>
                </div>
                <a href="{{ $whatsappLink }}" target="_blank" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Abrir WhatsApp</a>
            </div>

            <div style="margin-top:1.75rem">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar solicitud de matrícula</button>
            </div>
        </form>
    </div>

    <p style="text-align:center;margin-top:1.25rem;font-size:.82rem;color:#8a93a3">
        ¿Ya tienes una cuenta? <a href="{{ route('login') }}" style="color:var(--primary-blue)">Inicia sesión aquí</a>
    </p>
</div>

</body>
</html>
