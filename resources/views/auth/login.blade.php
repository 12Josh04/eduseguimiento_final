<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — EduSeguimiento</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { 
            --primary-blue: #003366; 
        }
        
        body { 
    font-family: 'Poppins', sans-serif; 
    margin: 0; 
    min-height: 100vh;
    display: flex; 
    align-items: center; 
    justify-content: center;
    
    /* Fondo con efecto de capa azul oscura */
    background: linear-gradient(rgba(0, 34, 68, 0.85), rgba(0, 34, 68, 0.85)), 
                url('{{ asset('images/logo.png') }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
}
        
        .login-card { 
            width: 900px; height: 500px;
            display: flex; background: white; 
            border-radius: 30px; overflow: hidden; 
            box-shadow: 0 15px 40px rgba(0,0,0,0.4); 
        }
        
        /* Lado Izquierdo: Formulario */
        .login-left { 
            width: 45%; padding: 50px; display: flex; flex-direction: column; justify-content: center;
        }
        
        /* Lado Derecho: Diseño Degradado */
        .login-right { 
            width: 55%; 
            background: linear-gradient(135deg, #ffe082 0%, #74b9ff 50%, #003366 100%);
            padding: 50px; color: white;
            display: flex; flex-direction: column; justify-content: center;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-primary { width: 100%; padding: 12px; background: var(--primary-blue); border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; }
        
        /* Estilos de cuentas de prueba */
        .test-account-box { background: rgba(255,255,255,0.15); padding: 15px; border-radius: 15px; margin-top: 20px; }
        .test-account { cursor: pointer; padding: 8px; border-radius: 6px; display: flex; align-items: center; gap: 10px; color: white; margin-bottom: 5px; }
        .test-account:hover { background: rgba(255,255,255,0.25); }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-left">
        <h2>{{ nombre_institucion() }}</h2>
        <p>Plataforma académica institucional</p>
        
        <form method="POST" action="/login">
            @csrf
            <div class="form-group">
                <label>User Name</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="correo@colegio.edu.pe" required>
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <div style="position:relative">
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" id="togglePwd" style="position:absolute; right:10px; top:12px; background:none; border:none; cursor:pointer;">
                        <i class="fas fa-eye" style="color: #666;"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary">Ingresar al Sistema</button>
        </form>
        <p style="text-align:center;margin-top:1.25rem;font-size:.85rem">
            ¿Deseas matricular a tu hijo/a?
            <a href="{{ route('matricula.formulario') }}" style="color:var(--primary-blue);font-weight:600">Solicita tu matrícula aquí</a>
        </p>
    </div>

    <div class="login-right">
        <h1>Bienvenido.</h1>
        <p>¿Eres nuevo? Prueba los accesos:</p>
        
        <div class="test-account-box">
            @php
                $cuentas = [
                    ['rol' => 'Promotor', 'email' => 'promotor@eduseguimiento.com', 'icon' => 'fa-user-tie', 'color' => '#81ecec'],
                    ['rol' => 'Docente', 'email' => 'docente@eduseguimiento.com', 'icon' => 'fa-briefcase', 'color' => '#fab1a0'],
                    ['rol' => 'Psicóloga', 'email' => 'psicologa@eduseguimiento.com', 'icon' => 'fa-brain', 'color' => '#a29bfe'],
                    ['rol' => 'Estudiante', 'email' => 'estudiante@eduseguimiento.com', 'icon' => 'fa-user-graduate', 'color' => '#74b9ff']
                ];
            @endphp

            @foreach($cuentas as $cuenta)
            <div onclick="setCredentials('{{ $cuenta['email'] }}')" class="test-account">
                <i class="fas {{ $cuenta['icon'] }}" style="color:{{ $cuenta['color'] }};"></i> 
                <span><b>{{ $cuenta['rol'] }}:</b> {{ $cuenta['email'] }}</span>
            </div>
            @endforeach
            <p style="font-size: 0.75rem; margin-top: 10px; opacity: 0.8;">Password para todos: <b>password123</b></p>
        </div>
    </div>
</div>

<script>
    function setCredentials(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password123';
    }
    
    document.getElementById('togglePwd').addEventListener('click', function () {
        const pwd = document.getElementById('password');
        const i = this.querySelector('i');
        if (pwd.type === 'password') { 
            pwd.type = 'text'; 
            i.className = 'fas fa-eye-slash'; 
        } else { 
            pwd.type = 'password'; 
            i.className = 'fas fa-eye'; 
        }
    });
</script>
</body>
</html>