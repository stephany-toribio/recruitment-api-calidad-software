<?php

namespace Database\Seeders;

use App\Models\AreaEstudio;
use App\Models\Habilidad;
use Illuminate\Database\Seeder;

class HabilidadSeeder extends Seeder
{
    public function run(): void
    {
        $areas = AreaEstudio::pluck('id', 'nombre');

        // Primero las 16 originales y en el mismo orden: conservan los IDs 1-16 que usa la colección Postman
        $originales = [
            ['nombre' => 'PHP', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'Laravel', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'Python', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'Node.js', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'JavaScript', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'React', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'Angular', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'Vue.js', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'MySQL', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'PostgreSQL', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'Docker', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'Git', 'tipo_habilidad' => 'tecnica', 'area' => 'Tecnología'],
            ['nombre' => 'Excel', 'tipo_habilidad' => 'tecnica', 'area' => 'Investigación y Datos'],
            ['nombre' => 'Trabajo en equipo', 'tipo_habilidad' => 'blanda', 'area' => null],
            ['nombre' => 'Comunicación efectiva', 'tipo_habilidad' => 'blanda', 'area' => null],
            ['nombre' => 'Liderazgo', 'tipo_habilidad' => 'blanda', 'area' => null],
        ];

        foreach ($originales as $habilidad) {
            Habilidad::updateOrCreate(
                ['nombre' => $habilidad['nombre']],
                [
                    'tipo_habilidad'  => $habilidad['tipo_habilidad'],
                    'area_estudio_id' => $habilidad['area'] ? $areas[$habilidad['area']] : null,
                    'activo'          => true,
                ]
            );
        }

        // Técnicas del catálogo del frontend (CREO_SKILLS). Cada una pertenece a una sola área (regla A).
        $tecnicasPorArea = [
            'Tecnología' => [
                'JavaScript', 'TypeScript', 'Python', 'Java', 'C#', 'PHP', 'React', 'Angular',
                'Vue.js', 'Node.js', 'Next.js', 'Express.js', 'GraphQL', 'REST APIs', 'SQL',
                'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Docker', 'Kubernetes', 'AWS', 'Azure',
                'GCP', 'Git', 'Linux', 'Scrum', 'Agile', 'CI/CD', 'Terraform', 'Django', 'Laravel',
                'Spring Boot', '.NET', 'TensorFlow', 'PyTorch', 'Pandas', 'Scikit-learn',
            ],
            'Ingeniería' => [
                'MATLAB', 'AutoCAD', 'SolidWorks', 'Instrumentación biomédica', 'Gestión de proyectos',
                'Control de calidad', 'Normas ISO', 'Mantenimiento de equipos médicos',
                'Simulación de procesos', 'Diseño CAD/CAM', 'Gestión de la cadena de suministro',
                'Six Sigma', 'Lectura de planos', 'Automatización industrial', 'Ingeniería de materiales',
                'Electrónica de potencia', 'Circuitos eléctricos', 'Programación de PLC', 'Robótica',
            ],
            'Ciencias de la Salud' => [
                'Atención al paciente', 'Laboratorio clínico', 'Primeros auxilios', 'Epidemiología',
                'Nutrición clínica', 'Farmacología', 'Bioquímica', 'Anatomía', 'Fisiología',
                'Terapia física', 'Cuidado intensivo', 'Farmacovigilancia', 'Salud pública',
                'Vacunación', 'Historia clínica electrónica', 'Bioseguridad',
            ],
            'Investigación y Datos' => [
                'Investigación científica', 'Estadística', 'SPSS', 'Análisis de datos',
                'Redacción académica', 'Revisión sistemática', 'Bioestadística', 'R', 'Power BI',
                'Excel avanzado', 'Machine Learning', 'Visualización de datos', 'Minería de datos',
                'Métodos cuantitativos', 'Métodos cualitativos', 'Muestreo estadístico',
            ],
            'Negocios y Administración' => [
                'Contabilidad financiera', 'Finanzas', 'Marketing digital', 'Administración',
                'Ventas', 'Atención al cliente', 'Negociación', 'Planeamiento estratégico',
                'Recursos humanos', 'Comercio exterior', 'Costos y presupuestos', 'SAP',
                'Google Analytics', 'Community management', 'Logística',
            ],
            'Diseño y Comunicación' => [
                'Figma', 'Adobe Photoshop', 'Adobe Illustrator', 'Adobe Premiere', 'After Effects',
                'UX Research', 'Prototipado', 'Diseño de interacción', 'Branding', 'Copywriting',
                'Fotografía', 'Edición de video', 'Diseño editorial', 'Ilustración digital',
                'Diseño UI', 'Wireframing', 'Diseño gráfico', 'UI/UX',
            ],
            'Ciencias Sociales y Educación' => [
                'Diseño curricular', 'Psicología clínica', 'Psicología organizacional',
                'Investigación cualitativa', 'Mediación de conflictos', 'Derecho civil',
                'Derecho laboral', 'Oratoria', 'Redacción legal', 'Docencia', 'Evaluación educativa',
                'Terapia cognitivo-conductual', 'Trabajo social', 'Análisis normativo',
                'Ética profesional', 'Gestión de conflictos', 'Tutoría académica',
            ],
        ];

        foreach ($tecnicasPorArea as $area => $nombres) {
            foreach ($nombres as $nombre) {
                Habilidad::updateOrCreate(
                    ['nombre' => $nombre],
                    ['tipo_habilidad' => 'tecnica', 'area_estudio_id' => $areas[$area], 'activo' => true]
                );
            }
        }

        // Blandas (SOFT_SKILLS del frontend): sin área, se muestran a todos los candidatos
        $blandas = [
            'Liderazgo', 'Comunicación efectiva', 'Trabajo en equipo', 'Resolución de problemas',
            'Pensamiento crítico', 'Gestión del tiempo', 'Adaptabilidad', 'Creatividad',
            'Empatía', 'Toma de decisiones', 'Proactividad', 'Resiliencia', 'Inteligencia emocional',
        ];

        foreach ($blandas as $nombre) {
            Habilidad::updateOrCreate(
                ['nombre' => $nombre],
                ['tipo_habilidad' => 'blanda', 'area_estudio_id' => null, 'activo' => true]
            );
        }
    }
}
