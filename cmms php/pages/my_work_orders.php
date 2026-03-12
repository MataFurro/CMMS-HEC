<?php

/**
 * pages/my_work_orders.php
 * Vista de tareas activas filtrada obligatoriamente por técnico para administradores.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

use Backend\Repositories\WorkOrderRepository;
use Backend\Repositories\UserRepository;

$repo = new WorkOrderRepository();
$userRepo = new UserRepository();

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? '';

if (!$userId || !in_array($userRole, [ROLE_TECHNICIAN, ROLE_CHIEF_ENGINEER, ROLE_AUDITOR, ROLE_ENGINEER])) {
    echo "<div class='p-10 text-center text-slate-400'>Acceso no autorizado.</div>";
    exit;
}

$isManager = in_array($userRole, [ROLE_CHIEF_ENGINEER, ROLE_AUDITOR, ROLE_ENGINEER]);
$selectedTechId = $_GET['tech_id'] ?? null;

// Obtener técnicos para el filtro de admin
$technicians = $isManager ? $userRepo->findAllTechnicians() : [];

// Si es manager y no hay tech_id, seleccionamos el primero por defecto
if ($isManager && !$selectedTechId && !empty($technicians)) {
    $selectedTechId = $technicians[0]['id'];
}

// Obtener las órdenes
if ($isManager) {
    // Si no hay técnicos, $ordersGen será vacío
    $ordersGen = $selectedTechId ? $repo->findByTechnician((int)$selectedTechId) : [];
} else {
    $ordersGen = $repo->findByTechnician($userId);
}

// Agrupar órdenes (Ocultamos Terminadas/Canceladas)
$enCurso = [];
$enEspera = [];

foreach ($ordersGen as $ot) {
    $statusValue = (isset($ot->status) && $ot->status instanceof \Backend\Models\WorkOrderStatus)
        ? $ot->status->value
        : (isset($ot->status) ? (string)$ot->status : 'En Curso');

    if ($statusValue === 'En Espera') {
        $enEspera[] = $ot;
    } elseif ($statusValue !== 'Terminada' && $statusValue !== 'Cancelada') {
        $enCurso[] = $ot;
    }
}

/**
 * Función auxiliar para renderizar una tarjeta de OT
 */
function renderTaskCard($ot, $isManager)
{
    $id = $ot->id ?? 'N/A';
    $statusValue = ($ot->status instanceof \Backend\Models\WorkOrderStatus) ? $ot->status->value : (string)($ot->status ?? 'En Curso');
    $assetName = $ot->assetName ?? $ot->asset_name ?? 'Activo Desconocido';
    $location = $ot->location ?? 'N/A';
    $techName = $ot->assignedTechName ?? $ot->assigned_tech_name ?? 'Sin Asignar';
    $createdDate = $ot->createdDate ?? null;
    $dateStr = ($createdDate instanceof \DateTime) ? $createdDate->format('d/m/Y') : 'N/A';

    $statusClass = getStatusClass($statusValue);
    $techLabel = $isManager ? "<p class='text-[10px] text-medical-blue mb-2 font-bold uppercase tracking-wider'><i class='fas fa-user-hard-hat mr-1'></i> " . htmlspecialchars($techName) . "</p>" : "";

    return "
        <div class='bg-[var(--panel-dark)] border border-[var(--border-color)] rounded-2xl p-5 hover:border-medical-blue/50 transition-all group relative overflow-hidden'>
            <div class='absolute top-0 right-0 w-16 h-16 bg-medical-blue/5 blur-3xl rounded-full -mr-8 -mt-8 group-hover:bg-medical-blue/10 transition-colors'></div>
            
            <div class='flex justify-between items-start mb-3'>
                <span class='text-[10px] font-mono text-[var(--text-muted)] bg-[var(--medical-dark)] px-2 py-0.5 rounded border border-[var(--border-color)]'>#{$id}</span>
                <span class='px-2 py-1 rounded text-[9px] font-black uppercase shadow-sm border {$statusClass}'>
                    {$statusValue}
                </span>
            </div>
            
            <h3 class='text-[var(--text-main)] font-bold text-sm mb-1 line-clamp-2 min-h-[2.5rem] leading-snug'>{$assetName}</h3>
            {$techLabel}
            
            <div class='grid grid-cols-2 gap-2 mt-4 text-[10px] text-[var(--text-muted)]'>
                <div class='flex items-center gap-1.5'>
                    <i class='fas fa-map-marker-alt text-medical-blue/70'></i>
                    <span class='truncate'>{$location}</span>
                </div>
                <div class='flex items-center gap-1.5 justify-end'>
                    <i class='far fa-calendar-alt text-medical-blue/70'></i>
                    <span>{$dateStr}</span>
                </div>
            </div>

            <div class='mt-5 flex justify-end'>
                <a href='?page=work_order_execution&id={$id}' class='w-full text-center py-2 bg-[var(--medical-dark)] hover:bg-medical-blue hover:text-white text-[var(--text-main)] text-[11px] font-black rounded-xl transition-all border border-[var(--border-color)] hover:border-medical-blue uppercase tracking-widest'>
                    Ejecutar OT <i class='fas fa-arrow-right ml-1'></i>
                </a>
            </div>
        </div>
    ";
}
?>

<div class="px-4 py-8 md:p-10 space-y-10 max-w-[1800px] mx-auto animate-fade-in pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black text-[var(--text-main)] flex items-center gap-4 tracking-tighter">
                <div class="w-12 h-12 bg-medical-blue/10 rounded-2xl flex items-center justify-center text-medical-blue border border-medical-blue/20">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <?= $isManager ? 'Gestión de Carga Técnica' : 'Mi Agenda del Día' ?>
            </h1>
            <p class="text-sm text-[var(--text-muted)] mt-2 font-medium">
                <?= $isManager ? 'Selecciona un especialista para monitorear sus tareas activas.' : 'Enfócate en tus tareas en curso para hoy.' ?>
            </p>
        </div>
    </div>

    <?php if ($isManager): ?>
        <!-- Selector de Técnicos (Diseño Tarjetas Rectangulares) -->
        <div class="space-y-4">
            <h2 class="text-xs font-black text-[var(--text-muted)] uppercase tracking-[0.2em] flex items-center gap-2 px-1">
                <i class="fas fa-id-card text-medical-blue"></i> Tarjetas de Especialistas
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($technicians as $tech): ?>
                    <?php $isActive = $selectedTechId == $tech['id']; ?>
                    <a href="?page=my_work_orders&tech_id=<?= $tech['id'] ?>"
                        class="group relative p-4 rounded-3xl border-2 transition-all flex items-center gap-4 <?= $isActive ? 'bg-medical-blue/15 border-medical-blue ring-4 ring-medical-blue/5 shadow-2xl shadow-medical-blue/20' : 'bg-[var(--panel-dark)] border-[var(--border-color)] hover:border-medical-blue/40' ?>">

                        <div class="w-12 h-12 rounded-2xl bg-[var(--medical-dark)] border-2 <?= $isActive ? 'border-medical-blue overflow-hidden' : 'border-[var(--border-color)]' ?> flex items-center justify-center relative shadow-inner">
                            <?php if (!empty($tech['avatar_url'])): ?>
                                <img src="<?= $tech['avatar_url'] ?>" alt="<?= $tech['name'] ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user-tie text-lg text-[var(--text-muted)] group-hover:text-medical-blue transition-colors"></i>
                            <?php endif; ?>

                            <?php if ($tech['active'] > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] w-5 h-5 flex items-center justify-center rounded-lg font-black border-2 border-[var(--panel-dark)]">
                                    <?= $tech['active'] ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-black truncate <?= $isActive ? 'text-medical-blue' : 'text-[var(--text-main)]' ?>"><?= htmlspecialchars($tech['name']) ?></h3>
                            <p class="text-[10px] text-[var(--text-muted)] uppercase font-bold tracking-tighter truncate"><?= htmlspecialchars($tech['specialty']) ?></p>
                        </div>

                        <?php if ($isActive): ?>
                            <div class="absolute top-2 right-2 w-2 h-2 bg-medical-blue rounded-full"></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="flex items-center justify-between border-b border-[var(--border-color)]">
        <div class="flex space-x-10">
            <button onclick="switchTab('en-curso')" id="tab-btn-en-curso" class="tab-btn active-tab pb-4 text-xs font-black uppercase tracking-[0.15em] text-medical-blue border-b-4 border-medical-blue transition-all">
                En Curso <span class="ml-2 bg-medical-blue/10 text-medical-blue py-0.5 px-2.5 rounded-lg text-[10px]"><?= count($enCurso) ?></span>
            </button>
            <button onclick="switchTab('en-espera')" id="tab-btn-en-espera" class="tab-btn pb-4 text-xs font-black uppercase tracking-[0.15em] text-[var(--text-muted)] hover:text-[var(--text-main)] border-b-4 border-transparent transition-all">
                En Espera <span class="ml-2 bg-[var(--panel-dark)] text-[var(--text-muted)] py-0.5 px-2.5 rounded-lg text-[10px]"><?= count($enEspera) ?></span>
            </button>
        </div>
        <div class="hidden md:flex items-center gap-2 text-[10px] font-bold text-[var(--text-muted)] uppercase tracking-widest">
            <i class="fas fa-info-circle text-medical-blue/40"></i>
            Las tareas terminadas deben consultarse en el Tablero de Gestión.
        </div>
    </div>

    <!-- Tabs Content -->
    <div class="mt-8">
        <!-- En Curso -->
        <div id="tab-en-curso" class="tab-content block pb-10">
            <?php if (empty($enCurso)): ?>
                <div class="p-20 text-center border-2 border-dashed border-[var(--border-color)] rounded-[2.5rem] bg-[var(--panel-dark)]/30">
                    <div class="w-20 h-20 bg-[var(--medical-dark)] rounded-3xl flex items-center justify-center mx-auto mb-6 border border-[var(--border-color)]">
                        <i class="fas fa-check-double text-2xl text-[var(--text-muted)]"></i>
                    </div>
                    <h4 class="text-[var(--text-main)] font-black text-lg mb-2 capitalize">Sin tareas en curso</h4>
                    <p class="text-[var(--text-muted)] text-sm max-w-sm mx-auto">Selecciona otro especialista o verifica las tareas en espera.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($enCurso as $ot) echo renderTaskCard($ot, $isManager); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- En Espera -->
        <div id="tab-en-espera" class="tab-content hidden pb-10">
            <?php if (empty($enEspera)): ?>
                <div class="p-20 text-center border-2 border-dashed border-[var(--border-color)] rounded-[2.5rem] bg-[var(--panel-dark)]/30">
                    <div class="w-20 h-20 bg-[var(--medical-dark)] rounded-3xl flex items-center justify-center mx-auto mb-6 border border-[var(--border-color)]">
                        <i class="fas fa-play-circle text-2xl text-[var(--text-muted)]"></i>
                    </div>
                    <h4 class="text-[var(--text-main)] font-black text-lg mb-2">Sin tareas en espera</h4>
                    <p class="text-[var(--text-muted)] text-sm max-w-sm mx-auto">El técnico seleccionado no tiene tareas en espera en este momento.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($enEspera as $ot) echo renderTaskCard($ot, $isManager); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => {
            el.classList.add('hidden');
            el.classList.remove('block', 'animate-fade-in');
        });

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('text-medical-blue', 'border-medical-blue', 'active-tab');
            btn.classList.add('text-slate-500', 'border-transparent');
        });

        const selectedTab = document.getElementById('tab-' + tabId);
        if (selectedTab) {
            selectedTab.classList.remove('hidden');
            selectedTab.classList.add('block', 'animate-fade-in');
        }

        const selectedBtn = document.getElementById('tab-btn-' + tabId);
        if (selectedBtn) {
            selectedBtn.classList.add('text-medical-blue', 'border-medical-blue', 'active-tab');
            selectedBtn.classList.remove('text-slate-500', 'border-transparent');
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>