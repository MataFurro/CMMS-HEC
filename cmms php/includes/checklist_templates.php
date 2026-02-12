<?php
// includes/checklist_templates.php
// Plantillas de Lista de Chequeo por Familia de Equipo Médico
// Basadas en formatos V1 (Cualitativo) y V2 (Cuantitativo + Metrología)

$checklist_templates = [

    // ═══════════════════════════════════════════════════════════════
    // MONITOR DE SIGNOS VITALES (Formato V2 - Cuantitativo)
    // ═══════════════════════════════════════════════════════════════
    'monitor_signos_vitales' => [
        'label' => 'Monitor de Signos Vitales',
        'icon'  => 'monitor_heart',
        'version' => 'V2',

        // Sección 1: Inspección Visual (Checklist Pasa/Falla/N.A.)
        'qualitative' => [
            'Verificar encendido del equipo',
            'Limpieza externa de carcasa y pantalla',
            'Estado de cable de poder y enchufe (AC Plug)',
            'Estado de conectores y accesorios (SpO2, ECG, PANI, Temp)',
            'Verificar estado de pantalla y display',
            'Funcionamiento de botones y perillas',
            'Alarmas sonoras y visuales operativas',
            'Verificar estado de batería (carga y autonomía)',
            'Estado de ruedas y frenos (si aplica)',
            'Autodiagnóstico del equipo',
        ],

        // Sección 2: Pruebas Cuantitativas (Valor Simulado vs Medido)
        'quantitative' => [
            [
                'group' => 'Saturación de Oxígeno (SpO2)',
                'unit' => '%',
                'tolerance_label' => '±5%',
                'points' => [
                    ['simulated' => 90],
                    ['simulated' => 95],
                    ['simulated' => 99],
                ]
            ],
            [
                'group' => 'Respiración',
                'unit' => 'RPM',
                'tolerance_label' => '±2 RPM',
                'points' => [
                    ['simulated' => 15],
                    ['simulated' => 30],
                    ['simulated' => 60],
                ]
            ],
            [
                'group' => 'Frecuencia Cardíaca (ECG)',
                'unit' => 'LPM',
                'tolerance_label' => '±3 LPM',
                'points' => [
                    ['simulated' => 30],
                    ['simulated' => 60],
                    ['simulated' => 120],
                    ['simulated' => 180],
                ]
            ],
            [
                'group' => 'Temperatura',
                'unit' => '°C',
                'tolerance_label' => '±1°C',
                'points' => [
                    ['simulated' => 37],
                ]
            ],
            [
                'group' => 'Presión Arterial No Invasiva (PANI)',
                'unit' => 'mmHg',
                'tolerance_label' => '±10 mmHg',
                'points' => [
                    ['simulated' => '60/30'],
                    ['simulated' => '120/80'],
                    ['simulated' => '200/150'],
                ]
            ],
        ],

        // Sección 3: Seguridad Eléctrica (IEC 62353)
        'electrical_safety' => [
            ['param' => 'Tensión de red',      'expected' => '220V',     'tolerance' => '±10%'],
            ['param' => 'Resistencia a tierra', 'expected' => '≤ 0.2 Ω', 'tolerance' => 'Normativo'],
            ['param' => 'Corriente de fuga',    'expected' => '≤ 0.5 mA', 'tolerance' => 'Normativo'],
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // MONITOR DESFIBRILADOR (Formato V2 - Cuantitativo)
    // ═══════════════════════════════════════════════════════════════
    'monitor_desfibrilador' => [
        'label' => 'Monitor Desfibrilador',
        'icon'  => 'electrical_services',
        'version' => 'V2',

        'qualitative' => [
            'Verificar encendido del equipo',
            'Limpieza externa de carcasa y pantalla',
            'Estado de cable de poder y enchufe',
            'Estado de paletas internas y externas',
            'Estado de cables de ECG y accesorios',
            'Funcionamiento de pantalla y display',
            'Alarmas sonoras y visuales operativas',
            'Verificar estado de batería y carga',
            'Prueba de descarga con carga interna',
            'Verificar impresora/registrador (si aplica)',
            'Autodiagnóstico del equipo',
        ],

        'quantitative' => [
            [
                'group' => 'Energía de Descarga',
                'unit' => 'J',
                'tolerance_label' => '±15%',
                'points' => [
                    ['simulated' => 50],
                    ['simulated' => 100],
                    ['simulated' => 200],
                    ['simulated' => 360],
                ]
            ],
            [
                'group' => 'Frecuencia Cardíaca (ECG)',
                'unit' => 'LPM',
                'tolerance_label' => '±3 LPM',
                'points' => [
                    ['simulated' => 30],
                    ['simulated' => 60],
                    ['simulated' => 120],
                    ['simulated' => 180],
                ]
            ],
            [
                'group' => 'SpO2',
                'unit' => '%',
                'tolerance_label' => '±5%',
                'points' => [
                    ['simulated' => 90],
                    ['simulated' => 95],
                    ['simulated' => 99],
                ]
            ],
        ],

        'electrical_safety' => [
            ['param' => 'Tensión de red',      'expected' => '220V',     'tolerance' => '±10%'],
            ['param' => 'Resistencia a tierra', 'expected' => '≤ 0.2 Ω', 'tolerance' => 'Normativo'],
            ['param' => 'Corriente de fuga',    'expected' => '≤ 0.5 mA', 'tolerance' => 'Normativo'],
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // INCUBADORA FIJA (Formato V1 - Solo Cualitativo)
    // ═══════════════════════════════════════════════════════════════
    'incubadora_fija' => [
        'label' => 'Incubadora Fija',
        'icon'  => 'child_care',
        'version' => 'V1',

        'qualitative' => [
            'Verificar encendido del equipo',
            'Limpieza externa de carcasa',
            'Estado de cable de poder y enchufe',
            'Estado y limpieza de cúpula principal',
            'Mecanismo de subida y bajada de bandeja',
            'Funcionamiento de puertas y accesos',
            'Estado de colchón y sistema de pesaje',
            'Celda de oxígeno (estado y calibración)',
            'Cambio de filtros (si corresponde)',
            'Alarmas sonoras y visuales operativas',
            'Funcionamiento de display y panel de control',
            'Pruebas funcionales generales',
        ],

        // Sin pruebas cuantitativas en formato V1
        'quantitative' => [],

        'electrical_safety' => [
            ['param' => 'Tensión de red',      'expected' => '220V',     'tolerance' => '±10%'],
            ['param' => 'Resistencia a tierra', 'expected' => '≤ 0.2 Ω', 'tolerance' => 'Normativo'],
            ['param' => 'Corriente de fuga',    'expected' => '≤ 0.5 mA', 'tolerance' => 'Normativo'],
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // INCUBADORA DE TRANSPORTE (Formato V1 - Solo Cualitativo)
    // ═══════════════════════════════════════════════════════════════
    'incubadora_transporte' => [
        'label' => 'Incubadora de Transporte',
        'icon'  => 'local_shipping',
        'version' => 'V1',

        'qualitative' => [
            'Verificar encendido del equipo',
            'Limpieza de carcasa y cúpula',
            'Estado de cable de poder y batería',
            'Estado de cúpula de transporte',
            'Mecanismo de fijación a camilla/ambulancia',
            'Sistema de oxígeno portátil',
            'Funcionamiento de alarmas',
            'Verificación de autonomía de batería',
            'Panel de control y display',
            'Pruebas funcionales generales',
        ],

        'quantitative' => [],

        'electrical_safety' => [
            ['param' => 'Tensión de red',      'expected' => '220V',     'tolerance' => '±10%'],
            ['param' => 'Resistencia a tierra', 'expected' => '≤ 0.2 Ω', 'tolerance' => 'Normativo'],
            ['param' => 'Corriente de fuga',    'expected' => '≤ 0.5 mA', 'tolerance' => 'Normativo'],
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // VENTILADOR MECÁNICO (Formato V2 - Cuantitativo)
    // ═══════════════════════════════════════════════════════════════
    'ventilador_mecanico' => [
        'label' => 'Ventilador Mecánico',
        'icon'  => 'air',
        'version' => 'V2',

        'qualitative' => [
            'Verificar encendido del equipo',
            'Limpieza externa de carcasa',
            'Estado de cable de poder y enchufe',
            'Estado de mangueras y circuito paciente',
            'Verificar válvulas de exhalación e inhalación',
            'Estado de filtros (HEPA, antibacteriano)',
            'Funcionamiento de pantalla y display',
            'Funcionamiento de alarmas de presión y volumen',
            'Estado de sensores de flujo y presión',
            'Verificar batería y autonomía',
            'Autodiagnóstico del equipo',
        ],

        'quantitative' => [
            [
                'group' => 'Volumen Tidal',
                'unit' => 'mL',
                'tolerance_label' => '±10%',
                'points' => [
                    ['simulated' => 200],
                    ['simulated' => 500],
                    ['simulated' => 800],
                ]
            ],
            [
                'group' => 'Frecuencia Respiratoria',
                'unit' => 'RPM',
                'tolerance_label' => '±2 RPM',
                'points' => [
                    ['simulated' => 10],
                    ['simulated' => 20],
                    ['simulated' => 40],
                ]
            ],
            [
                'group' => 'Presión Inspiratoria (PIP)',
                'unit' => 'cmH₂O',
                'tolerance_label' => '±2 cmH₂O',
                'points' => [
                    ['simulated' => 15],
                    ['simulated' => 25],
                    ['simulated' => 40],
                ]
            ],
            [
                'group' => 'PEEP',
                'unit' => 'cmH₂O',
                'tolerance_label' => '±1 cmH₂O',
                'points' => [
                    ['simulated' => 5],
                    ['simulated' => 10],
                    ['simulated' => 15],
                ]
            ],
            [
                'group' => 'FiO2',
                'unit' => '%',
                'tolerance_label' => '±3%',
                'points' => [
                    ['simulated' => 21],
                    ['simulated' => 50],
                    ['simulated' => 100],
                ]
            ],
        ],

        'electrical_safety' => [
            ['param' => 'Tensión de red',      'expected' => '220V',     'tolerance' => '±10%'],
            ['param' => 'Resistencia a tierra', 'expected' => '≤ 0.2 Ω', 'tolerance' => 'Normativo'],
            ['param' => 'Corriente de fuga',    'expected' => '≤ 0.5 mA', 'tolerance' => 'Normativo'],
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // MÁQUINA DE ANESTESIA (Formato V2 - Cuantitativo)
    // ═══════════════════════════════════════════════════════════════
    'maquina_anestesia' => [
        'label' => 'Máquina de Anestesia',
        'icon'  => 'vaccines',
        'version' => 'V2',

        'qualitative' => [
            'Verificar encendido del equipo',
            'Limpieza externa de carcasa',
            'Estado de cable de poder y enchufe',
            'Estado de mangueras y circuito respiratorio',
            'Verificar sistema de vaporizadores',
            'Estado de absorbedor de CO₂ (cal sodada)',
            'Funcionamiento de flujímetros',
            'Prueba de estanqueidad del sistema',
            'Verificar válvulas APL y unidireccionales',
            'Alarmas de presión, volumen y gases',
            'Estado de batería y respaldo eléctrico',
            'Autodiagnóstico del equipo',
        ],

        'quantitative' => [
            [
                'group' => 'Volumen Tidal',
                'unit' => 'mL',
                'tolerance_label' => '±10%',
                'points' => [
                    ['simulated' => 300],
                    ['simulated' => 500],
                    ['simulated' => 700],
                ]
            ],
            [
                'group' => 'Presión del Sistema',
                'unit' => 'cmH₂O',
                'tolerance_label' => '±2 cmH₂O',
                'points' => [
                    ['simulated' => 20],
                    ['simulated' => 30],
                ]
            ],
            [
                'group' => 'Concentración de Agente Anestésico',
                'unit' => '%',
                'tolerance_label' => '±0.5%',
                'points' => [
                    ['simulated' => 1.0],
                    ['simulated' => 2.0],
                    ['simulated' => 3.0],
                ]
            ],
        ],

        'electrical_safety' => [
            ['param' => 'Tensión de red',      'expected' => '220V',     'tolerance' => '±10%'],
            ['param' => 'Resistencia a tierra', 'expected' => '≤ 0.2 Ω', 'tolerance' => 'Normativo'],
            ['param' => 'Corriente de fuga',    'expected' => '≤ 0.5 mA', 'tolerance' => 'Normativo'],
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // MONITOR CARDIOFETAL (Formato V1+ - Mixto)
    // ═══════════════════════════════════════════════════════════════
    'monitor_cardiofetal' => [
        'label' => 'Monitor Cardiofetal',
        'icon'  => 'pregnant_woman',
        'version' => 'V1',

        'qualitative' => [
            'Verificar encendido del equipo',
            'Limpieza externa de carcasa y pantalla',
            'Estado de cable de poder y enchufe',
            'Estado de transductores (TOCO y ultrasonido)',
            'Estado de cinturones de sujeción',
            'Funcionamiento de pantalla y display',
            'Alarmas sonoras y visuales operativas',
            'Verificar impresora/registrador de papel',
            'Estado de batería y autonomía',
            'Pruebas funcionales generales',
        ],

        'quantitative' => [],

        'electrical_safety' => [
            ['param' => 'Tensión de red',      'expected' => '220V',     'tolerance' => '±10%'],
            ['param' => 'Resistencia a tierra', 'expected' => '≤ 0.2 Ω', 'tolerance' => 'Normativo'],
            ['param' => 'Corriente de fuga',    'expected' => '≤ 0.5 mA', 'tolerance' => 'Normativo'],
        ],
    ],

    // ═══════════════════════════════════════════════════════════════
    // MONITOR DE GASTO CARDÍACO (Formato V2)
    // ═══════════════════════════════════════════════════════════════
    'monitor_gasto_cardiaco' => [
        'label' => 'Monitor de Gasto Cardíaco',
        'icon'  => 'cardiology',
        'version' => 'V2',

        'qualitative' => [
            'Verificar encendido del equipo',
            'Limpieza externa de carcasa y pantalla',
            'Estado de cable de poder y enchufe',
            'Estado de cables y sensores de presión invasiva',
            'Estado de transductores de termodilución',
            'Funcionamiento de pantalla y display',
            'Alarmas sonoras y visuales operativas',
            'Estado de batería (si aplica)',
            'Autodiagnóstico del equipo',
        ],

        'quantitative' => [
            [
                'group' => 'Presión Invasiva',
                'unit' => 'mmHg',
                'tolerance_label' => '±3 mmHg',
                'points' => [
                    ['simulated' => 0],
                    ['simulated' => 100],
                    ['simulated' => 200],
                    ['simulated' => 300],
                ]
            ],
        ],

        'electrical_safety' => [
            ['param' => 'Tensión de red',      'expected' => '220V',     'tolerance' => '±10%'],
            ['param' => 'Resistencia a tierra', 'expected' => '≤ 0.2 Ω', 'tolerance' => 'Normativo'],
            ['param' => 'Corriente de fuga',    'expected' => '≤ 0.5 mA', 'tolerance' => 'Normativo'],
        ],
    ],
];

/**
 * Obtener una plantilla por su key.
 * @param string $key
 * @return array|null
 */
function getChecklistTemplate(string $key): ?array
{
    global $checklist_templates;
    return $checklist_templates[$key] ?? null;
}

/**
 * Listar todas las plantillas disponibles (para el <select>).
 * @return array [key => label]
 */
function listChecklistTemplates(): array
{
    global $checklist_templates;
    $list = [];
    foreach ($checklist_templates as $key => $tpl) {
        $list[$key] = $tpl['label'] . ' (' . $tpl['version'] . ')';
    }
    return $list;
}
