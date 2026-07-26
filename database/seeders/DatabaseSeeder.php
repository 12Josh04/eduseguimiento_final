<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private $faker;
    private int $anio;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create('es_PE');
        $this->anio  = (int) now()->year;

        $this->seedConfiguracionAdicional();

        $grados    = $this->seedGrados();
        $secciones = $this->seedSecciones($grados);
        $cursos    = $this->seedCursos();
        $periodos  = $this->seedPeriodos();

        $demoIds = $this->seedUsuariosBase();
        $docentes = $this->seedDocentes();

        ['rows' => $asignaciones, 'lookup' => $lookup] = $this->seedAsignaciones($docentes, $grados, $cursos);
        $this->seedHorarios($asignaciones);
        $this->seedClasesDesarrolladas($asignaciones);

        $estudiantes = $this->seedEstudiantes($grados, $secciones);
        $this->seedApoderados($estudiantes);
        $this->seedNotas($estudiantes, $cursos, $periodos, $lookup, $docentes[0]['id']);
        $this->seedActividades($asignaciones, $periodos);
        $this->seedConductas($estudiantes, $demoIds);
        $this->seedAsistencias($estudiantes, $grados, $lookup);
        $this->seedSesionesPsicologia($estudiantes, $demoIds['psicologa_id']);

        $this->command?->info('✅  Seeder completado.');
        $this->command?->info('');
        $this->command?->info('  promotor@eduseguimiento.com    → Promotor   (password123)');
        $this->command?->info('  docente@eduseguimiento.com     → Docente    (password123)');
        $this->command?->info('  psicologa@eduseguimiento.com   → Psicóloga  (password123)');
        $this->command?->info('  estudiante@eduseguimiento.com  → Estudiante (password123)');
        $this->command?->info('');
        $this->command?->info('  ' . count($docentes) . ' docentes, ' . count($estudiantes) . ' estudiantes, ' . count($cursos) . ' cursos (docenteN@eduseguimiento.com, password123).');
    }

    /** Devuelve, por cada grado, su clave compuesta "Nivel Nombre" (ej. "Secundaria 1°"), su id, nivel y nombre. */
    private function seedGrados(): array
    {
        $niveles = [
            'Primaria'   => ['1°', '2°', '3°', '4°', '5°', '6°'],
            'Secundaria' => ['1°', '2°', '3°', '4°', '5°'],
        ];

        $ids = [];
        foreach ($niveles as $nivel => $nombres) {
            foreach ($nombres as $nombre) {
                $id = (string) Str::uuid();
                DB::table('grados')->insert([
                    'id' => $id,
                    'nombre' => $nombre,
                    'nivel' => $nivel,
                    'anio_escolar' => $this->anio,
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $ids["$nivel $nombre"] = ['id' => $id, 'nivel' => $nivel, 'nombre' => $nombre];
            }
        }
        return $ids;
    }

    private function seedSecciones(array $grados): array
    {
        $ids = [];
        foreach ($grados as $key => $g) {
            foreach (['A', 'B'] as $letra) {
                DB::table('secciones')->insert([
                    'id' => (string) Str::uuid(),
                    'grado_id' => $g['id'],
                    'nombre' => $letra,
                    'anio_escolar' => $this->anio,
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $ids[$key][] = $letra;
            }
        }
        return $ids;
    }

    private function seedCursos(): array
    {
        $catalogo = [
            ['nombre' => 'Matemática',              'codigo' => 'MAT01', 'area' => 'Matemática'],
            ['nombre' => 'Comunicación',             'codigo' => 'COM01', 'area' => 'Comunicación'],
            ['nombre' => 'Ciencia y Tecnología',     'codigo' => 'CYT01', 'area' => 'Ciencia y Tecnología'],
            ['nombre' => 'Personal Social',          'codigo' => 'PSO01', 'area' => 'Desarrollo Personal, Ciudadanía y Cívica'],
            ['nombre' => 'Inglés',                   'codigo' => 'ING01', 'area' => 'Inglés'],
            ['nombre' => 'Educación Física',         'codigo' => 'EDF01', 'area' => 'Educación Física'],
            ['nombre' => 'Arte y Cultura',           'codigo' => 'ART01', 'area' => 'Arte y Cultura'],
            ['nombre' => 'Educación Religiosa',      'codigo' => 'REL01', 'area' => 'Educación Religiosa'],
            ['nombre' => 'Educación para el Trabajo', 'codigo' => 'EPT01', 'area' => 'Educación para el Trabajo'],
        ];

        $ids = [];
        foreach ($catalogo as $c) {
            $id = (string) Str::uuid();
            DB::table('cursos')->insert([
                'id' => $id,
                'nombre' => $c['nombre'],
                'area_curricular' => $c['area'],
                'codigo' => $c['codigo'],
                'nivel' => 'Básica Regular',
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ids[$c['nombre']] = $id;
        }
        return $ids;
    }

    private function seedConfiguracionAdicional(): void
    {
        $extra = [
            'dre' => 'DRE ANCASH',
            'ugel' => 'UGEL Huaraz',
            'codigo_modular' => '1309947-0',
            'nivel_educativo' => 'Primaria y Secundaria',
            // Placeholder: reemplazar por el número real de WhatsApp de matrícula (formato: 51987654321, sin "+").
            'whatsapp_matricula' => '51999999999',
        ];

        foreach ($extra as $clave => $valor) {
            DB::table('configuraciones_institucionales')->updateOrInsert(
                ['clave' => $clave],
                [
                    'id' => (string) Str::uuid(),
                    'valor' => $valor,
                    'tipo' => 'text',
                    'descripcion' => 'Dato institucional para la libreta de notas oficial',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedPeriodos(): array
    {
        $bimestres = [
            ['nombre' => 'Bimestre I',   'inicio' => "{$this->anio}-03-01", 'fin' => "{$this->anio}-05-10"],
            ['nombre' => 'Bimestre II',  'inicio' => "{$this->anio}-05-11", 'fin' => "{$this->anio}-07-20"],
            ['nombre' => 'Bimestre III', 'inicio' => "{$this->anio}-08-01", 'fin' => "{$this->anio}-10-10"],
            ['nombre' => 'Bimestre IV',  'inicio' => "{$this->anio}-10-11", 'fin' => "{$this->anio}-12-20"],
        ];

        $ids = [];
        foreach ($bimestres as $b) {
            $id = (string) Str::uuid();
            DB::table('periodos')->insert([
                'id' => $id,
                'nombre' => $b['nombre'],
                'tipo' => 'bimestre',
                'anio_escolar' => $this->anio,
                'fecha_inicio' => $b['inicio'],
                'fecha_fin' => $b['fin'],
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ids[] = $id;
        }
        return $ids;
    }

    private function crearUsuario(string $nombre, string $email, int $rolId): string
    {
        $id = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $id,
            'nombre' => $nombre,
            'email' => $email,
            'password_hash' => Hash::make('password123'),
            'rol_id' => $rolId,
            'activo' => 1,
            'creado_en' => now(),
            'actualizado_en' => now(),
        ]);
        return $id;
    }

    private function seedUsuariosBase(): array
    {
        $promotorId  = $this->crearUsuario('Promotor General', 'promotor@eduseguimiento.com', 1);
        $psicologaId = $this->crearUsuario('Psicóloga Demo', 'psicologa@eduseguimiento.com', 3);

        return [
            'promotor_id'  => $promotorId,
            'psicologa_id' => $psicologaId,
        ];
    }

    /**
     * 15 docentes: 9 especialistas de Secundaria (uno por curso, dictan en los 5 grados)
     * y 6 tutores de Primaria (uno por grado, dictan todos los cursos de su grado).
     * El primero (Matemática - Secundaria) conserva el login de pruebas docente@eduseguimiento.com.
     */
    private function seedDocentes(): array
    {
        $cursosSecundaria = [
            'Matemática', 'Comunicación', 'Ciencia y Tecnología', 'Personal Social',
            'Inglés', 'Educación Física', 'Arte y Cultura', 'Educación Religiosa', 'Educación para el Trabajo',
        ];
        $gradosPrimaria = ['1°', '2°', '3°', '4°', '5°', '6°'];

        $docentes = [];
        $n = 1;

        foreach ($cursosSecundaria as $i => $curso) {
            $nombre = $this->faker->firstName() . ' ' . $this->faker->lastName() . ' ' . $this->faker->lastName();
            $email = $n === 1 ? 'docente@eduseguimiento.com' : "docente{$n}@eduseguimiento.com";
            $id = $this->crearUsuario($nombre, $email, 2);
            $docentes[] = ['id' => $id, 'nombre' => $nombre, 'tipo' => 'secundaria', 'curso' => $curso];
            $n++;
        }

        foreach ($gradosPrimaria as $g) {
            $nombre = $this->faker->firstName() . ' ' . $this->faker->lastName() . ' ' . $this->faker->lastName();
            $id = $this->crearUsuario($nombre, "docente{$n}@eduseguimiento.com", 2);
            $docentes[] = ['id' => $id, 'nombre' => $nombre, 'tipo' => 'primaria_tutor', 'grado' => "Primaria $g"];
            $n++;
        }

        return $docentes;
    }

    /**
     * Especialistas de Secundaria: dictan su curso en los 5 grados.
     * Tutores de Primaria: dictan todos los cursos en su único grado.
     * Devuelve también un lookup [gradoKey][cursoNombre] => docente_id para atribuir notas/asistencia/clases.
     */
    private function seedAsignaciones(array $docentes, array $grados, array $cursos): array
    {
        $gradosSecundaria = ['Secundaria 1°', 'Secundaria 2°', 'Secundaria 3°', 'Secundaria 4°', 'Secundaria 5°'];

        $rows = [];
        $lookup = [];

        foreach ($docentes as $d) {
            if ($d['tipo'] === 'secundaria') {
                foreach ($gradosSecundaria as $gradoKey) {
                    $id = (string) Str::uuid();
                    $cursoId = $cursos[$d['curso']];
                    DB::table('asignaciones_docente')->insert([
                        'id' => $id, 'docente_id' => $d['id'], 'curso_id' => $cursoId,
                        'grado_id' => $grados[$gradoKey]['id'], 'seccion' => null,
                        'anio_escolar' => $this->anio, 'activo' => 1, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $rows[] = ['id' => $id, 'docente_id' => $d['id'], 'curso_id' => $cursoId, 'curso_nombre' => $d['curso'], 'grado_id' => $grados[$gradoKey]['id'], 'grado_key' => $gradoKey];
                    $lookup[$gradoKey][$d['curso']] = $d['id'];
                }
            } else {
                foreach ($cursos as $nombreCurso => $cursoId) {
                    $id = (string) Str::uuid();
                    DB::table('asignaciones_docente')->insert([
                        'id' => $id, 'docente_id' => $d['id'], 'curso_id' => $cursoId,
                        'grado_id' => $grados[$d['grado']]['id'], 'seccion' => null,
                        'anio_escolar' => $this->anio, 'activo' => 1, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $rows[] = ['id' => $id, 'docente_id' => $d['id'], 'curso_id' => $cursoId, 'curso_nombre' => $nombreCurso, 'grado_id' => $grados[$d['grado']]['id'], 'grado_key' => $d['grado']];
                    $lookup[$d['grado']][$nombreCurso] = $d['id'];
                }
            }
        }

        return ['rows' => $rows, 'lookup' => $lookup];
    }

    private function seedHorarios(array $asignaciones): void
    {
        $slots = [];
        foreach ([1, 2, 3, 4, 5] as $dia) {
            $slots[] = ['dia' => $dia, 'inicio' => '08:00', 'fin' => '09:30'];
            $slots[] = ['dia' => $dia, 'inicio' => '10:00', 'fin' => '11:30'];
        }
        $aulas = ['Aula 101', 'Aula 102', 'Aula 103', 'Aula 104', 'Laboratorio', 'Aula 201'];

        $porDocente = [];
        foreach ($asignaciones as $a) {
            $porDocente[$a['docente_id']][] = $a;
        }

        $filas = [];
        foreach ($porDocente as $lista) {
            foreach ($lista as $i => $a) {
                $slot = $slots[$i % count($slots)];
                $filas[] = [
                    'id' => (string) Str::uuid(),
                    'asignacion_id' => $a['id'],
                    'dia_semana' => $slot['dia'],
                    'hora_inicio' => $slot['inicio'],
                    'hora_fin' => $slot['fin'],
                    'aula' => $aulas[$i % count($aulas)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('horarios')->insert($filas);
    }

    /** 4 sesiones de clase por cada asignación docente-curso-grado, dentro del Bimestre I. */
    private function seedClasesDesarrolladas(array $asignaciones): void
    {
        $temas = [
            'Presentación del curso y normas de convivencia',
            'Desarrollo de conceptos fundamentales de la unidad 1',
            'Práctica guiada y resolución de ejercicios',
            'Evaluación formativa y retroalimentación',
        ];
        $inicioBimestre1 = \Carbon\Carbon::parse("{$this->anio}-03-03");

        $filas = [];
        foreach ($asignaciones as $a) {
            foreach ($temas as $i => $tema) {
                $filas[] = [
                    'id' => (string) Str::uuid(),
                    'curso_id' => $a['curso_id'],
                    'grado_id' => $a['grado_id'],
                    'seccion' => null,
                    'docente_id' => $a['docente_id'],
                    'fecha' => $inicioBimestre1->copy()->addWeeks($i)->format('Y-m-d'),
                    'tema' => $tema,
                    'descripcion' => 'Sesión de aprendizaje correspondiente a la unidad 1 del área de ' . $a['curso_nombre'] . '.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($filas, 400) as $lote) {
            DB::table('clases_desarrolladas')->insert($lote);
        }
    }

    /**
     * Una actividad (trabajo con indicaciones) por cada asignación docente-curso-grado, en Bimestre I,
     * con entregas de los estudiantes en estados variados (pendiente / entregado / calificado) para
     * poder probar de inmediato el flujo docente-sube-trabajo → alumno-entrega → docente-califica.
     */
    private function seedActividades(array $asignaciones, array $periodos): void
    {
        $periodoId = $periodos[0]; // Bimestre I

        $titulos = [
            'tarea' => 'Trabajo N.º 1',
            'practica' => 'Práctica calificada N.º 1',
            'examen' => 'Examen Bimestral I',
        ];
        $tipos = ['tarea', 'practica', 'examen'];

        $filasEntregas = [];
        $filasNotas = [];

        foreach ($asignaciones as $i => $a) {
            $tipo = $tipos[$i % count($tipos)];
            $actividadId = (string) Str::uuid();

            DB::table('actividades')->insert([
                'id' => $actividadId,
                'curso_id' => $a['curso_id'],
                'grado_id' => $a['grado_id'],
                'seccion' => null,
                'periodo_id' => $periodoId,
                'docente_id' => $a['docente_id'],
                'titulo' => $titulos[$tipo] . ' — ' . $a['curso_nombre'],
                'instrucciones' => "Desarrolla las actividades propuestas para la unidad 1 de {$a['curso_nombre']}. Presenta tu trabajo ordenado, con nombre completo, grado y sección. Se evaluará orden, claridad y correcta aplicación de los conceptos revisados en clase.",
                'tipo_evaluacion' => $tipo,
                'fecha_entrega' => \Carbon\Carbon::parse("{$this->anio}-04-10")->format('Y-m-d'),
                'archivo_path' => null,
                'activo' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $estudiantesGrado = DB::table('matriculas')->where('grado_id', $a['grado_id'])->where('activo', 1)->pluck('estudiante_id');

            foreach ($estudiantesGrado as $j => $estId) {
                $entregaId = (string) Str::uuid();
                $mod = $j % 4;

                if ($mod === 0) {
                    // pendiente: aún no entrega
                    $filasEntregas[] = [
                        'id' => $entregaId, 'actividad_id' => $actividadId, 'estudiante_id' => $estId,
                        'estado' => 'pendiente', 'archivo_path' => null, 'comentario_estudiante' => null,
                        'nota' => null, 'observacion_docente' => null, 'entregado_en' => null, 'calificado_en' => null,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                } elseif ($mod === 1) {
                    // entregado, pendiente de calificar
                    $filasEntregas[] = [
                        'id' => $entregaId, 'actividad_id' => $actividadId, 'estudiante_id' => $estId,
                        'estado' => 'entregado', 'archivo_path' => null, 'comentario_estudiante' => 'Aquí adjunto mi trabajo, cualquier corrección avísenme.',
                        'nota' => null, 'observacion_docente' => null,
                        'entregado_en' => now()->subDays(random_int(1, 5)), 'calificado_en' => null,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                } else {
                    // calificado
                    $nota = random_int(10, 20);
                    $filasEntregas[] = [
                        'id' => $entregaId, 'actividad_id' => $actividadId, 'estudiante_id' => $estId,
                        'estado' => 'calificado', 'archivo_path' => null, 'comentario_estudiante' => 'Entrego mi trabajo completo.',
                        'nota' => $nota, 'observacion_docente' => $nota >= 14 ? 'Buen trabajo, cumple con lo solicitado.' : 'Cumple parcialmente, reforzar para la próxima entrega.',
                        'entregado_en' => now()->subDays(random_int(6, 10)), 'calificado_en' => now()->subDays(random_int(1, 5)),
                        'created_at' => now(), 'updated_at' => now(),
                    ];

                    $filasNotas[] = [
                        'id' => (string) Str::uuid(),
                        'estudiante_id' => $estId,
                        'curso_id' => $a['curso_id'],
                        'actividad_id' => $actividadId,
                        'docente_id' => $a['docente_id'],
                        'periodo_id' => $periodoId,
                        'grado_id' => $a['grado_id'],
                        'seccion' => null,
                        'tipo_evaluacion' => $tipo,
                        'nombre_evaluacion' => $titulos[$tipo] . ' — ' . $a['curso_nombre'],
                        'nota' => $nota,
                        'nota_maxima' => 20,
                        'observacion' => null,
                        'fecha' => now()->subDays(random_int(1, 5))->format('Y-m-d'),
                        'creado_en' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($filasEntregas, 400) as $lote) {
            DB::table('entregas_actividad')->insert($lote);
        }
        foreach (array_chunk($filasNotas, 400) as $lote) {
            DB::table('notas')->insert($lote);
        }
    }

    /** Mínimo 5 estudiantes por cada grado + sección (22 combinaciones = 110 estudiantes). */
    private function seedEstudiantes(array $grados, array $secciones): array
    {
        $estudiantes = [];
        $contador = 0;

        $userDemoId = $this->crearUsuario('Estudiante Demo', 'estudiante@eduseguimiento.com', 4);
        $contador++;
        $estudiantes[] = $this->crearEstudianteEnSeccion($userDemoId, 'Demo', 'Estudiante', '2010-04-15', $contador, $grados, 'Secundaria 1°', 'A');

        foreach ($grados as $gradoKey => $g) {
            foreach ($secciones[$gradoKey] as $letra) {
                $porSeccion = ($gradoKey === 'Secundaria 1°' && $letra === 'A') ? 4 : 5; // "A" ya tiene al estudiante demo
                for ($n = 0; $n < $porSeccion; $n++) {
                    $contador++;
                    $nombre = $this->faker->firstName();
                    $apellido = $this->faker->lastName() . ' ' . $this->faker->lastName();
                    $userId = $this->crearUsuario("$nombre $apellido", "estudiante{$contador}@eduseguimiento.com", 4);
                    $fechaNacimiento = $this->faker->dateTimeBetween('-17 years', '-6 years')->format('Y-m-d');
                    $estudiantes[] = $this->crearEstudianteEnSeccion($userId, $nombre, $apellido, $fechaNacimiento, $contador, $grados, $gradoKey, $letra);
                }
            }
        }

        return $estudiantes;
    }

    private function crearEstudianteEnSeccion(string $userId, string $nombre, string $apellido, string $fechaNacimiento, int $contador, array $grados, string $gradoKey, string $seccionLetra): array
    {
        $estId = (string) Str::uuid();
        $codigo = 'EST' . str_pad((string) $contador, 5, '0', STR_PAD_LEFT);

        DB::table('estudiantes')->insert([
            'id' => $estId,
            'usuario_id' => $userId,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'codigo' => $codigo,
            'fecha_nacimiento' => $fechaNacimiento,
            'dni' => (string) random_int(10000000, 79999999),
            'telefono' => '9' . random_int(10000000, 99999999),
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('matriculas')->insert([
            'id' => (string) Str::uuid(),
            'estudiante_id' => $estId,
            'grado_id' => $grados[$gradoKey]['id'],
            'seccion' => $seccionLetra,
            'anio_escolar' => $this->anio,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $estId,
            'grado' => $grados[$gradoKey]['nombre'],
            'nivel' => $grados[$gradoKey]['nivel'],
            'grado_key' => $gradoKey,
            'seccion' => $seccionLetra,
        ];
    }

    private function seedApoderados(array $estudiantes): void
    {
        $filas = [];
        foreach ($estudiantes as $est) {
            $filas[] = [
                'id' => (string) Str::uuid(),
                'estudiante_id' => $est['id'],
                'nombre' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
                'parentesco' => $this->faker->randomElement(['padre', 'madre', 'tutor']),
                'telefono' => '9' . random_int(10000000, 99999999),
                'email' => $this->faker->unique()->safeEmail(),
                'es_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($filas, 400) as $lote) {
            DB::table('apoderados')->insert($lote);
        }
    }

    private function seedNotas(array $estudiantes, array $cursos, array $periodos, array $lookup, string $docenteFallbackId): void
    {
        $tipos = ['examen', 'tarea', 'practica'];

        $conclusiones = [
            'AD' => [
                'Demuestra un manejo sobresaliente, superando lo esperado para el grado.',
                'Evidencia aprendizajes que van más allá del nivel esperado en el área.',
            ],
            'A' => [
                'Demuestra manejo satisfactorio en las tareas propuestas del área.',
                'Evidencia el nivel esperado para el grado, con desempeño consistente.',
            ],
            'B' => [
                'Se encuentra próximo al nivel esperado; requiere acompañamiento para consolidar el logro.',
                'Necesita reforzar algunos procedimientos propios del área para alcanzar el nivel esperado.',
            ],
            'C' => [
                'Evidencia dificultades frecuentes en las tareas del área; necesita mayor acompañamiento.',
                'Muestra un progreso mínimo respecto al nivel esperado para el grado.',
            ],
        ];

        // Un grupo de estudiantes, repartido entre secciones, queda deliberadamente en riesgo
        // (bajo rendimiento), para poder probar las alertas automáticas de la Psicóloga.
        $enRiesgoIds = collect($estudiantes)->filter(fn($e, $i) => $i % 12 === 5)->pluck('id')->all();

        $filas = [];
        foreach ($estudiantes as $est) {
            $enRiesgo = in_array($est['id'], $enRiesgoIds);
            foreach ($cursos as $nombreCurso => $cursoId) {
                $docenteId = $lookup[$est['grado_key']][$nombreCurso] ?? $docenteFallbackId;
                foreach (array_slice($periodos, 0, 2) as $periodoId) {
                    $nota = $enRiesgo ? random_int(5, 10) : random_int(8, 20);
                    $nivel = $nota >= 18 ? 'AD' : ($nota >= 14 ? 'A' : ($nota >= 11 ? 'B' : 'C'));
                    $filas[] = [
                        'id' => (string) Str::uuid(),
                        'estudiante_id' => $est['id'],
                        'curso_id' => $cursoId,
                        'docente_id' => $docenteId,
                        'periodo_id' => $periodoId,
                        'grado_id' => null,
                        'seccion' => $est['seccion'],
                        'tipo_evaluacion' => $this->faker->randomElement($tipos),
                        'nombre_evaluacion' => 'Evaluación ' . $nombreCurso,
                        'nota' => $nota,
                        'nota_maxima' => 20,
                        'observacion' => $this->faker->randomElement($conclusiones[$nivel]),
                        'fecha' => now()->subDays(random_int(1, 90))->format('Y-m-d'),
                        'creado_en' => now(),
                    ];
                    if (count($filas) >= 400) {
                        DB::table('notas')->insert($filas);
                        $filas = [];
                    }
                }
            }
        }
        if ($filas) {
            DB::table('notas')->insert($filas);
        }
    }

    /** Asistencia diaria (Bimestre I) tomada por el docente de Matemática de cada grado. */
    private function seedAsistencias(array $estudiantes, array $grados, array $lookup): void
    {
        $estadosProbables = ['presente', 'presente', 'presente', 'presente', 'tardanza', 'falta', 'justificado'];
        $cursoAsistencia = 'Matemática';

        // Un subconjunto de estudiantes queda con asistencia deliberadamente baja, para las alertas de riesgo.
        $bajaAsistenciaIds = collect($estudiantes)->filter(fn($e, $i) => $i % 10 === 3)->pluck('id')->all();

        // Se anclan las fechas dentro del Bimestre I (en vez de "hoy - N días") para que la
        // asistencia siempre caiga dentro de un período lectivo, sin importar cuándo se ejecute el seeder.
        $inicioBimestre1 = \Carbon\Carbon::parse("{$this->anio}-03-03");

        $cursos = DB::table('cursos')->pluck('id', 'nombre')->all();

        $filas = [];
        for ($dia = 0; $dia < 10; $dia++) {
            $fecha = $inicioBimestre1->copy()->addDays($dia);
            if ($fecha->isWeekend()) {
                continue;
            }
            foreach ($estudiantes as $est) {
                $docenteId = $lookup[$est['grado_key']][$cursoAsistencia] ?? null;
                if (!$docenteId) {
                    continue;
                }
                $enRiesgo = in_array($est['id'], $bajaAsistenciaIds);
                $estado = $enRiesgo
                    ? $this->faker->randomElement(['falta', 'falta', 'tardanza', 'presente'])
                    : $this->faker->randomElement($estadosProbables);

                $filas[] = [
                    'id' => (string) Str::uuid(),
                    'estudiante_id' => $est['id'],
                    'curso_id' => $cursos[$cursoAsistencia],
                    'docente_id' => $docenteId,
                    'grado_id' => $grados[$est['grado_key']]['id'],
                    'seccion' => $est['seccion'],
                    'fecha' => $fecha->format('Y-m-d'),
                    'estado' => $estado,
                    'observacion' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (count($filas) >= 400) {
                    DB::table('asistencias')->insert($filas);
                    $filas = [];
                }
            }
        }
        if ($filas) {
            DB::table('asistencias')->insert($filas);
        }
    }

    private function seedConductas(array $estudiantes, array $demoIds): void
    {
        $descripciones = [
            'positivo' => [
                'Ayudó a un compañero con la tarea de clase.',
                'Participación destacada durante la sesión de aprendizaje.',
                'Mostró liderazgo positivo en el trabajo en equipo.',
            ],
            'negativo' => [
                'Interrumpió reiteradamente el desarrollo de la clase.',
                'No presentó la tarea asignada sin justificación.',
                'Conflicto verbal con un compañero durante el recreo.',
            ],
            'observacion' => [
                'Se recomienda seguimiento por bajo rendimiento reciente.',
                'Cambio de comportamiento notado por la tutora del aula.',
                'Se cita a los padres para conversar sobre su progreso.',
            ],
        ];

        // Reportador alterna entre el psicólogo (registro psicológico) y el propio docente de Matemática del grado.
        $muestra = collect($estudiantes)->filter(fn($e, $i) => $i % 6 === 0)->values()->all();

        foreach ($muestra as $i => $est) {
            $tipo = array_keys($descripciones)[$i % 3];
            DB::table('conductas')->insert([
                'id' => (string) Str::uuid(),
                'estudiante_id' => $est['id'],
                'reportador_id' => $i % 2 === 0 ? $demoIds['psicologa_id'] : $demoIds['promotor_id'],
                'tipo' => $tipo,
                'descripcion' => $this->faker->randomElement($descripciones[$tipo]),
                'lugar' => $this->faker->randomElement(['Aula', 'Patio', 'Biblioteca', 'Laboratorio']),
                'fecha_hora' => now()->subDays(random_int(0, 13))->subHours(random_int(0, 6)),
            ]);
        }
    }

    /**
     * Sesiones de acompañamiento psicológico (1 o 2 por estudiante) para el mismo grupo
     * marcado en riesgo académico en seedNotas(), dando continuidad narrativa a la simulación.
     */
    private function seedSesionesPsicologia(array $estudiantes, string $psicologaId): void
    {
        $motivos = [
            'Seguimiento por bajo rendimiento académico reciente',
            'Apoyo emocional tras situación familiar reportada por el tutor',
            'Orientación vocacional y manejo del estrés escolar',
            'Seguimiento por inasistencias reiteradas',
        ];
        $observacionesPool = [
            'El estudiante muestra buena disposición al diálogo; se recomienda seguimiento en 2 semanas.',
            'Se identifican factores familiares que podrían estar afectando su desempeño; se coordinará con el apoderado.',
            'Muestra mejoría respecto a la sesión anterior; continuar monitoreo mensual.',
            'Se sugiere derivación a especialista externo si la situación no mejora.',
        ];
        $acuerdosPool = [
            'Reforzar hábitos de estudio en casa con apoyo del apoderado.',
            'Cita de seguimiento programada; se informará al tutor del aula.',
            'Se acuerda check-in semanal breve con el estudiante.',
        ];

        $muestra = collect($estudiantes)->filter(fn($e, $i) => $i % 12 === 5)->values()->all();

        foreach ($muestra as $est) {
            $numSesiones = random_int(1, 2);
            for ($s = 0; $s < $numSesiones; $s++) {
                DB::table('sesiones_psicologia')->insert([
                    'id' => (string) Str::uuid(),
                    'estudiante_id' => $est['id'],
                    'psicologo_id' => $psicologaId,
                    'fecha' => now()->subDays(random_int(3, 60))->format('Y-m-d'),
                    'motivo' => $this->faker->randomElement($motivos),
                    'observaciones' => $this->faker->randomElement($observacionesPool),
                    'acuerdos' => $this->faker->randomElement($acuerdosPool),
                    'proxima_cita' => random_int(0, 1) ? now()->addDays(random_int(7, 21))->format('Y-m-d') : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
