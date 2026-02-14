<?php

/**
 * Backend/data/mock_data.php
 * ─────────────────────────────────────────────────────
 * FUENTE ÚNICA DE DATOS MOCK
 * En producción, este archivo se reemplaza por consultas a BD.
 * Las páginas NUNCA acceden a estos arrays directamente;
 * siempre lo hacen a través de los Providers.
 * ─────────────────────────────────────────────────────
 */

// ═══════════════════════════════════════════════════════
// USUARIOS / ROLES
// ═══════════════════════════════════════════════════════
$MOCK_USERS = [
    'auditor' => [
        'id' => 1,
        'name' => 'Lic. Auditor',
        'email' => 'auditor@biocmms.com',
        'password_hash' => '$2y$10$placeholder_hash', // Inyectado para auditoría
        'role' => 'Auditor',
        'avatar_url' => 'https://i.pravatar.cc/150?u=auditor',
        'desc' => 'Solo Observación'
    ],
    'chief' => [
        'id' => 2,
        'name' => 'Ing. Roberto Jefe',
        'email' => 'jefe@biocmms.com',
        'password_hash' => '$2y$10$UDu6jaXTX58LN8QjD.Q1beWvAqf9eEqgpzGJrl8jN3hGPfEuMhEQq',
        'role' => 'Ingeniero',
        'avatar_url' => 'https://i.pravatar.cc/150?u=chief',
        'desc' => 'Gestión y Reportes'
    ],
    'engineer' => [
        'id' => 3,
        'name' => 'Ing. Laura',
        'email' => 'ing@biocmms.com',
        'password_hash' => '$2y$10$UDu6jaXTX58LN8QjD.Q1beWvAqf9eEqgpzGJrl8jN3hGPfEuMhEQq',
        'role' => 'Ingeniero',
        'avatar_url' => 'https://i.pravatar.cc/150?u=eng',
        'desc' => 'Supervisión Técnica'
    ],
    'tech' => [
        'id' => 4,
        'name' => 'Téc. Mario',
        'email' => 'tec@biocmms.com',
        'password_hash' => '$2y$10$placeholder_hash',
        'role' => 'Técnico',
        'avatar_url' => 'https://i.pravatar.cc/150?u=tech',
        'desc' => 'Ejecución de OTs'
    ],
    'user' => [
        'id' => 5,
        'name' => 'Usuario Clínico',
        'email' => 'user@biocmms.com',
        'password_hash' => '$2y$10$placeholder_hash',
        'role' => 'Usuario',
        'avatar_url' => 'https://i.pravatar.cc/150?u=user',
        'desc' => 'Solicitud de Servicios'
    ],
    'demo' => [
        'id' => 6,
        'name' => 'Usuario Demo',
        'email' => 'demo@biocmms.com',
        'password_hash' => '$2y$10$placeholder_hash',
        'role' => 'Usuario', // Comparte privilegios de 'Usuario'
        'avatar_url' => 'https://i.pravatar.cc/150?u=demo',
        'desc' => 'Cuenta de Demostración'
    ],
];

// ═══════════════════════════════════════════════════════
// ACTIVOS BIOMÉDICOS (fusión inventory + dashboard)
// ═══════════════════════════════════════════════════════
$MOCK_ASSETS = [
    [
        'id' => 'PB-840-00122',
        'serial_number' => 'SN-992031-B',
        'name' => 'Ventilador Mecánico',
        'brand' => 'Puritan Bennett',
        'model' => '840',
        'location' => 'UCI Adultos - Box 04',
        'sub_location' => 'Cama 4',
        'vendor' => 'Draeger Medical',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'OPERATIVE',
        'riesgo_ge' => 'Life Support',
        'codigo_umdns' => '17-429',
        'fecha_instalacion' => '2020-05-10',
        'purchased_year' => 2020,
        'acquisition_cost' => 45000,
        'vencimiento_vida_util' => '2030-05-10',
        'total_useful_life' => 10,
        'useful_life_pct' => 75,
        'years_remaining' => 4,
        'warranty_until' => '2026-12-15',
        'warranty_expiration' => '2026-12-15',
        'last_maintenance' => '2026-01-20',
        'next_maintenance' => '2026-07-20',
        'under_maintenance_plan' => true,
        'en_uso' => true,
        'recalls' => [],
        'funcion_ge' => 10,
        'riesgo_ge_score' => 5,
        'mantenimiento_ge' => 4,
        'image_url' => 'https://jbh.com.pk/wp-content/uploads/2021/04/Puritan-Bennett-840.jpg',
        'family' => 'Ventilación',
        'family_icon' => 'air',
        'family_color' => '#0ea5e9',
        'hours_used' => 18450,
        'total_failures' => 12,
        'downtime_hours' => 156
    ],
    [
        'id' => 'AL-500-00441',
        'serial_number' => 'SN-882211-X',
        'name' => 'Bomba de Infusión',
        'brand' => 'Alaris',
        'model' => 'GH Plus',
        'location' => 'Urgencias - Sala 01',
        'sub_location' => 'Box 1',
        'vendor' => 'Becton Dickinson',
        'ownership' => 'Comodato',
        'criticality' => 'RELEVANT',
        'status' => 'MAINTENANCE',
        'riesgo_ge' => 'High Risk',
        'codigo_umdns' => '13-215',
        'fecha_instalacion' => '2021-02-15',
        'purchased_year' => 2021,
        'acquisition_cost' => 8500,
        'vencimiento_vida_util' => '2031-02-15',
        'total_useful_life' => 10,
        'useful_life_pct' => 85,
        'years_remaining' => 7,
        'warranty_until' => '2026-02-15',
        'warranty_expiration' => '2026-02-15',
        'last_maintenance' => '2026-02-05',
        'next_maintenance' => '2026-08-05',
        'under_maintenance_plan' => true,
        'en_uso' => false,
        'recalls' => [
            ['id' => 'AV-2024-01', 'agency' => 'ISP', 'priority' => 'Alta', 'description' => 'Falla en software de goteo']
        ],
        'funcion_ge' => 8,
        'riesgo_ge_score' => 4,
        'mantenimiento_ge' => 3,
        'image_url' => 'https://www.bd.com/content/dam/bd/us/en-us/offerings/capabilities/infusion/infusion-pumps/alaris-system/alaris-gh-plus-large.jpg',
        'family' => 'Infusión',
        'family_icon' => 'water_drop',
        'family_color' => '#8b5cf6',
        'hours_used' => 28700,
        'total_failures' => 22,
        'downtime_hours' => 187
    ],
    [
        'id' => 'MM-X3-00922',
        'serial_number' => 'SN-773344-Y',
        'name' => 'Monitor Multiparamétrico',
        'brand' => 'Mindray',
        'model' => 'BeneVision X3',
        'location' => 'Pabellón 03',
        'sub_location' => 'Mesa Anestesia',
        'vendor' => 'Mindray Chile',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'OPERATIVE_WITH_OBS',
        'riesgo_ge' => 'High Risk',
        'codigo_umdns' => '12-630',
        'fecha_instalacion' => '2022-08-20',
        'purchased_year' => 2022,
        'acquisition_cost' => 12000,
        'vencimiento_vida_util' => '2032-08-20',
        'total_useful_life' => 10,
        'useful_life_pct' => 90,
        'years_remaining' => 8,
        'warranty_until' => '2025-08-20',
        'warranty_expiration' => '2025-08-20',
        'last_maintenance' => '2026-01-15',
        'next_maintenance' => '2026-07-15',
        'under_maintenance_plan' => true,
        'en_uso' => true,
        'recalls' => [],
        'funcion_ge' => 9,
        'riesgo_ge_score' => 4,
        'mantenimiento_ge' => 3,
        'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuTnaOyhDigSEJ_ZeiC5vlm5VEN4TICv5-Uk95l4oLSa4UlVPj36whZ9nAbcywMbM2UJsZSQQ-ZzoERBXbiyPkVhNiEq5VFx609iR72MDSNS9M15h6Xw71p6PKvEmyUBH3-ICY4Q8NsO7WSOPEdLVGp347QdiBje3dZ088nhjDZqFJQV6q_PMoECxkS3wI4nNpI5j-nZ5BgVzOhP7Uu3mulfvz2PZpBfaq6vbjJh9zaqLzkw9c3G4TgLFD4g_QY0tXEmNI-8-100s'
    ],
    [
        'id' => 'DF-CU-00210',
        'serial_number' => 'SN-554433-Z',
        'name' => 'Desfibrilador',
        'brand' => 'Zoll',
        'model' => 'R Series',
        'location' => 'Piso 3 - Torre A',
        'sub_location' => 'Carro de Paro',
        'vendor' => 'Medtronic',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'NO_OPERATIVE',
        'riesgo_ge' => 'Life Support',
        'codigo_umdns' => '11-129',
        'fecha_instalacion' => '2020-03-30',
        'purchased_year' => 2020,
        'acquisition_cost' => 15000,
        'vencimiento_vida_util' => '2030-03-30',
        'total_useful_life' => 10,
        'useful_life_pct' => 40,
        'years_remaining' => 4,
        'warranty_until' => '2025-03-30',
        'warranty_expiration' => '2025-03-30',
        'last_maintenance' => '2023-12-01',
        'next_maintenance' => '2024-06-01',
        'under_maintenance_plan' => true,
        'en_uso' => false,
        'recalls' => [],
        'funcion_ge' => 10,
        'riesgo_ge_score' => 5,
        'mantenimiento_ge' => 3,
        'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuTnaOyhDigSEJ_ZeiC5vlm5VEN4TICv5-Uk95l4oLSa4UlVPj36whZ9nAbcywMbM2UJsZSQQ-ZzoERBXbiyPkVhNiEq5VFx609iR72MDSNS9M15h6Xw71p6PKvEmyUBH3-ICY4Q8NsO7WSOPEdLVGp347QdiBje3dZ088nhjDZqFJQV6q_PMoECxkS3wI4nNpI5j-nZ5BgVzOhP7Uu3mulfvz2PZpBfaq6vbjJh9zaqLzkw9c3G4TgLFD4g_QY0tXEmNI-8-100s'
    ],
    [
        'id' => 'ECG-2024-001',
        'serial_number' => 'SN-ECG-9988',
        'name' => 'Electrocardiógrafo',
        'brand' => 'Philips',
        'model' => 'PageWriter TC70',
        'location' => 'Cardiología',
        'sub_location' => 'Consulta 2',
        'vendor' => 'Philips Medical',
        'ownership' => 'Propio',
        'criticality' => 'RELEVANT',
        'status' => 'OPERATIVE_WITH_OBS',
        'observations' => 'Pantalla con leve parpadeo ocasional.',
        'purchased_year' => 2023,
        'acquisition_cost' => 5500,
        'total_useful_life' => 8,
        'useful_life_pct' => 60,
        'years_remaining' => 4,
        'warranty_until' => '2027-01-01',
        'warranty_expiration' => '2027-01-01',
        'under_maintenance_plan' => true,
        'funcion_ge' => 5,
        'riesgo_ge_score' => 2,
        'mantenimiento_ge' => 3,
        'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuArjc0RB-oPKqM3OEkdyKO5qx0pqx3tnnDtgQIHmBy0OPhWndzJRDGmHAcSYe5KMj0OjejmEqQHwFvzj3j49_uv32qOSGRi45_B0VwA769XNTkLdWndUI_FM0j2hmcjFtaudmO_Y7PVvrQYFCicy5r0hOsgef2wmHu8tH4m42rvSfGyQ0ijsJnKLkakgcGce8Iu_LCpMrDOwXVMHGj1pEW6dn2BZOSGHAPH7GUrrvLeB-Sphiq9IgFn8INtJB9-UCwIwvp96rzTMKE'
    ]
];

// ═══════════════════════════════════════════════════════
// ÓRDENES DE TRABAJO
// ═══════════════════════════════════════════════════════
$MOCK_WORK_ORDERS = [
    [
        'id' => 'OT-2026-4584',
        'asset_id' => 'MM-X3-00922',
        'asset' => 'Monitor Multiparámetro',
        'type' => 'Calibración',
        'status' => 'Pendiente',
        'tech' => 'Ana Muñoz',
        'date' => '2026-02-11',
        'priority' => 'Baja',
        'checklist_template' => null
    ],
    [
        'id' => 'OT-2026-4583',
        'asset_id' => 'AL-500-00441',
        'asset' => 'Bomba de Infusión',
        'type' => 'Correctiva',
        'status' => 'En Proceso',
        'tech' => 'Pablo Rojas',
        'date' => '2026-02-10',
        'priority' => 'Media',
        'checklist_template' => null
    ],
    [
        'id' => 'OT-2025-3210',
        'asset_id' => 'DF-CU-00210',
        'asset' => 'Rayos X Portátil',
        'type' => 'Preventiva',
        'status' => 'Terminada',
        'tech' => 'Ana Muñoz',
        'date' => '2025-11-20',
        'priority' => 'Baja',
        'checklist_template' => null
    ],
    [
        'id' => 'OT-2024-1105',
        'asset_id' => 'PB-840-00122',
        'asset' => 'Ventilador Mecánico',
        'type' => 'Preventiva',
        'status' => 'Terminada',
        'tech' => 'Mario Gómez',
        'date' => '2024-08-15',
        'priority' => 'Alta',
        'checklist_template' => 'ventilador_mecanico'
    ]
];

// ═══════════════════════════════════════════════════════
// OT CORRECTIVAS (para cálculos de confiabilidad)
// ═══════════════════════════════════════════════════════
$MOCK_OT_CORRECTIVAS = [
    ['asset_id' => 'PB-840-00122', 'date' => '2025-06-15', 'duration_hours' => 2.5],
    ['asset_id' => 'PB-840-00122', 'date' => '2026-01-20', 'duration_hours' => 3.0],
    ['asset_id' => 'AL-500-00441', 'date' => '2025-08-10', 'duration_hours' => 1.5],
    ['asset_id' => 'AL-500-00441', 'date' => '2026-02-05', 'duration_hours' => 2.0],
    ['asset_id' => 'DF-CU-00210', 'date' => '2025-05-20', 'duration_hours' => 4.0],
    ['asset_id' => 'DF-CU-00210', 'date' => '2026-01-15', 'duration_hours' => 3.5],
    ['asset_id' => 'ECG-2024-001', 'date' => '2025-09-10', 'duration_hours' => 1.0],
    ['asset_id' => 'ECG-2024-001', 'date' => '2026-02-01', 'duration_hours' => 1.5],
];

// ═══════════════════════════════════════════════════════
// TÉCNICOS (ranking + carga de trabajo)
// ═══════════════════════════════════════════════════════
$MOCK_TECHNICIANS = [
    [
        'name' => 'Carlos Rodriguez',
        'role' => 'Ing. Clínico Sr.',
        'initial' => 'CR',
        'ot_terminadas' => 22,
        'active' => 8,
        'completed' => 12,
        'capacity' => 85
    ],
    [
        'name' => 'Ana Martínez',
        'role' => 'Técnico Biomédico',
        'initial' => 'AM',
        'ot_terminadas' => 15,
        'active' => 3,
        'completed' => 15,
        'capacity' => 45
    ],
    [
        'name' => 'Roberto Paiva',
        'role' => 'Ing. Electrónico',
        'initial' => 'RP',
        'ot_terminadas' => 5,
        'active' => 11,
        'completed' => 5,
        'capacity' => 95
    ],
    [
        'name' => 'Elena Solís',
        'role' => 'Técnico Especialista',
        'initial' => 'ES',
        'ot_terminadas' => 10,
        'active' => 5,
        'completed' => 10,
        'capacity' => 60
    ],
];

// ═══════════════════════════════════════════════════════
// EVENTOS RECIENTES
// ═══════════════════════════════════════════════════════
$MOCK_EVENTS = [
    [
        'id' => 'OT-2024-001',
        'title' => 'OT-2024-001 Finalizada',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Preventivo | Téc. Mario Gómez',
        'time' => 'Hace 15 días',
        'type' => 'success',
        'color_class' => 'emerald-500'
    ],
    [
        'id' => 'OT-2024-015',
        'title' => 'OT-2024-015 En Proceso',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Correctivo | Téc. Pablo Rojas',
        'time' => 'Hace 5 días',
        'type' => 'warning',
        'color_class' => 'amber-500'
    ],
    [
        'id' => 'OT-2023-089',
        'title' => 'OT-2023-089 Finalizada',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Calibración | Téc. Ana Muñoz',
        'time' => 'Hace 2 meses',
        'type' => 'success',
        'color_class' => 'emerald-500'
    ],
    [
        'id' => 'OT-2023-045',
        'title' => 'OT-2023-045 Finalizada',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Preventivo | Téc. Mario Gómez',
        'time' => 'Hace 5 meses',
        'type' => 'success',
        'color_class' => 'emerald-500'
    ]
];
