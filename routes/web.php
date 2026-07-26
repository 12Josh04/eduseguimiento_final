<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConductaController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\BoletaController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\RecuperacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\InstitucionController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\ConstanciaController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\SesionPsicologiaController;
use App\Http\Controllers\ActividadController;

// ── Raíz ──────────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect('/login'));

// ── Autenticación ─────────────────────────────────────────────────────────
Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Matrícula — formulario público (sin sesión) ─────────────────────────────
Route::get('/matricula', [MatriculaController::class, 'formulario'])->name('matricula.formulario');
Route::post('/matricula', [MatriculaController::class, 'enviar'])->name('matricula.enviar');
Route::get('/matricula/gracias', [MatriculaController::class, 'gracias'])->name('matricula.gracias');

// ── Rutas protegidas ───────────────────────────────────────────────────────
Route::middleware(['auth.session'])->group(function () {

        // ── Panel principal ─────────────────────────────────────────────
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ── Estudiantes — Promotor, Docente, Psicóloga ──────────────────
        Route::middleware('auth.session:promotor,docente,psicologa')->group(function () {
            Route::resource('estudiantes', EstudianteController::class);
        });

        // ── Notas — consulta (Promotor, Docente, Psicóloga) ─────────────
        Route::middleware('auth.session:promotor,docente,psicologa')->group(function () {
            Route::get('/notas', [NotaController::class, 'index'])->name('notas.index');
            Route::get('/notas/promedios', [NotaController::class, 'promedios'])->name('notas.promedios');
            Route::get('/notas/cursos/{curso}', [NotaController::class, 'curso'])->name('notas.curso');
            Route::get('/notas/estudiantes/{estudiante}/cursos', [NotaController::class, 'estudianteCursos'])->name('notas.estudiante.cursos');
            Route::get('/notas/actividades/{id}', [ActividadController::class, 'show'])->name('actividades.show');
        });

        // ── Notas — registro/eliminación (Promotor, Docente) ────────────
        Route::middleware('auth.session:promotor,docente')->group(function () {
            Route::post('/notas', [NotaController::class, 'store'])->name('notas.store');
            Route::delete('/notas/{id}', [NotaController::class, 'destroy'])->name('notas.destroy');
            Route::post('/notas/cursos/{curso}/clases', [NotaController::class, 'storeClase'])->name('notas.clases.store');
            Route::delete('/notas/clases/{id}', [NotaController::class, 'destroyClase'])->name('notas.clases.destroy');
            Route::post('/notas/cursos/{curso}/actividades', [ActividadController::class, 'store'])->name('actividades.store');
            Route::post('/notas/actividades/{id}/calificar/{estudiante}', [ActividadController::class, 'calificar'])->name('actividades.calificar');
            Route::delete('/notas/actividades/{id}', [ActividadController::class, 'destroy'])->name('actividades.destroy');
        });

        // ── Actividades — archivos (Promotor, Docente, Estudiante) ──────
        Route::middleware('auth.session:promotor,docente,estudiante')->group(function () {
            Route::get('/notas/actividades/{id}/archivo', [ActividadController::class, 'archivoIndicaciones'])->name('actividades.archivo');
            Route::get('/notas/entregas/{id}/archivo', [ActividadController::class, 'archivoEntrega'])->name('entregas.archivo');
        });

        // ── Docentes — panel agrupado (solo Promotor) ───────────────────
        Route::middleware('auth.session:promotor')->group(function () {
            Route::get('/docentes', [DocenteController::class, 'index'])->name('docentes.index');
            Route::get('/docentes/{id}', [DocenteController::class, 'show'])->name('docentes.show');
        });

        // ── Sesiones de Psicología — Promotor y Psicóloga (confidencial) ─
        Route::middleware('auth.session:promotor,psicologa')->group(function () {
            Route::get('/sesiones-psicologia', [SesionPsicologiaController::class, 'index'])->name('sesiones.index');
            Route::post('/sesiones-psicologia', [SesionPsicologiaController::class, 'store'])->name('sesiones.store');
            Route::delete('/sesiones-psicologia/{id}', [SesionPsicologiaController::class, 'destroy'])->name('sesiones.destroy');
        });

        // ── Conductas — Promotor, Docente, Psicóloga ────────────────────
        Route::middleware('auth.session:promotor,docente,psicologa')->group(function () {
            Route::get('/conductas', [ConductaController::class, 'index'])->name('conductas.index');
            Route::post('/conductas', [ConductaController::class, 'store'])->name('conductas.store');
            Route::delete('/conductas/{id}', [ConductaController::class, 'destroy'])->name('conductas.destroy');
            Route::get('/conductas/historial', [ConductaController::class, 'historial'])->name('conductas.historial');
        });

        // ── Cursos — solo Promotor ──────────────────────────────────────
        Route::middleware('auth.session:promotor')->group(function () {
            Route::get('/cursos', [CursoController::class, 'index'])->name('cursos.index');
            Route::post('/cursos', [CursoController::class, 'store'])->name('cursos.store');
            Route::put('/cursos/{id}', [CursoController::class, 'update'])->name('cursos.update');
            Route::delete('/cursos/{id}', [CursoController::class, 'destroy'])->name('cursos.destroy');
        });

        // ── Asignaciones Docentes — solo Promotor ───────────────────────
        Route::middleware('auth.session:promotor')->prefix('asignaciones')->name('asignaciones.')->group(function () {
            Route::get('/', [AsignacionController::class, 'index'])->name('index');
            Route::post('/', [AsignacionController::class, 'store'])->name('store');
            Route::get('/{id}/detalles', [AsignacionController::class, 'detalles'])->name('detalles');
            Route::get('/{id}/edit', [AsignacionController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AsignacionController::class, 'update'])->name('update');
            Route::delete('/{id}', [AsignacionController::class, 'destroy'])->name('destroy');
        });

        // ── Auditoría — solo Promotor ────────────────────────────────────
        Route::middleware('auth.session:promotor')->group(function () {
            Route::get('/auditorias', [AuditoriaController::class, 'index'])->name('auditorias.index');
        });

        // ── Institución — solo Promotor ───────────────────────────────────
        Route::middleware('auth.session:promotor')->group(function () {
            Route::get('/institucion', [InstitucionController::class, 'index'])->name('institucion.index');
            Route::post('/institucion', [InstitucionController::class, 'update'])->name('institucion.update');
        });

        // ── Recuperación de contraseña — solo Promotor ────────────────────
        Route::middleware('auth.session:promotor')->group(function () {
            Route::get('/recuperaciones', [RecuperacionController::class, 'index'])->name('recuperaciones.index');
            Route::post('/recuperaciones', [RecuperacionController::class, 'reset'])->name('recuperaciones.reset');
        });

        // ── Horario del Docente ─────────────────────────────────────────
        Route::middleware('auth.session:docente')->group(function () {
            Route::get('/horario/docente', [HorarioController::class, 'docente'])->name('horario.docente');
        });

        // ✅ Ruta POST para asignar estudiante a sección
        Route::post('/secciones/asignar', [EstudianteController::class, 'asignarSeccionEstudiante'])
            ->name('secciones.asignar');
        
        // ✅ Ruta POST para quitar estudiante de sección
        Route::post('/secciones/quitar', [EstudianteController::class, 'quitarSeccionEstudiante'])
            ->name('secciones.quitar');
        
        // ============================================
        // RUTAS CRUD PARA SECCIONES
        // ============================================
        Route::resource('secciones', SeccionController::class)->except(['show','create','edit']);

        // AJAX: estudiantes por grado (para el modal de notas)
        Route::middleware('auth.session:promotor,docente')
            ->get('/notas/estudiantes-por-grado', [NotaController::class, 'estudiantesPorGrado'])
            ->name('notas.estudiantesPorGrado');

    Route::middleware('auth.session:docente')->group(function () {
        Route::get('/asistencias', [AsistenciaController::class, 'index'])->name('asistencias.index');
        Route::post('/asistencias', [AsistenciaController::class, 'store'])->name('asistencias.store');
    });

    Route::middleware('auth.session:estudiante')->group(function () {
        Route::get('/asistencias/historial', [AsistenciaController::class, 'historial'])->name('asistencias.historial');
    });

    Route::middleware('auth.session:promotor,docente')->group(function () {
        Route::get('/reportes/asistencia', [ReporteController::class, 'asistencia'])->name('reportes.asistencia');
        Route::get('/reportes/academico', [ReporteController::class, 'academico'])->name('reportes.academico');
        Route::get('/reportes/orden-merito', [ReporteController::class, 'ordenMerito'])->name('reportes.ordenMerito');
        Route::get('/reportes/comparativo-secciones', [ReporteController::class, 'comparativoSecciones'])->name('reportes.comparativoSecciones');
    });

    Route::middleware('auth.session:promotor')->group(function () {
        Route::get('/control-asistencia', [AsistenciaController::class, 'control'])->name('asistencias.control');
    });

    Route::middleware('auth.session:promotor')->prefix('matriculas-pendientes')->name('matricula.')->group(function () {
        Route::get('/', [MatriculaController::class, 'pendientes'])->name('pendientes');
        Route::get('/{id}/voucher', [MatriculaController::class, 'voucher'])->name('voucher');
        Route::post('/{id}/confirmar', [MatriculaController::class, 'confirmar'])->name('confirmar');
        Route::post('/{id}/rechazar', [MatriculaController::class, 'rechazar'])->name('rechazar');
    });

    Route::middleware('auth.session:promotor,docente,estudiante')->group(function () {
        Route::get('/boletas/{id}', [BoletaController::class, 'show'])->name('boletas.show');
    });

    Route::middleware('auth.session:promotor,docente,estudiante')->prefix('constancias')->name('constancias.')->group(function () {
        Route::get('/{id}/estudios', [ConstanciaController::class, 'estudios'])->name('estudios');
        Route::get('/{id}/matricula', [ConstanciaController::class, 'matricula'])->name('matricula');
        Route::get('/{id}/conducta', [ConstanciaController::class, 'conducta'])->name('conducta');
    });

    Route::middleware('auth.session')->group(function () {
        Route::get('/mi-cuenta', [CuentaController::class, 'perfil'])->name('cuenta.perfil');
        Route::post('/mi-cuenta', [CuentaController::class, 'actualizar'])->name('cuenta.actualizar');
        Route::post('/mi-cuenta/password', [CuentaController::class, 'cambiarPassword'])->name('cuenta.password');
    });

    // ── Rutas del Alumno (con AlumnoPermiso) ──────────────────────────────
    Route::middleware(['auth.session:estudiante', 'alumno.permiso'])
        ->prefix('alumno')
        ->name('alumno.')
        ->group(function () {
            Route::get('/notas',     [NotaController::class,    'index'])->name('notas');
            Route::get('/promedios', [NotaController::class,    'promedios'])->name('promedios');
            Route::get('/conductas', [ConductaController::class,'index'])->name('conductas');
            Route::get('/actividades', [ActividadController::class, 'misActividades'])->name('actividades');
            Route::get('/actividades/{id}', [ActividadController::class, 'miActividad'])->name('actividad.show');
            Route::post('/actividades/{id}/entregar', [ActividadController::class, 'entregar'])->name('actividad.entregar');
            Route::get('/horario',   [HorarioController::class, 'alumno'])->name('horario');
            Route::get('/libreta',   fn() => redirect('/boletas/' . session('estudiante_id')))->name('libreta');
            Route::get('/constancia-estudios', fn() => redirect(route('constancias.estudios', session('estudiante_id'))))->name('constancia.estudios');
            Route::get('/constancia-matricula', fn() => redirect(route('constancias.matricula', session('estudiante_id'))))->name('constancia.matricula');
            Route::get('/constancia-conducta', fn() => redirect(route('constancias.conducta', session('estudiante_id'))))->name('constancia.conducta');
        });

    // ── Usuarios — solo Promotor ───────────────────────────────────────────
    Route::middleware('auth.session:promotor')->group(function () {
        Route::get('/usuarios',         [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios',        [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}',    [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    });
});