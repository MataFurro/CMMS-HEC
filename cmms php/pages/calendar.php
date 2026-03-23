<?php
require_once __DIR__ . '/../Backend/Providers/WorkOrderProvider.php';
require_once __DIR__ . '/../Backend/Providers/AssetProvider.php';
require_once __DIR__ . '/../Backend/Providers/EventProvider.php';

$view = $_GET['view'] ?? 'month';
$currentMonth = (int)($_GET['month'] ?? date('n'));
$currentYear = (int)($_GET['year'] ?? date('Y'));

// Generar rango de fechas para la consulta
if ($view === 'month') {
    $startDate = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = date('Y-m-t', strtotime($startDate));
} elseif ($view === 'year') {
    $startDate = "$currentYear-01-01";
    $endDate = "$currentYear-12-31";
} else {
    $startDate = date('Y-m-d', strtotime('-1 month'));
    $endDate = date('Y-m-d', strtotime('+1 month'));
}

$realOrders = getAgendaEvents($startDate, $endDate);
$predictiveOrders = []; // Removed: getPredictiveAgendaEvents($startDate, $endDate);
$allEvents = array_merge($realOrders, $predictiveOrders);

$weekDays = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$hours = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];

// Adaptar eventos para fácil acceso por fecha en vista mes
$eventsByDate = [];
foreach ($allEvents as $ev) {
    if (isset($ev['date'])) {
        $eventsByDate[$ev['date']][] = $ev;
    }
}

// Estadísticas para el año
$annualWorkload = [];
$months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

foreach ($months as $idx => $m) {
    $monthNum = str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
    $otsThisMonth = array_filter($allEvents, function ($o) use ($monthNum, $currentYear) {
        return strpos($o['date'] ?? '', "$currentYear-$monthNum-") === 0;
    });
    $annualWorkload[] = ['month' => $m, 'ots' => count($otsThisMonth)];
}
?>

<div class="space-y-8 animate-in fade-in duration-500">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tight text-text-main flex items-center gap-3">
                Agenda Técnica 2026
                <span
                    class="text-medical-blue material-symbols-outlined text-3xl font-variation-fill">event_upcoming</span>
            </h1>
            <p class="text-text-muted mt-2 text-lg font-medium opacity-100">Cronograma maestro sincronizado con el módulo de Órdenes de Trabajo.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex bg-panel-dark/50 border border-[var(--border-color)] p-1.5 rounded-2xl">
                <?php
                $views = [
                    ['id' => 'year', 'label' => 'Año'],
                    ['id' => 'month', 'label' => 'Mes'],
                    ['id' => 'week', 'label' => 'Semana'],
                    ['id' => 'day', 'label' => 'Día']
                ];
                foreach ($views as $v):
                    $isActive = $view === $v['id'];
                    $class = $isActive
                        ? 'bg-medical-blue text-white shadow-lg shadow-medical-blue/20'
                        : 'text-text-muted hover:text-text-main';
                ?>
                    <a href="?page=calendar&view=<?= $v['id'] ?>"
                        class="px-5 py-2 text-xs font-bold rounded-xl transition-all <?= $class ?>">
                        <?= $v['label'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <a href="?page=work_orders"
                class="flex items-center gap-3 px-6 py-3 border border-[var(--border-color)] text-text-muted rounded-2xl hover:bg-panel-dark transition-all font-bold text-sm">
                <span class="material-symbols-outlined text-xl">settings_applications</span>
                <span>Manejo de OTs</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Sidebar Legend (Common) -->
        <div class="lg:col-span-3 space-y-6">
            <div class="bg-panel-dark p-6 rounded-3xl border border-[var(--border-color)] shadow-xl">
                <h3 class="text-[10px] font-black text-text-muted uppercase tracking-[0.2em] mb-6 px-1 text-center">
                    Leyenda de Estados</h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-3 bg-panel-dark/40 rounded-xl border border-transparent">
                        <div class="size-3 rounded-full bg-danger"></div>
                        <span class="text-[10px] font-black text-text-muted uppercase tracking-widest">Correctivos Críticos</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-panel-dark/40 rounded-xl border border-transparent">
                        <div class="size-3 rounded-full bg-medical-blue"></div>
                        <span class="text-[10px] font-black text-text-muted uppercase tracking-widest">Preventivos Programados</span>
                    </div>
                    <!-- Sugerencia Predictiva Removed -->
                </div>
                <div class="mt-8 pt-8 border-t border-[var(--border-color)] space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <p class="text-[10px] font-black text-text-muted uppercase tracking-widest">Filtro por Servicio</p>
                        <div class="flex gap-2">
                            <button onclick="toggleAllServices(true)" class="text-[9px] font-bold text-medical-blue uppercase hover:underline">Todos</button>
                            <span class="text-[9px] text-border-dark">|</span>
                            <button onclick="toggleAllServices(false)" class="text-[9px] font-bold text-text-muted uppercase hover:underline">Ninguno</button>
                        </div>
                    </div>

                    <!-- Buscador dinámico de servicios -->
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-sm text-text-muted group-focus-within:text-medical-blue transition-colors">search</span>
                        <input type="text" id="serviceSearch" placeholder="Buscar servicio..."
                            class="w-full bg-panel-dark/50 border border-[var(--border-color)] rounded-xl py-2 pl-9 pr-4 text-xs text-text-main focus:border-medical-blue focus:ring-1 focus:ring-medical-blue/20 transition-all outline-none"
                            onkeyup="filterServices()">
                    </div>

                    <div id="serviceList" class="space-y-1 max-height-[300px] overflow-y-auto pr-2 custom-scrollbar" style="max-height: 280px;">
                        <?php
                        $allLocations = getAllLocations();
                        foreach ($allLocations as $loc): ?>
                            <label class="service-item flex items-center gap-3 p-2 rounded-xl hover:bg-panel-dark cursor-pointer group transition-all" data-service="<?= mb_strtolower(trim($loc), 'UTF-8') ?>">
                                <div class="relative flex items-center justify-center">
                                    <input type="checkbox" checked
                                        class="service-checkbox size-4 rounded-md border-[var(--border-color)] bg-medical-dark text-medical-blue focus:ring-0 checked:border-medical-blue transition-all" />
                                </div>
                                <span class="text-[11px] text-text-muted group-hover:text-text-main font-bold uppercase tracking-wider transition-colors truncate"><?= $loc ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Botón de Aplicar -->
                    <div class="pt-4 flex gap-3">
                        <button onclick="filterServices()"
                            class="flex-1 bg-medical-blue text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-medical-blue/80 transition-all shadow-lg shadow-medical-blue/20">
                            Aplicar Filtros
                        </button>
                        <button onclick="toggleAllServices(true)"
                            title="Restablecer"
                            class="px-4 border border-[var(--border-color)] text-text-muted rounded-xl hover:bg-medical-blue/5 transition-all">
                            <span class="material-symbols-outlined text-sm">restart_alt</span>
                        </button>
                    </div>
                </div>

                <script>
                    function normalizeStr(str) {
                        return str ? str.toLowerCase().trim().normalize("NFD").replace(/[\u0300-\u036f]/g, "") : "";
                    }

                    function filterServices() {
                        const query = normalizeStr(document.getElementById('serviceSearch').value);
                        const checkedServices = Array.from(document.querySelectorAll('.service-checkbox:checked'))
                            .map(cb => normalizeStr(cb.closest('.service-item').getAttribute('data-service')));

                        // Filtrar la lista lateral
                        const items = document.querySelectorAll('.service-item');
                        items.forEach(item => {
                            const service = normalizeStr(item.getAttribute('data-service'));
                            item.style.display = service.includes(query) ? 'flex' : 'none';
                        });

                        // Filtrar los eventos en el calendario (Vista Mes, Semana, Día)
                        const allEvents = document.querySelectorAll('.agenda-event');
                        allEvents.forEach(ev => {
                            const evLoc = normalizeStr(ev.getAttribute('data-location'));
                            const isChecked = checkedServices.includes(evLoc);

                            if (isChecked) {
                                ev.style.display = ''; // Revertir al display original (flex/block)
                                ev.classList.add('animate-in', 'fade-in', 'zoom-in-95');
                            } else {
                                ev.style.display = 'none';
                            }
                        });

                        // Actualizar Heatmap (Vista Año) si existe
                        const heatmapDays = document.querySelectorAll('.heatmap-day');
                        heatmapDays.forEach(day => {
                            const dayEventsStr = day.getAttribute('data-events-json');
                            if (!dayEventsStr) return;

                            const dayEvents = JSON.parse(dayEventsStr);
                            const visibleEvents = dayEvents.filter(e => checkedServices.includes(normalizeStr(e.location)));
                            const count = visibleEvents.length;

                            // Recalcular opacidad
                            const opacity = count > 0 ? Math.min(0.3 + (count * 0.2), 1) : "";
                            const colorClass = count > 0 ? 'bg-medical-blue shadow-sm' : 'bg-slate-400/10';

                            const hasPred = day.classList.contains('has-pred');
                            const borderColor = hasPred ? 'border-amber-500/50' : (count > 0 ? 'border-transparent' : 'border-slate-400/30');

                            day.style.opacity = opacity;
                            day.className = `heatmap-day aspect-square rounded-[3px] border ${borderColor} ${colorClass} transition-all hover:scale-125 hover:z-10 cursor-help relative group/day ${hasPred ? 'has-pred' : ''}`;
                        });
                    }

                    function toggleAllServices(checked) {
                        document.querySelectorAll('.service-checkbox').forEach(cb => {
                            cb.checked = checked;
                        });
                        filterServices();
                    }
                </script>
            </div>
            <div
                class="bg-medical-blue/5 border border-medical-blue/20 p-6 rounded-3xl flex flex-col items-center text-center">
                <span class="material-symbols-outlined text-medical-blue text-3xl mb-3">sync</span>
                <h4 class="text-[10px] font-black text-medical-blue uppercase tracking-widest mb-2">Sincronización
                    Activa</h4>
                <p class="text-xs font-bold text-text-main">Actualizado en tiempo real</p>
            </div>
        </div>

        <!-- Main Content Area based on View -->
        <div class="lg:col-span-9">

            <!-- MONTH VIEW -->
            <?php if ($view === 'month'): ?>
                <div class="bg-panel-dark rounded-[2rem] border border-[var(--border-color)] shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-300">
                    <div class="p-6 border-b border-[var(--border-color)] bg-medical-dark/30 flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <a href="?page=calendar&view=month&month=<?= $currentMonth == 1 ? 12 : $currentMonth - 1 ?>&year=<?= $currentMonth == 1 ? $currentYear - 1 : $currentYear ?>" class="p-2 hover:bg-medical-blue/5 rounded-xl transition-colors">
                                <span class="material-symbols-outlined text-text-muted">chevron_left</span>
                            </a>
                            <h3 class="text-xl font-bold text-text-main uppercase tracking-tight"><?= $months[$currentMonth - 1] . " " . $currentYear ?></h3>
                            <a href="?page=calendar&view=month&month=<?= $currentMonth == 12 ? 1 : $currentMonth + 1 ?>&year=<?= $currentMonth == 12 ? $currentYear + 1 : $currentYear ?>" class="p-2 hover:bg-medical-blue/5 rounded-xl transition-colors">
                                <span class="material-symbols-outlined text-text-muted">chevron_right</span>
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 border-b border-[var(--border-color)] bg-medical-dark/10">
                        <?php foreach ($weekDays as $day): ?>
                            <div class="py-5 text-center text-[10px] font-black text-text-muted uppercase tracking-[0.2em]">
                                <?= substr($day, 0, 2) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="grid grid-cols-7 auto-rows-[140px]">
                        <?php
                        $firstDayOfMonth = strtotime("$currentYear-$currentMonth-01");
                        $daysInMonth = date('t', $firstDayOfMonth);
                        $dayOfWeek = date('N', $firstDayOfMonth) - 1; // 0 (Lunes) a 6 (Domingo)

                        // Días del mes anterior para rellenar
                        $prevMonthDays = date('t', strtotime("-1 month", $firstDayOfMonth));
                        for ($i = $dayOfWeek - 1; $i >= 0; $i--): ?>
                            <div class="p-3 border-r border-b border-[var(--border-color)] opacity-60 bg-medical-dark/10 flex flex-col relative">
                                <span class="text-sm font-black font-mono text-text-muted/40"><?= $prevMonthDays - $i ?></span>
                            </div>
                        <?php endfor;

                        // Días del mes actual
                        for ($d = 1; $d <= $daysInMonth; $d++):
                            $dateKey = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                            $isToday = $dateKey === date('Y-m-d');
                            $dayEvents = $eventsByDate[$dateKey] ?? [];
                        ?>
                            <div onclick="window.location.href='?page=calendar&view=day&date=<?= $dateKey ?>'"
                                class="p-3 border-r border-b border-[var(--border-color)] transition-all hover:bg-panel-dark/40 flex flex-col relative group cursor-pointer <?= $isToday ? 'bg-medical-blue/5' : '' ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-sm font-black font-mono <?= $isToday ? 'text-medical-blue' : 'text-text-muted' ?>">
                                        <?= $d ?>
                                    </span>
                                    <?php if ($isToday): ?>
                                        <span class="size-2 rounded-full bg-medical-blue shadow-[0_0_12px_rgba(14,165,233,0.5)]"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="space-y-1.5 overflow-y-auto custom-scrollbar flex-1">
                                    <?php foreach ($dayEvents as $event):
                                        $typeLabel = $event['type'] === 'PREDICTIVE' ? 'PRED' : 'OT';
                                        $color = $event['color'];
                                        $location = mb_strtolower(trim($event['location'] ?? 'general'), 'UTF-8');
                                    ?>
                                        <div class="agenda-event px-2 py-1 bg-<?= $color ?>/10 border-l-2 border-<?= $color ?> rounded text-[9px] font-black text-<?= $color ?> truncate uppercase pointer-events-none"
                                            data-location="<?= $location ?>"
                                            title="<?= htmlspecialchars($event['title']) ?> (<?= htmlspecialchars($event['location'] ?? 'General') ?>)">
                                            <?= $typeLabel ?>: <?= htmlspecialchars($event['id']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endfor;

                        // Rellenar días del mes siguiente si es necesario
                        $remainingCells = 35 - ($dayOfWeek + $daysInMonth);
                        if ($remainingCells < 0) $remainingCells += 7; // Manejar meses de 6 semanas si aplica (aunque 42 es mejor)

                        // Ajuste para 42 celdas máximo (6 semanas) para cuadrícula perfecta
                        $totalCellsNeeded = ($dayOfWeek + $daysInMonth > 35) ? 42 : 35;
                        $remainingCells = $totalCellsNeeded - ($dayOfWeek + $daysInMonth);

                        for ($i = 1; $i <= $remainingCells; $i++): ?>
                            <div class="p-3 border-r border-b border-[var(--border-color)] opacity-20 bg-medical-dark/30 flex flex-col relative">
                                <span class="text-sm font-black font-mono text-text-muted"><?= $i ?></span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- YEAR VIEW -->
            <?php elseif ($view === 'year'): ?>
                <!-- YEAR VIEW (HEATMAP REFINED) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 animate-in fade-in zoom-in-95 duration-300">
                    <?php foreach ($annualWorkload as $idx => $item):
                        $monthNum = $idx + 1;
                        $monthName = $item['month'];
                        $totalMonthEvents = $item['ots'];

                        // Calcular detalles del mes para el heatmap
                        $firstDay = strtotime("$currentYear-$monthNum-01");
                        $daysInMonth = date('t', $firstDay);
                        $startOffset = date('N', $firstDay) - 1; // 0-6

                        // Filtrar eventos predictivos para tendencia
                        $monthEvents = array_filter($allEvents, function ($e) use ($currentYear, $monthNum) {
                            $m = str_pad($monthNum, 2, '0', STR_PAD_LEFT);
                            return strpos($e['date'] ?? '', "$currentYear-$m-") === 0;
                        });
                        $predictiveCount = count(array_filter($monthEvents, fn($e) => $e['type'] === 'PREDICTIVE'));
                        $realCount = $totalMonthEvents - $predictiveCount;
                    ?>
                        <div class="bg-panel-dark rounded-[2rem] border border-[var(--border-color)] p-6 hover:border-medical-blue/40 transition-all group relative flex flex-col">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="text-sm font-black text-text-main uppercase tracking-widest"><?= $monthName ?></h4>
                                    <p class="text-[9px] font-bold text-text-muted uppercase mt-1">
                                        <?= $predictiveCount ?> Pred. | <?= $realCount ?> Prog.
                                    </p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-[14px] font-black text-text-main"><?= $totalMonthEvents ?></span>
                                    <span class="text-[8px] font-black text-medical-blue uppercase">Eventos</span>
                                </div>
                            </div>

                            <!-- Heatmap Grid -->
                            <div class="grid grid-cols-7 gap-1.5 flex-1 mb-4">
                                <?php
                                // Relleno inicial
                                for ($i = 0; $i < $startOffset; $i++) echo '<div class="aspect-square rounded-[3px] border border-transparent bg-transparent"></div>';

                                // Días del mes
                                for ($d = 1; $d <= $daysInMonth; $d++):
                                    $dateStr = "$currentYear-" . str_pad($monthNum, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                                    $dailyEvents = array_filter($allEvents, fn($e) => ($e['date'] ?? '') === $dateStr);
                                    $count = count($dailyEvents);

                                    // Intensidad basada en cantidad
                                    $opacityStyle = $count > 0 ? 'opacity: ' . min(0.3 + ($count * 0.2), 1) . ';' : '';
                                    $colorClass = $count > 0 ? 'bg-medical-blue shadow-sm' : 'bg-slate-500/10';

                                    // Borde especial si hay predictivos
                                    $hasPredictive = !empty(array_filter($dailyEvents, fn($e) => $e['type'] === 'PREDICTIVE'));
                                    $borderColor = $hasPredictive ? 'border-amber-500/50' : ($count > 0 ? 'border-transparent' : 'border-slate-500/30');
                                ?>
                                    <div class="heatmap-day aspect-square rounded-[3px] border <?= $borderColor ?> <?= $colorClass ?> transition-all hover:scale-125 hover:z-10 cursor-help relative group/day <?= $hasPredictive ? 'has-pred' : '' ?>"
                                        style="<?= $opacityStyle ?>"
                                        data-events-json="<?= htmlspecialchars(json_encode(array_values($dailyEvents))) ?>"
                                        title="<?= $d ?> <?= $monthName ?>: <?= $count ?> eventos">
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-[var(--border-color)]">
                                <div class="flex -space-x-2">
                                    <div class="size-5 rounded-full bg-medical-blue/20 border border-medical-blue/30 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[10px] text-medical-blue">precision_manufacturing</span>
                                    </div>
                                    <div class="size-5 rounded-full bg-amber-500/20 border border-amber-500/30 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-[10px] text-amber-500">psychology</span>
                                    </div>
                                </div>
                                <a href="?page=calendar&view=month&month=<?= $monthNum ?>&year=<?= $currentYear ?>"
                                    class="text-[9px] font-black text-medical-blue uppercase tracking-widest hover:underline">
                                    Ver Detalle
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- WEEK VIEW -->
            <?php elseif ($view === 'week'):
                $startOfWeek = strtotime("this week", strtotime("$currentYear-$currentMonth-" . date('d')));
            ?>
                <div class="bg-panel-dark rounded-[2rem] border border-[var(--border-color)] shadow-2xl overflow-hidden animate-in fade-in slide-in-from-right-4 duration-300">
                    <div class="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr_1fr_1fr] border-b border-[var(--border-color)] bg-medical-dark/30">
                        <div class="py-4 border-r border-[var(--border-color)]"></div>
                        <?php foreach ($weekDays as $idx => $day):
                            $date = date('Y-m-d', strtotime("+$idx days", $startOfWeek));
                        ?>
                            <div class="py-4 text-center text-[10px] font-black text-text-muted uppercase tracking-widest border-r border-[var(--border-color)]/30">
                                <?= $day ?> <span class="block text-text-main opacity-40 mt-1"><?= date('d M', strtotime($date)) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="h-[600px] overflow-y-auto custom-scrollbar">
                        <?php foreach ($hours as $hour): ?>
                            <div class="grid grid-cols-[80px_1fr_1fr_1fr_1fr_1fr_1fr_1fr] border-b border-[var(--border-color)]/30">
                                <div class="py-6 px-3 text-[10px] font-mono text-text-muted border-r border-[var(--border-color)] text-right font-bold uppercase">
                                    <?= $hour ?>
                                </div>
                                <?php foreach ($weekDays as $idx => $day):
                                    $date = date('Y-m-d', strtotime("+$idx days", $startOfWeek));
                                    $dayEvents = array_filter($allEvents, function ($e) use ($date) {
                                        return $e['date'] === $date;
                                    });
                                ?>
                                    <div onclick="window.location.href='?page=calendar&view=day&date=<?= $date ?>'"
                                        class="border-r border-[var(--border-color)]/30 relative min-h-[80px] hover:bg-medical-blue/5 transition-colors p-1 space-y-1 cursor-pointer">
                                        <?php foreach ($dayEvents as $event):
                                            $color = $event['color'];
                                            $location = mb_strtolower(trim($event['location'] ?? 'general'), 'UTF-8');
                                        ?>
                                            <div class="agenda-event p-2 bg-<?= $color ?>/20 border-l-2 border-<?= $color ?> rounded-lg shadow-lg shadow-<?= $color ?>/5 pointer-events-none"
                                                data-location="<?= $location ?>">
                                                <p class="text-[8px] font-black text-text-main uppercase leading-none"><?= htmlspecialchars($event['id']) ?></p>
                                                <p class="text-[7px] text-<?= $color ?> font-bold mt-1 uppercase truncate"><?= htmlspecialchars($event['title']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- DAY VIEW -->
            <?php elseif ($view === 'day'):
                $dateParam = $_GET['date'] ?? date('Y-m-d');
                $selectedDay = $dateParam;
                $dayEvents = array_filter($allEvents, function ($e) use ($selectedDay) {
                    return ($e['date'] ?? '') === $selectedDay;
                });
            ?>
                <div class="bg-panel-dark rounded-[2rem] border border-border-dark shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-300">
                    <div class="p-8 border-b border-border-dark bg-medical-dark/30 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-text-main uppercase tracking-tight"><?= date('l, d \d\e F, Y', strtotime($selectedDay)) ?></h3>
                            <p class="text-xs text-text-muted font-black uppercase tracking-widest mt-1"><?= count($dayEvents) ?> Intervenciones Programadas para hoy</p>
                        </div>
                    </div>
                    <div class="p-8 space-y-8 h-[500px] overflow-y-auto custom-scrollbar">
                        <?php if (empty($dayEvents)): ?>
                            <div class="flex flex-col items-center justify-center py-20 opacity-20">
                                <span class="material-symbols-outlined text-6xl mb-4">event_busy</span>
                                <p class="text-sm font-black uppercase tracking-widest">Sin actividades programadas</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($dayEvents as $event): ?>
                                <div class="agenda-event flex gap-8 group" data-location="<?= mb_strtolower(trim($event['location'] ?? 'general'), 'UTF-8') ?>">
                                    <div class="w-20 pt-1 shrink-0">
                                        <span class="text-lg font-mono font-black text-text-main"><?= $event['time'] ?? '08:00' ?></span>
                                        <div class="mt-2 h-full w-px bg-panel-dark mx-auto opacity-50 group-last:hidden"></div>
                                    </div>
                                    <div class="flex-1 card-glass p-6 border-l-4 border-l-<?= $event['color'] ?> hover:bg-panel-dark/20 transition-all">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-bold text-text-main text-base"><?= htmlspecialchars($event['title']) ?></h4>
                                                <p class="text-sm text-text-muted font-medium mt-1"><?= htmlspecialchars($event['category'] ?? 'Tarea') ?></p>
                                            </div>
                                            <span class="text-[9px] font-black px-3 py-1 rounded-lg uppercase tracking-widest bg-<?= $event['color'] ?>/10 text-<?= $event['color'] ?> border border-<?= $event['color'] ?>/20">
                                                <?= $event['status'] ?>
                                            </span>
                                        </div>
                                        <div class="mt-4 flex items-center gap-6">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-text-muted text-sm">person</span>
                                                <span class="text-[10px] font-bold text-text-muted uppercase"><?= htmlspecialchars($event['tech']) ?></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-text-muted text-sm">location_on</span>
                                                <span class="text-[10px] font-bold text-text-muted uppercase"><?= htmlspecialchars($event['location'] ?? 'Hospital General') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-6 flex justify-between items-center px-4">
                <p class="text-[10px] font-black text-text-muted uppercase tracking-widest">© BioCMMS Agenda - Sincronizado con v4.5</p>
                <div class="flex gap-4">
                    <span class="flex items-center gap-2 text-[10px] font-black text-text-muted uppercase"><span
                            class="size-2 rounded-full bg-[var(--border-color)]"></span> Feriado</span>
                    <span class="flex items-center gap-2 text-[10px] font-black text-text-muted uppercase"><span
                            class="size-2 rounded-full bg-medical-blue"></span> Programado</span>
                    <!-- Predictive (IA) Legend Removed -->
                </div>
            </div>
        </div>
    </div>
</div>