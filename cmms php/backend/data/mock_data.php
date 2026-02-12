<?php

/**
 * backend/data/mock_data.php
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
        'role' => 'Auditor',
        'avatar' => 'https://i.pravatar.cc/150?u=auditor',
        'desc' => 'Solo Observación'
    ],
    'chief' => [
        'id' => 2,
        'name' => 'Ing. Roberto Jefe',
        'email' => 'jefe@biocmms.com',
        'role' => 'Ingeniero',
        'avatar' => 'https://i.pravatar.cc/150?u=chief',
        'desc' => 'Gestión y Reportes'
    ],
    'engineer' => [
        'id' => 3,
        'name' => 'Ing. Laura',
        'email' => 'ing@biocmms.com',
        'role' => 'Ingeniero',
        'avatar' => 'https://i.pravatar.cc/150?u=eng',
        'desc' => 'Supervisión Técnica'
    ],
    'tech' => [
        'id' => 4,
        'name' => 'Téc. Mario',
        'email' => 'tec@biocmms.com',
        'role' => 'Técnico',
        'avatar' => 'https://i.pravatar.cc/150?u=tech',
        'desc' => 'Ejecución de OTs'
    ],
];

// ═══════════════════════════════════════════════════════
// ACTIVOS BIOMÉDICOS (fusión inventory + dashboard)
// ═══════════════════════════════════════════════════════
$MOCK_ASSETS = [
    [
        'id' => 'PB-840-00122',
        'serialNumber' => 'SN-992031-B',
        'name' => 'Ventilador Mecánico',
        'brand' => 'Puritan Bennett',
        'model' => '840',
        'location' => 'UCI Adultos - Box 04',
        'subLocation' => 'Cama 4',
        'vendor' => 'Draeger Medical',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'OPERATIVE',
        'riesgoGE' => 'Life Support',
        'codigoUMDNS' => '17-429',
        'fechaInstalacion' => '2020-05-10',
        'purchasedYear' => 2020,
        'acquisitionCost' => 45000,
        'vencimientoVidaUtil' => '2030-05-10',
        'totalUsefulLife' => 10,
        'usefulLife' => 75,
        'yearsRemaining' => 4,
        'warrantyUntil' => '2026-12-15',
        'warrantyExpiration' => '2026-12-15',
        'lastMaintenance' => '2026-01-20',
        'nextMaintenance' => '2026-07-20',
        'underMaintenancePlan' => true,
        'enUso' => true,
        'recalls' => [],
        'funcion' => 10,
        'riesgo' => 5,
        'mantenimiento' => 4,
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuArjc0RB-oPKqM3OEkdyKO5qx0pqx3tnnDtgQIHmBy0OPhWndzJRDGmHAcSYe5KMj0OjejmEqQHwFvzj3j49_uv32qOSGRi45_B0VwA769XNTkLdWndUI_FM0j2hmcjFtaudmO_Y7PVvrQYFCicy5r0hOsgef2wmHu8tH4m42rvSfGyQ0ijsJnKLkakgcGce8Iu_LCpMrDOwXVMHGj1pEW6dn2BZOSGHAPH7GUrrvLeB-Sphiq9IgFn8INtJB9-UCwIwvp96rzTMKE'
    ],
    [
        'id' => 'AL-500-00441',
        'serialNumber' => 'SN-882211-X',
        'name' => 'Bomba de Infusión',
        'brand' => 'Alaris',
        'model' => 'GH Plus',
        'location' => 'Urgencias - Sala 01',
        'subLocation' => 'Box 1',
        'vendor' => 'Becton Dickinson',
        'ownership' => 'Comodato',
        'criticality' => 'RELEVANT',
        'status' => 'MAINTENANCE',
        'riesgoGE' => 'High Risk',
        'codigoUMDNS' => '13-215',
        'fechaInstalacion' => '2021-02-15',
        'purchasedYear' => 2021,
        'acquisitionCost' => 8500,
        'vencimientoVidaUtil' => '2031-02-15',
        'totalUsefulLife' => 10,
        'usefulLife' => 85,
        'yearsRemaining' => 7,
        'warrantyUntil' => '2026-02-15',
        'warrantyExpiration' => '2026-02-15',
        'lastMaintenance' => '2026-02-05',
        'nextMaintenance' => '2026-08-05',
        'underMaintenancePlan' => true,
        'enUso' => false,
        'recalls' => [
            ['id' => 'AV-2024-01', 'agency' => 'ISP', 'priority' => 'Alta', 'description' => 'Falla en software de goteo']
        ],
        'funcion' => 8,
        'riesgo' => 4,
        'mantenimiento' => 3,
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCqTY2B_WeZ_djLO8S6euAFUJ1WzmzbMSUGYduj9g59ioholcDXR6um3kCeWy8poItGYaqCnDdViYv97BGdYNx8ucDtglCYRM8Bn0aWVnXsngWQharhj4mTBqGiH58Vdm_g9T9eTm72oulsEa28E5vrELOutWmLu_-yqJAEsNzS1XHxFT7w4-ZP_s112NWvyWajE_qYtSotNUDX_RmK9BRoKh5E1SLr0eiG4u1f8btf5ya4Q9ay-aLE9NzkgsKJHPSZonQn9m3BQ4o'
    ],
    [
        'id' => 'MM-X3-00922',
        'serialNumber' => 'SN-773344-Y',
        'name' => 'Monitor Multiparamétrico',
        'brand' => 'Mindray',
        'model' => 'BeneVision X3',
        'location' => 'Pabellón 03',
        'subLocation' => 'Mesa Anestesia',
        'vendor' => 'Mindray Chile',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'OPERATIVE_WITH_OBS',
        'riesgoGE' => 'High Risk',
        'codigoUMDNS' => '12-630',
        'fechaInstalacion' => '2022-08-20',
        'purchasedYear' => 2022,
        'acquisitionCost' => 12000,
        'vencimientoVidaUtil' => '2032-08-20',
        'totalUsefulLife' => 10,
        'usefulLife' => 90,
        'yearsRemaining' => 8,
        'warrantyUntil' => '2025-08-20',
        'warrantyExpiration' => '2025-08-20',
        'lastMaintenance' => '2026-01-15',
        'nextMaintenance' => '2026-07-15',
        'underMaintenancePlan' => true,
        'enUso' => true,
        'recalls' => [],
        'funcion' => 9,
        'riesgo' => 4,
        'mantenimiento' => 3,
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuTnaOyhDigSEJ_ZeiC5vlm5VEN4TICv5-Uk95l4oLSa4UlVPj36whZ9nAbcywMbM2UJsZSQQ-ZzoERBXbiyPkVhNiEq5VFx609iR72MDSNS9M15h6Xw71p6PKvEmyUBH3-ICY4Q8NsO7WSOPEdLVGp347QdiBje3dZ088nhjDZqFJQV6q_PMoECxkS3wI4nNpI5j-nZ5BgVzOhP7Uu3mulfvz2PZpBfaq6vbjJh9zaqLzkw9c3G4TgLFD4g_QY0tXEmNI-8-100s'
    ],
    [
        'id' => 'DF-CU-00210',
        'serialNumber' => 'SN-554433-Z',
        'name' => 'Desfibrilador',
        'brand' => 'Zoll',
        'model' => 'R Series',
        'location' => 'Piso 3 - Torre A',
        'subLocation' => 'Carro de Paro',
        'vendor' => 'Medtronic',
        'ownership' => 'Propio',
        'criticality' => 'CRITICAL',
        'status' => 'NO_OPERATIVE',
        'riesgoGE' => 'Life Support',
        'codigoUMDNS' => '11-129',
        'fechaInstalacion' => '2020-03-30',
        'purchasedYear' => 2020,
        'acquisitionCost' => 15000,
        'vencimientoVidaUtil' => '2030-03-30',
        'totalUsefulLife' => 10,
        'usefulLife' => 40,
        'yearsRemaining' => 4,
        'warrantyUntil' => '2025-03-30',
        'warrantyExpiration' => '2025-03-30',
        'lastMaintenance' => '2023-12-01',
        'nextMaintenance' => '2024-06-01',
        'underMaintenancePlan' => true,
        'enUso' => false,
        'recalls' => [],
        'funcion' => 10,
        'riesgo' => 5,
        'mantenimiento' => 3,
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCuTnaOyhDigSEJ_ZeiC5vlm5VEN4TICv5-Uk95l4oLSa4UlVPj36whZ9nAbcywMbM2UJsZSQQ-ZzoERBXbiyPkVhNiEq5VFx609iR72MDSNS9M15h6Xw71p6PKvEmyUBH3-ICY4Q8NsO7WSOPEdLVGp347QdiBje3dZ088nhjDZqFJQV6q_PMoECxkS3wI4nNpI5j-nZ5BgVzOhP7Uu3mulfvz2PZpBfaq6vbjJh9zaqLzkw9c3G4TgLFD4g_QY0tXEmNI-8-100s'
    ],
    [
        'id' => 'ECG-2024-001',
        'serialNumber' => 'SN-ECG-9988',
        'name' => 'Electrocardiógrafo',
        'brand' => 'Philips',
        'model' => 'PageWriter TC70',
        'location' => 'Cardiología',
        'subLocation' => 'Consulta 2',
        'vendor' => 'Philips Medical',
        'ownership' => 'Propio',
        'criticality' => 'RELEVANT',
        'status' => 'OPERATIVE_WITH_OBS',
        'observations' => 'Pantalla con leve parpadeo ocasional.',
        'purchasedYear' => 2023,
        'acquisitionCost' => 5500,
        'totalUsefulLife' => 8,
        'usefulLife' => 60,
        'yearsRemaining' => 4,
        'warrantyUntil' => '2027-01-01',
        'warrantyExpiration' => '2027-01-01',
        'underMaintenancePlan' => true,
        'funcion' => 5,
        'riesgo' => 2,
        'mantenimiento' => 3,
        'imageUrl' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuArjc0RB-oPKqM3OEkdyKO5qx0pqx3tnnDtgQIHmBy0OPhWndzJRDGmHAcSYe5KMj0OjejmEqQHwFvzj3j49_uv32qOSGRi45_B0VwA769XNTkLdWndUI_FM0j2hmcjFtaudmO_Y7PVvrQYFCicy5r0hOsgef2wmHu8tH4m42rvSfGyQ0ijsJnKLkakgcGce8Iu_LCpMrDOwXVMHGj1pEW6dn2BZOSGHAPH7GUrrvLeB-Sphiq9IgFn8INtJB9-UCwIwvp96rzTMKE'
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
    ['equipo_id' => 'PB-840-00122', 'fecha' => '2025-06-15', 'duracion_horas' => 2.5],
    ['equipo_id' => 'PB-840-00122', 'fecha' => '2026-01-20', 'duracion_horas' => 3.0],
    ['equipo_id' => 'AL-500-00441', 'fecha' => '2025-08-10', 'duracion_horas' => 1.5],
    ['equipo_id' => 'AL-500-00441', 'fecha' => '2026-02-05', 'duracion_horas' => 2.0],
    ['equipo_id' => 'DF-CU-00210', 'fecha' => '2025-05-20', 'duracion_horas' => 4.0],
    ['equipo_id' => 'DF-CU-00210', 'fecha' => '2026-01-15', 'duracion_horas' => 3.5],
    ['equipo_id' => 'ECG-2024-001', 'fecha' => '2025-09-10', 'duracion_horas' => 1.0],
    ['equipo_id' => 'ECG-2024-001', 'fecha' => '2026-02-01', 'duracion_horas' => 1.5],
];

// ═══════════════════════════════════════════════════════
// TÉCNICOS (ranking + carga de trabajo)
// ═══════════════════════════════════════════════════════
$MOCK_TECHNICIANS = [
    [
        'name' => 'Carlos Rodriguez',
        'role' => 'Ing. Clínico Sr.',
        'initial' => 'CR',
        'otTerminadas' => 22,
        'active' => 8,
        'completed' => 12,
        'capacity' => 85
    ],
    [
        'name' => 'Ana Martínez',
        'role' => 'Técnico Biomédico',
        'initial' => 'AM',
        'otTerminadas' => 15,
        'active' => 3,
        'completed' => 15,
        'capacity' => 45
    ],
    [
        'name' => 'Roberto Paiva',
        'role' => 'Ing. Electrónico',
        'initial' => 'RP',
        'otTerminadas' => 5,
        'active' => 11,
        'completed' => 5,
        'capacity' => 95
    ],
    [
        'name' => 'Elena Solís',
        'role' => 'Técnico Especialista',
        'initial' => 'ES',
        'otTerminadas' => 10,
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
        'colorClass' => 'emerald-500'
    ],
    [
        'id' => 'OT-2024-015',
        'title' => 'OT-2024-015 En Proceso',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Correctivo | Téc. Pablo Rojas',
        'time' => 'Hace 5 días',
        'type' => 'warning',
        'colorClass' => 'amber-500'
    ],
    [
        'id' => 'OT-2023-089',
        'title' => 'OT-2023-089 Finalizada',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Calibración | Téc. Ana Muñoz',
        'time' => 'Hace 2 meses',
        'type' => 'success',
        'colorClass' => 'emerald-500'
    ],
    [
        'id' => 'OT-2023-045',
        'title' => 'OT-2023-045 Finalizada',
        'subtitle' => 'Ventilador Mecánico PB-840-00122 - Preventivo | Téc. Mario Gómez',
        'time' => 'Hace 5 meses',
        'type' => 'success',
        'colorClass' => 'emerald-500'
    ]
];
