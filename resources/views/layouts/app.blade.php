<!DOCTYPE html>
<html lang="es">
<head>
    <script>
        // Aplica el tema guardado ANTES de pintar la página, para evitar el parpadeo de tema incorrecto.
        (function () {
            var tema = localStorage.getItem('eduseguimiento_tema');
            if (tema === 'dark' || tema === 'light') {
                document.documentElement.setAttribute('data-theme', tema);
            }
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','EduSeguimiento') — {{ nombre_institucion() }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>

{{-- ── NAVBAR ─────────────────────────────────────────────────────────── --}}
<nav class="navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
            <div>
                <span class="brand-name">{{ nombre_institucion() }}</span>
                <span class="brand-sub">Sistema académico</span>
            </div>
        </div>
    </div>
    <div class="navbar-right">
        <button class="btn-icon-nav" id="themeToggle" title="Cambiar tema claro/oscuro">
            <i class="fas fa-moon" id="themeToggleIcon"></i>
        </button>
        @php $notificaciones = notificaciones_navbar(); @endphp
        <div class="notif-menu" id="notifMenuToggle">
            <button class="btn-icon-nav" title="Notificaciones">
                <i class="fas fa-bell"></i>
                @if(count($notificaciones) > 0)
                    <span class="badge-notif">{{ count($notificaciones) }}</span>
                @endif
            </button>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header">Notificaciones</div>
                @forelse($notificaciones as $n)
                    <a href="{{ $n['url'] }}" class="notif-item">
                        <span class="notif-item-icon notif-{{ $n['color'] }}"><i class="fas {{ $n['icono'] }}"></i></span>
                        <span class="notif-item-text">
                            {{ $n['texto'] }}
                            @if(!empty($n['tiempo']))
                                <span class="notif-item-time">{{ \Carbon\Carbon::parse($n['tiempo'])->diffForHumans() }}</span>
                            @endif
                        </span>
                    </a>
                @empty
                    <div class="notif-empty"><i class="fas fa-circle-check"></i> No hay nada pendiente por ahora.</div>
                @endforelse
            </div>
        </div>
        <div class="user-menu" id="userMenuToggle">
            <div class="user-avatar">
                {{ strtoupper(substr(Auth::user()->nombre ?? 'U', 0, 1)) }}
            </div>
            <div>
                <span class="user-name">{{ Auth::user()->nombre ?? 'Usuario' }}</span>
                <span class="role-badge role-{{ Auth::user()->rol ?? '' }}">
                    {{ ucfirst(Auth::user()->rol ?? '') }}
                </span>
            </div>
            <i class="fas fa-chevron-down" style="color:rgba(255,255,255,.6);font-size:.7rem"></i>
            <div class="user-dropdown" id="userDropdown">
                <a href="/mi-cuenta"><i class="fas fa-user"></i> Mi Perfil</a>
                <div class="dd-divider"></div>
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<div class="app-wrapper">

{{-- ── SIDEBAR ─────────────────────────────────────────────────────────── --}}
<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        @php $rol = Auth::user()->rol ?? ''; @endphp

        {{-- Principal --}}
        <div class="nav-section">
            <span class="nav-section-label">Principal</span>
            <a href="/dashboard" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i><span>Dashboard</span>
            </a>
            <a href="/mi-cuenta" class="nav-link {{ request()->is('mi-cuenta*') ? 'active' : '' }}">
                <i class="fas fa-user-cog"></i><span>Mi cuenta</span>
            </a>
        </div>

        {{-- ── PROMOTOR ── --}}
        @if($rol === 'promotor')
        <div class="nav-section">
            <span class="nav-section-label">Académico</span>
            <a href="/estudiantes" class="nav-link {{ request()->is('estudiantes*') ? 'active' : '' }}">
                <i class="fas fa-user-graduate"></i><span>Estudiantes</span>
            </a>
            <a href="/matriculas-pendientes" class="nav-link {{ request()->is('matriculas-pendientes*') ? 'active' : '' }}">
                <i class="fas fa-user-plus"></i><span>Matrícula</span>
                @php $pendientesMatricula = DB::table('solicitudes_matricula')->where('estado','pendiente')->count(); @endphp
                @if($pendientesMatricula > 0)
                    <span class="badge-notif" style="position:static;margin-left:.4rem">{{ $pendientesMatricula }}</span>
                @endif
            </a>
            <a href="/notas" class="nav-link {{ request()->is('notas') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i><span>Notas</span>
            </a>
            <a href="/notas/promedios" class="nav-link {{ request()->is('notas/promedios') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i><span>Promedios</span>
            </a>
            <a href="/reportes/orden-merito" class="nav-link {{ request()->is('reportes/orden-merito') ? 'active' : '' }}">
                <i class="fas fa-medal"></i><span>Orden de Mérito</span>
            </a>
            <a href="/reportes/comparativo-secciones" class="nav-link {{ request()->is('reportes/comparativo-secciones') ? 'active' : '' }}">
                <i class="fas fa-balance-scale"></i><span>Comparativo Secciones</span>
            </a>
            <a href="/secciones" class="nav-link {{ request()->is('secciones*') ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i><span>Secciones</span>
            </a>
            <a href="/cursos" class="nav-link {{ request()->is('cursos*') ? 'active' : '' }}">
                <i class="fas fa-book"></i><span>Cursos</span>
            </a>
            <a href="/asignaciones" class="nav-link {{ request()->is('asignaciones*') ? 'active' : '' }}">
                <i class="fas fa-user-check"></i><span>Asignaciones</span>
            </a>
            <a href="/docentes" class="nav-link {{ request()->is('docentes*') ? 'active' : '' }}">
                <i class="fas fa-chalkboard-teacher"></i><span>Docentes</span>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-label">Conductual</span>
            <a href="/conductas" class="nav-link {{ request()->is('conductas') ? 'active' : '' }}">
                <i class="fas fa-heart"></i><span>Conductas</span>
            </a>
            <a href="/conductas/historial" class="nav-link {{ request()->is('conductas/historial') ? 'active' : '' }}">
                <i class="fas fa-history"></i><span>Historial</span>
            </a>
            <a href="/sesiones-psicologia" class="nav-link {{ request()->is('sesiones-psicologia*') ? 'active' : '' }}">
                <i class="fas fa-brain"></i><span>Sesiones de Psicología</span>
            </a>
            <a href="/control-asistencia" class="nav-link {{ request()->is('control-asistencia') ? 'active' : '' }}">
                <i class="fas fa-clipboard-check"></i><span>Control de Asistencia</span>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-label">Administración</span>
            <a href="/usuarios" class="nav-link {{ request()->is('usuarios*') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i><span>Usuarios</span>
            </a>
            <a href="/auditorias" class="nav-link {{ request()->is('auditorias*') ? 'active' : '' }}">
                <i class="fas fa-history"></i><span>Auditoría</span>
            </a>
            <a href="/recuperaciones" class="nav-link {{ request()->is('recuperaciones*') ? 'active' : '' }}">
                <i class="fas fa-key"></i><span>Recuperación</span>
            </a>
            <a href="/institucion" class="nav-link {{ request()->is('institucion*') ? 'active' : '' }}">
                <i class="fas fa-school"></i><span>Institución</span>
            </a>
        </div>
        @endif

        {{-- ── PSICÓLOGA ── --}}
        @if($rol === 'psicologa')
        <div class="nav-section">
            <span class="nav-section-label">Académico</span>
            <a href="/estudiantes" class="nav-link {{ request()->is('estudiantes*') ? 'active' : '' }}">
                <i class="fas fa-user-graduate"></i><span>Estudiantes</span>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-label">Conductual</span>
            <a href="/conductas" class="nav-link {{ request()->is('conductas') ? 'active' : '' }}">
                <i class="fas fa-heart"></i><span>Conductas</span>
            </a>
            <a href="/conductas/historial" class="nav-link {{ request()->is('conductas/historial') ? 'active' : '' }}">
                <i class="fas fa-history"></i><span>Historial</span>
            </a>
            <a href="/sesiones-psicologia" class="nav-link {{ request()->is('sesiones-psicologia*') ? 'active' : '' }}">
                <i class="fas fa-brain"></i><span>Sesiones de Psicología</span>
            </a>
        </div>
        @endif

        {{-- ── DOCENTE ── --}}
        @if($rol === 'docente')
        <div class="nav-section">
            <span class="nav-section-label">Mis Cursos</span>
            <a href="/estudiantes" class="nav-link {{ request()->is('estudiantes*') ? 'active' : '' }}">
                <i class="fas fa-user-graduate"></i><span>Mis Alumnos</span>
            </a>
            <a href="/notas" class="nav-link {{ request()->is('notas') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i><span>Mis Notas</span>
            </a>
            <a href="/conductas" class="nav-link {{ request()->is('conductas') ? 'active' : '' }}">
                <i class="fas fa-heart"></i><span>Conductas</span>
            </a>
            <a href="/asistencias" class="nav-link {{ request()->is('asistencias') ? 'active' : '' }}">
                <i class="fas fa-calendar-check"></i><span>Asistencias</span>
            </a>
            <a href="/reportes/asistencia" class="nav-link {{ request()->is('reportes/asistencia') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i><span>Reportes</span>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-label">Mi Agenda</span>
            <a href="/horario/docente" class="nav-link {{ request()->is('horario/docente') ? 'active' : '' }}">
                <i class="fas fa-calendar-week"></i><span>Mi Horario</span>
            </a>
        </div>
        @endif

        {{-- ── ALUMNO ── --}}
        @if($rol === 'estudiante')
        <div class="nav-section">
            <span class="nav-section-label">Mi Información</span>
            <a href="/alumno/notas" class="nav-link {{ request()->is('alumno/notas') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i><span>Mis Notas</span>
            </a>
            <a href="/alumno/actividades" class="nav-link {{ request()->is('alumno/actividades*') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i><span>Mis Actividades</span>
                @php $actividadesPendientes = DB::table('entregas_actividad')->where('estudiante_id', session('estudiante_id'))->where('estado','pendiente')->count(); @endphp
                @if($actividadesPendientes > 0)
                    <span class="badge-notif" style="position:static;margin-left:.4rem">{{ $actividadesPendientes }}</span>
                @endif
            </a>
            <a href="/alumno/promedios" class="nav-link {{ request()->is('alumno/promedios') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i><span>Mis Promedios</span>
            </a>
            <a href="/alumno/conductas" class="nav-link {{ request()->is('alumno/conductas') ? 'active' : '' }}">
                <i class="fas fa-heart"></i><span>Mi Conducta</span>
            </a>
            <a href="/alumno/libreta" class="nav-link {{ request()->is('alumno/libreta') || request()->is('boletas*') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i><span>Mi Libreta de Notas</span>
            </a>
            <a href="/asistencias/historial" class="nav-link {{ request()->is('asistencias/historial') ? 'active' : '' }}">
                <i class="fas fa-calendar-check"></i><span>Mi Asistencia</span>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-label">Mis Constancias</span>
            <a href="{{ route('alumno.constancia.estudios') }}" target="_blank" class="nav-link">
                <i class="fas fa-file-alt"></i><span>De Estudios</span>
            </a>
            <a href="{{ route('alumno.constancia.matricula') }}" target="_blank" class="nav-link">
                <i class="fas fa-file-signature"></i><span>De Matrícula</span>
            </a>
            <a href="{{ route('alumno.constancia.conducta') }}" target="_blank" class="nav-link">
                <i class="fas fa-file-medical"></i><span>De Conducta</span>
            </a>
        </div>
        <div class="nav-section">
            <span class="nav-section-label">Mi Agenda</span>
            <a href="/alumno/horario" class="nav-link {{ request()->is('alumno/horario') ? 'active' : '' }}">
                <i class="fas fa-calendar-week"></i><span>Mi Horario</span>
            </a>
        </div>
        @endif

    </nav>
    <div class="sidebar-footer">
        <div class="school-year">
            <i class="fas fa-calendar-alt"></i><span>Año Escolar {{ anio_escolar_actual() }}</span>
        </div>
    </div>
</aside>

{{-- ── CONTENIDO PRINCIPAL ─────────────────────────────────────────────── --}}
<main class="main-content" id="mainContent">
    @if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button class="alert-close"><i class="fas fa-times"></i></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button class="alert-close"><i class="fas fa-times"></i></button>
    </div>
    @endif

    @yield('content')
</main>

</div>{{-- .app-wrapper --}}

<script src="{{ asset('js/app.js') }}"></script>
<script>
    // ── Modo oscuro: alternar y persistir en localStorage ─────────────────
    (function () {
        var STORAGE_KEY = 'eduseguimiento_tema';
        var btn = document.getElementById('themeToggle');
        var icon = document.getElementById('themeToggleIcon');
        if (!btn) return;

        function temaActual() {
            var guardado = localStorage.getItem(STORAGE_KEY);
            if (guardado === 'dark' || guardado === 'light') return guardado;
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        function actualizarIcono() {
            var actual = temaActual();
            icon.classList.toggle('fa-moon', actual !== 'dark');
            icon.classList.toggle('fa-sun', actual === 'dark');
        }

        btn.addEventListener('click', function () {
            var nuevo = temaActual() === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nuevo);
            localStorage.setItem(STORAGE_KEY, nuevo);
            actualizarIcono();
        });

        actualizarIcono();
    })();
</script>
@stack('scripts')
</body>
</html>
