<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud enviada — {{ nombre_institucion() }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family:'Poppins',sans-serif; margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f0f2f6; }
        .card { background:#fff; border-radius:16px; box-shadow:0 8px 30px rgba(0,0,0,.08); padding:2.5rem; max-width:480px; text-align:center; }
        .icon { width:70px; height:70px; border-radius:50%; background:#e6f7f3; color:#0ea27a; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 1rem; }
        .btn { display:inline-flex; align-items:center; gap:.5rem; padding:.7rem 1.4rem; border-radius:8px; font-weight:600; border:none; cursor:pointer; text-decoration:none; margin-top:1rem; }
        .btn-whatsapp { background:#25D366; color:#fff; }
        .btn-outline { background:#fff; color:#003366; border:1.5px solid #003366; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fas fa-check"></i></div>
        <h2>¡Solicitud enviada!</h2>
        <p style="color:#555">
            Recibimos tu solicitud de matrícula y el comprobante de pago.
            Nuestro equipo confirmará el pago y activará la matrícula en un plazo breve;
            te contactaremos al teléfono que registraste.
        </p>
        <a href="{{ $whatsappLink }}" target="_blank" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Escribir por WhatsApp</a>
        <br>
        <a href="{{ route('matricula.formulario') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</body>
</html>
